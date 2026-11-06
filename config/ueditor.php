<?php

return [
    // 是否启用 UEditor 后端接口，关闭后所有请求直接返回“接口已关闭”
    'enable' => true,

    // 接口路由地址，前端 serverUrl 需与此保持一致；设为空字符串则不自动注册路由
    'route' => '/ueditor/server',

    // 接口路由中间件，例如 [\app\middleware\AdminAuth::class]，留空表示不鉴权
    'middleware' => [],

    // 上传授权回调，签名 function (\think\Request $request): bool，返回 false 拒绝上传
    // 未登录用户禁止上传时，可配置为 [\app\common\UeditorAuth::class, 'check']
    'upload_auth' => null,

    // 自定义上传处理器，签名 function (string $type, \think\file\UploadedFile $file, array $config): array
    // 返回 UEditor 标准响应数组（至少包含 state 与 url），用于接入 OSS、COS、七牛等云存储
    // 配置后本地存储逻辑不再执行
    'upload_handler' => null,

    // 本地存储配置
    'storage' => [
        // 存储根目录，留空表示 public/storage；必须是可写的绝对路径或相对应用根目录的路径
        'root' => '',
        // 资源访问 URL 前缀，需与 Web 服务器的实际映射一致
        'url' => '/storage',
        // 按日期分目录的格式，例如 Ymd 生成 20261107
        'date_format' => 'Ymd',
    ],

    // 图片上传
    'image' => [
        // UEditor 前端请求的 action 名称
        'action' => 'uploadimage',
        // 文件字段名
        'field' => 'upfile',
        // 允许的最大字节数，默认 10MB
        'max_size' => 10 * 1024 * 1024,
        // 允许的扩展名，必须带点且小写
        'allow_exts' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'],
        // 资源 URL 前缀，用于 CDN；留空表示使用 storage.url
        'url_prefix' => '',
        // 前端插入后的对齐方式：none、left、right、center
        'insert_align' => 'none',
        // 前端是否启用图片压缩
        'compress_enable' => true,
        // 压缩后的最大边长
        'compress_border' => 5000,
    ],

    // 涂鸦上传
    'scrawl' => [
        'action' => 'uploadscrawl',
        'field' => 'upfile',
        'max_size' => 10 * 1024 * 1024,
        'allow_exts' => ['.png', '.jpg', '.jpeg'],
        'url_prefix' => '',
        'insert_align' => 'none',
    ],

    // 视频上传
    'video' => [
        'action' => 'uploadvideo',
        'field' => 'upfile',
        'max_size' => 100 * 1024 * 1024,
        'allow_exts' => ['.mp4', '.webm', '.ogg', '.mov'],
        'url_prefix' => '',
    ],

    // 音频上传
    'audio' => [
        'action' => 'uploadaudio',
        'field' => 'upfile',
        'max_size' => 50 * 1024 * 1024,
        'allow_exts' => ['.mp3', '.wav', '.ogg', '.m4a'],
        'url_prefix' => '',
    ],

    // 附件上传
    'file' => [
        'action' => 'uploadfile',
        'field' => 'upfile',
        'max_size' => 50 * 1024 * 1024,
        'allow_exts' => ['.zip', '.rar', '.7z', '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.txt'],
        'url_prefix' => '',
    ],

    // 远程图片抓取，存在 SSRF 风险，默认关闭
    'catcher' => [
        // 是否启用抓取
        'enable' => false,
        'action' => 'catchimage',
        'field' => 'source',
        'max_size' => 10 * 1024 * 1024,
        'allow_exts' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
        'url_prefix' => '',
        // 允许抓取的本地域名，用于开发环境
        'local_domain' => ['127.0.0.1', 'localhost'],
    ],

    // 在线管理与文件列表
    'manager' => [
        'image' => [
            'action' => 'listimage',
            'list_size' => 20,
            'allow_exts' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'],
            'url_prefix' => '',
            'insert_align' => 'none',
        ],
        'file' => [
            'action' => 'listfile',
            'list_size' => 20,
            'allow_exts' => ['.zip', '.rar', '.7z', '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.txt'],
            'url_prefix' => '',
        ],
    ],

    // UEditor Plus 工具栏显示项，例如 ['ai' => true] 显示 AI 按钮
    'toolbar_shows' => [],

    // UEditor Plus 右键菜单显示项
    'shortcut_menu_shows' => [],
];