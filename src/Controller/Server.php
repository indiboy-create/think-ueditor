<?php

namespace Youvision\ThinkUeditor\Controller;

use think\Request;
use think\Response;
use Youvision\ThinkUeditor\Support\Config;
use Youvision\ThinkUeditor\Support\Uploader;

class Server
{
    public function index(Request $request): Response
    {
        if (!Config::get('enable', true)) {
            return $this->result(['state' => 'UEditor 接口已关闭'], $request);
        }

        $action = (string) $request->param('action', 'config');

        if ($action === '' || $action === 'config') {
            return $this->result(Config::build(), $request);
        }

        $type = $this->resolve_action($action);

        if ($type === null) {
            return $this->result(['state' => '未知的 action：' . $action], $request);
        }

        if (in_array($type, ['image', 'scrawl', 'video', 'audio', 'file'], true)) {
            return $this->upload($type, $request);
        }

        if ($type === 'listimage' || $type === 'listfile') {
            return $this->lists(substr($type, 4), $request);
        }

        return $this->catch_images($request);
    }

    protected function resolve_action(string $action): ?string
    {
        $map = [
            (string) Config::get('image.action', 'uploadimage') => 'image',
            (string) Config::get('scrawl.action', 'uploadscrawl') => 'scrawl',
            (string) Config::get('video.action', 'uploadvideo') => 'video',
            (string) Config::get('audio.action', 'uploadaudio') => 'audio',
            (string) Config::get('file.action', 'uploadfile') => 'file',
            (string) Config::get('manager.image.action', 'listimage') => 'listimage',
            (string) Config::get('manager.file.action', 'listfile') => 'listfile',
            (string) Config::get('catcher.action', 'catchimage') => 'catchimage',
        ];

        $aliases = [
            'image' => 'image',
            'crawl' => 'scrawl',
            'video' => 'video',
            'audio' => 'audio',
            'file' => 'file',
            'listImage' => 'listimage',
            'listFile' => 'listfile',
            'catch' => 'catchimage',
        ];

        return $map[$action] ?? $aliases[$action] ?? null;
    }

    protected function upload(string $type, Request $request): Response
    {
        $auth = Config::get('upload_auth');

        if (is_callable($auth) && $auth($request) === false) {
            return $this->result(['state' => '无上传权限'], $request);
        }

        $field = (string) Config::get($type . '.field', 'upfile');
        $file = $request->file($field) ?: $request->file('file') ?: $request->file('upfile');

        if ($type === 'scrawl' && !$file) {
            return $this->result(
                Uploader::upload_scrawl((string) $request->param($field, '')),
                $request
            );
        }

        if (!$file) {
            return $this->result(['state' => '未找到上传文件'], $request);
        }

        return $this->result(Uploader::upload($type, $file), $request);
    }

    protected function lists(string $type, Request $request): Response
    {
        $start = (int) $request->param('start', 0);
        $size = (int) $request->param('size', Config::get('manager.' . $type . '.list_size', 20));

        return $this->result(Uploader::lists($type, $start, $size), $request);
    }

    protected function catch_images(Request $request): Response
    {
        $sources = $request->param('source', []);

        if (!is_array($sources)) {
            $sources = [$sources];
        }

        $list = [];

        foreach (array_slice($sources, 0, 10) as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }

            $list[] = Uploader::catch_image($url);
        }

        return $this->result(['state' => 'SUCCESS', 'list' => $list], $request);
    }

    protected function result(array $data, Request $request): Response
    {
        $callback = (string) $request->param('callback', '');

        if ($callback !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $callback)) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return response($callback . '(' . $json . ');', 200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
            ]);
        }

        return json($data);
    }
}