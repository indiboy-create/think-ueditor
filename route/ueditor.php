<?php

use think\facade\Route;
use Youvision\ThinkUeditor\Controller\Server;

$path = config('ueditor.route', '/ueditor/server');

if (is_string($path) && $path !== '') {
    Route::rule($path, Server::class . '@index', 'GET|POST')
        ->middleware((array) config('ueditor.middleware', []));
}