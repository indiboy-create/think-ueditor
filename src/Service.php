<?php

namespace Youvision\ThinkUeditor;

use think\Service as BaseService;
use Youvision\ThinkUeditor\Command\Publish;
use Youvision\ThinkUeditor\Support\Config;

class Service extends BaseService
{
    public function register(): void
    {
        $this->app->bind('ueditor', Config::class);
    }

    public function boot(): void
    {
        $this->commands([
            Publish::class,
        ]);

        $path = config('ueditor.route', '/ueditor/server');

        if (is_string($path) && $path !== '') {
            $this->loadRoutesFrom(__DIR__ . '/../route/ueditor.php');
        }
    }
}