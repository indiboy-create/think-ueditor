<?php

namespace Youvision\ThinkUeditor\Support;

use think\file\UploadedFile;
use Throwable;

class Uploader
{
    public static function upload(string $type, UploadedFile $file): array
    {
        $conf = (array) Config::get($type, []);
        $ext = static::ext($file);
        $error = static::validate($conf, $file->getSize(), $ext);

        if ($error !== null) {
            return ['state' => $error];
        }

        $handler = Config::get('upload_handler');

        if (is_callable($handler)) {
            $result = $handler($type, $file, $conf);

            if (is_array($result) && isset($result['state'])) {
                return $result;
            }
        }

        return static::store_file($type, $file, $conf, $ext);
    }

    public static function upload_scrawl(string $content): array
    {
        $conf = (array) Config::get('scrawl', []);

        if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,(.+)$/s', $content, $match)) {
            $ext = '.' . strtolower($match[1]);
            $raw = base64_decode($match[2], true);
        } else {
            $ext = '.png';
            $raw = base64_decode($content, true);
        }

        if ($raw === false || $raw === '') {
            return ['state' => '涂鸦数据解析失败'];
        }

        $error = static::validate($conf, strlen($raw), $ext);

        if ($error !== null) {
            return ['state' => $error];
        }

        $relative = static::relative($conf, 'scrawl');
        $dir = Config::storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $name = date('His') . '_' . bin2hex(random_bytes(6)) . $ext;

        if (!static::ensure_dir($dir)) {
            return ['state' => '上传目录创建失败'];
        }

