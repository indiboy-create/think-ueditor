<?php

namespace Youvision\ThinkUeditor\Support;

class Config
{
    public static function load(): array
    {
        $default = require dirname(__DIR__, 2) . '/config/ueditor.php';
        $user = (array) config('ueditor', []);

        foreach ($user as $key => $value) {
            if (is_array($value) && isset($default[$key]) && is_array($default[$key])) {
                $default[$key] = array_replace($default[$key], $value);
                continue;
            }

            $default[$key] = $value;
        }

        return $default;
    }

    public static function get(string $key = '', $default = null)
    {
        $config = static::load();

        if ($key === '') {
            return $config;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }

            $config = $config[$segment];
        }

        return $config;
    }

    public static function storage_root(): string
    {
        $root = (string) static::get('storage.root', '');

        if ($root === '') {
            return app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'storage';
        }

        if (!preg_match('/^([a-zA-Z]:[\\\\\/]|\/)/', $root)) {
            return app()->getRootPath() . ltrim($root, '/\\');
        }

        return rtrim($root, '/\\');
    }

    public static function storage_url(): string
    {
        return rtrim((string) static::get('storage.url', '/storage'), '/');
    }

    public static function build(): array
    {
        return [
            'imageActionName' => (string) static::get('image.action', 'uploadimage'),
            'imageFieldName' => (string) static::get('image.field', 'upfile'),
            'imageMaxSize' => (int) static::get('image.max_size', 10485760),
            'imageAllowFiles' => array_values((array) static::get('image.allow_exts', [])),
            'imageCompressEnable' => (bool) static::get('image.compress_enable', true),
            'imageCompressBorder' => (int) static::get('image.compress_border', 5000),
            'imageInsertAlign' => (string) static::get('image.insert_align', 'none'),
            'imageUrlPrefix' => '',

            'scrawlActionName' => (string) static::get('scrawl.action', 'uploadscrawl'),
            'scrawlFieldName' => (string) static::get('scrawl.field', 'upfile'),
            'scrawlMaxSize' => (int) static::get('scrawl.max_size', 10485760),
            'scrawlUrlPrefix' => '',
            'scrawlInsertAlign' => (string) static::get('scrawl.insert_align', 'none'),

            'snapscreenActionName' => (string) static::get('image.action', 'uploadimage'),
            'snapscreenUrlPrefix' => '',
            'snapscreenInsertAlign' => 'none',

            'catcherLocalDomain' => array_values((array) static::get('catcher.local_domain', [])),
            'catcherActionName' => (string) static::get('catcher.action', 'catchimage'),
            'catcherFieldName' => (string) static::get('catcher.field', 'source'),
            'catcherUrlPrefix' => '',
            'catcherMaxSize' => (int) static::get('catcher.max_size', 10485760),
            'catcherAllowFiles' => array_values((array) static::get('catcher.allow_exts', [])),

            'videoActionName' => (string) static::get('video.action', 'uploadvideo'),
            'videoFieldName' => (string) static::get('video.field', 'upfile'),
            'videoUrlPrefix' => '',
            'videoMaxSize' => (int) static::get('video.max_size', 104857600),
            'videoAllowFiles' => array_values((array) static::get('video.allow_exts', [])),

            'audioActionName' => (string) static::get('audio.action', 'uploadaudio'),
            'audioFieldName' => (string) static::get('audio.field', 'upfile'),
            'audioUrlPrefix' => '',
            'audioMaxSize' => (int) static::get('audio.max_size', 52428800),
            'audioAllowFiles' => array_values((array) static::get('audio.allow_exts', [])),

            'fileActionName' => (string) static::get('file.action', 'uploadfile'),
            'fileFieldName' => (string) static::get('file.field', 'upfile'),
            'fileUrlPrefix' => '',
            'fileMaxSize' => (int) static::get('file.max_size', 52428800),
            'fileAllowFiles' => array_values((array) static::get('file.allow_exts', [])),

            'imageManagerActionName' => (string) static::get('manager.image.action', 'listimage'),
            'imageManagerListSize' => (int) static::get('manager.image.list_size', 20),
            'imageManagerUrlPrefix' => '',
            'imageManagerInsertAlign' => (string) static::get('manager.image.insert_align', 'none'),
            'imageManagerAllowFiles' => array_values((array) static::get('manager.image.allow_exts', [])),

            'fileManagerActionName' => (string) static::get('manager.file.action', 'listfile'),
            'fileManagerUrlPrefix' => '',
            'fileManagerListSize' => (int) static::get('manager.file.list_size', 20),
            'fileManagerAllowFiles' => array_values((array) static::get('manager.file.allow_exts', [])),

            'toolbarShows' => (object) (array) static::get('toolbar_shows', []),
            'shortcutMenuShows' => (object) (array) static::get('shortcut_menu_shows', []),
        ];
    }
}