        if (file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $raw) === false) {
            return ['state' => '文件保存失败'];
        }

        if (@getimagesize($dir . DIRECTORY_SEPARATOR . $name) === false) {
            @unlink($dir . DIRECTORY_SEPARATOR . $name);
            return ['state' => '文件不是有效的图片'];
        }

        return [
            'state' => 'SUCCESS',
            'url' => static::url($conf, $relative, $name),
            'title' => $name,
            'original' => 'scrawl' . $ext,
            'type' => $ext,
            'size' => strlen($raw),
        ];
    }

    public static function lists(string $type, int $start, int $size): array
    {
        $conf = (array) Config::get('manager.' . $type, []);
        $allow = static::normalize_exts((array) ($conf['allow_exts'] ?? []));
        $root = Config::storage_root() . DIRECTORY_SEPARATOR . 'ueditor' . DIRECTORY_SEPARATOR . $type;
        $prefix = static::prefix($conf);

        $files = [];

        if (is_dir($root)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $item) {
                if (!$item->isFile()) {
                    continue;
                }

                $ext = '.' . strtolower($item->getExtension());

                if ($allow !== [] && !in_array($ext, $allow, true)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($item->getPathname(), strlen(Config::storage_root())));

                $files[] = [
                    'url' => $prefix . $relative,
                    'mtime' => $item->getMTime(),
                ];
            }
        }

        usort($files, static fn (array $a, array $b) => $b['mtime'] <=> $a['mtime']);

        $total = count($files);
        $list = array_slice($files, max(0, $start), $size > 0 ? $size : 20);

        return [
            'state' => 'SUCCESS',
            'list' => $list,
            'start' => $start,
            'total' => $total,
        ];
    }

    public static function catch_image(string $url): array
    {
        if (!Config::get('catcher.enable', false)) {
            return ['state' => '未开启远程图片抓取', 'source' => htmlspecialchars($url)];
        }

        $conf = (array) Config::get('catcher', []);
        $parts = parse_url($url);

        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return ['state' => '图片地址不合法', 'source' => htmlspecialchars($url)];
        }

        $ip = gethostbyname($parts['host']);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['state' => '禁止抓取内网地址', 'source' => htmlspecialchars($url)];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'ThinkUeditor/1.0',
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $mime = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            return ['state' => '图片抓取失败', 'source' => htmlspecialchars($url)];
        }

        $allow = static::normalize_exts((array) ($conf['allow_exts'] ?? []));
        $ext = static::ext_from_mime($mime);

        if (!in_array($ext, $allow, true)) {
            return ['state' => '图片类型不允许', 'source' => htmlspecialchars($url)];
        }

        $error = static::validate($conf, strlen($body), $ext);

        if ($error !== null) {
            return ['state' => $error, 'source' => htmlspecialchars($url)];
        }

        $relative = static::relative($conf, 'image');
        $dir = Config::storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $name = date('His') . '_' . bin2hex(random_bytes(6)) . $ext;

        if (!static::ensure_dir($dir)) {
            return ['state' => '上传目录创建失败', 'source' => htmlspecialchars($url)];
        }

        if (file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $body) === false) {
            return ['state' => '文件保存失败', 'source' => htmlspecialchars($url)];
        }

        if (@getimagesize($dir . DIRECTORY_SEPARATOR . $name) === false) {
            @unlink($dir . DIRECTORY_SEPARATOR . $name);
            return ['state' => '文件不是有效的图片', 'source' => htmlspecialchars($url)];
        }

        return [
            'state' => 'SUCCESS',
            'url' => static::url($conf, $relative, $name),
            'size' => strlen($body),
            'title' => $name,
            'original' => basename((string) ($parts['path'] ?? '')),
            'source' => htmlspecialchars($url),
        ];
    }

    protected static function store_file(string $type, UploadedFile $file, array $conf, string $ext): array
    {
        $original = $file->getOriginalName();
        $size = $file->getSize();
        $relative = static::relative($conf, $type);
        $dir = Config::storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $name = date('His') . '_' . bin2hex(random_bytes(6)) . $ext;

        if (!static::ensure_dir($dir)) {
            return ['state' => '上传目录创建失败'];
        }

        try {
            $file->move($dir, $name);
        } catch (Throwable $e) {
            return ['state' => '文件保存失败：' . $e->getMessage()];
        }

        $target = $dir . DIRECTORY_SEPARATOR . $name;

        if (!is_file($target)) {
            return ['state' => '文件保存失败'];
        }

        if (in_array($type, ['image', 'scrawl'], true) && @getimagesize($target) === false) {
            @unlink($target);
            return ['state' => '文件不是有效的图片'];
        }

        return [
            'state' => 'SUCCESS',
            'url' => static::url($conf, $relative, $name),
            'title' => $name,
            'original' => $original,
            'type' => $ext,
            'size' => $size,
        ];
    }

    protected static function validate(array $conf, int $size, string $ext): ?string
    {
        $allow = static::normalize_exts((array) ($conf['allow_exts'] ?? []));

        if ($ext === '' || ($allow !== [] && !in_array($ext, $allow, true))) {
            return '不允许的文件类型';
        }

        $max = (int) ($conf['max_size'] ?? 0);

        if ($max > 0 && $size > $max) {
            return '文件大小超出限制';
        }

        return null;
    }

    protected static function relative(array $conf, string $type): string
    {
        $format = (string) Config::get('storage.date_format', 'Ymd');

        return 'ueditor/' . $type . '/' . date($format);
    }

    protected static function url(array $conf, string $relative, string $name): string
    {
        return static::prefix($conf) . '/' . $relative . '/' . $name;
    }

    protected static function prefix(array $conf): string
    {
        $prefix = rtrim((string) ($conf['url_prefix'] ?? ''), '/');

        return $prefix !== '' ? $prefix : Config::storage_url();
    }

    protected static function ensure_dir(string $dir): bool
    {
        return is_dir($dir) || mkdir($dir, 0755, true) || is_dir($dir);
    }

    protected static function ext(UploadedFile $file): string
    {
        $ext = strtolower($file->getOriginalExtension());

        return $ext === '' ? '' : '.' . ltrim($ext, '.');
    }

    protected static function normalize_exts(array $exts): array
    {
        return array_values(array_filter(array_map(
            static fn ($ext) => '.' . strtolower(ltrim((string) $ext, '.')),
            $exts
        )));
    }

    protected static function ext_from_mime(string $mime): string
    {
        $map = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/bmp' => '.bmp',
        ];

        foreach ($map as $type => $ext) {
            if (stripos($mime, $type) === 0) {
                return $ext;
            }
        }

        return '';
    }
}