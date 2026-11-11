## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# think-ueditor

UEditor Plus 的 ThinkPHP 8 集成包，内置前端静态资源与后端上传接口。

## 环境要求

- PHP >= 8.0
- ThinkPHP 8
- UEditor Plus 前端产物版本 4.5.0（dist-min）

## 安装

```bash
composer require youvision/think-ueditor
```

安装后 ThinkPHP 会自动完成：

- 服务注册：`extra.think.services`
- 配置复制：`extra.think.config`，生成 `config/ueditor.php`（已存在则不覆盖）
- 路由注册：默认 `/ueditor/server`

## 发布静态资源

```bash
php think ueditor:publish
```

升级包之后重新发布并覆盖：

```bash
composer update youvision/think-ueditor
php think ueditor:publish --force
```

默认发布到 `public/vendor/ueditor`，可加 `--force` 覆盖已有文件。不要直接修改包内 `resources/assets/ueditor`，自动同步会覆盖。

## 配置

配置文件位于 `config/ueditor.php`，每个配置项均有中文说明。常用项：

| 配置 | 说明 |
| --- | --- |
| `route` | 后端接口地址，默认 `/ueditor/server`，留空则不注册路由 |
| `middleware` | 接口中间件，用于登录鉴权 |
| `upload_auth` | 上传授权回调，返回 false 拒绝上传 |
| `upload_handler` | 自定义上传处理器，用于接入 OSS、COS、七牛 |
| `storage.root` | 存储根目录，留空为 `public/storage` |
| `storage.url` | 资源访问前缀，默认 `/storage` |
| `image.allow_exts` | 图片允许的扩展名 |
| `image.max_size` | 图片最大字节数 |
| `catcher.enable` | 是否开启远程图片抓取，默认关闭 |

业务代码中读取配置：

```php
config('ueditor.image.max_size');
```

## 前端使用

```html
<script>window.UEDITOR_HOME_URL = '/vendor/ueditor/';</script>
<script src="/vendor/ueditor/ueditor.config.js"></script>
<script src="/vendor/ueditor/ueditor.all.js"></script>

<script id="editor" type="text/plain" style="height:500px"></script>
<script>
var ue = UE.getEditor('editor', {
    serverUrl: '/ueditor/server'
});
</script>
```

`loadConfigFromServer` 为 true 时，前端会请求 `serverUrl?action=config` 获取上传配置，再按配置中的 action 名称发起上传。

## 后端接口

| action | 说明 |
| --- | --- |
| `config` | 返回完整前端配置 |
| `uploadimage` | 图片上传 |
| `uploadscrawl` | 涂鸦上传，支持文件与 base64 |
| `uploadvideo` | 视频上传 |
| `uploadaudio` | 音频上传 |
| `uploadfile` | 附件上传 |
| `listimage` | 图片列表，参数 `start`、`size` |
| `listfile` | 文件列表，参数 `start`、`size` |
| `catchimage` | 远程图片抓取，参数 `source[]`，默认关闭 |

上传成功响应：

```json
{
  "state": "SUCCESS",
  "url": "/storage/ueditor/image/20261107/120000_ab12cd34ef.jpg",
  "title": "120000_ab12cd34ef.jpg",
  "original": "photo.jpg",
  "type": ".jpg",
  "size": 123456
}
```

失败时 `state` 为具体错误信息，HTTP 状态码仍为 200。

## 自定义上传

在 `config/ueditor.php` 中配置 `upload_handler`，签名如下：

```php
'upload_handler' => function (string $type, \think\file\UploadedFile $file, array $config): array {
    $url = \app\common\Storage::put($type, $file);

    return [
        'state' => 'SUCCESS',
        'url' => $url,
        'title' => $file->getOriginalName(),
        'original' => $file->getOriginalName(),
        'type' => '.' . strtolower($file->getOriginalExtension()),
        'size' => $file->getSize(),
    ];
},
```

返回数组必须包含 `state`，成功时包含 `url`。配置后不再执行本地存储逻辑。

## 上传鉴权

推荐配置中间件，避免接口被匿名调用：

```php
'middleware' => [
    \app\middleware\AdminAuth::class,
],
```

也可以只拦截上传、放行配置请求：

```php
'upload_auth' => function (\think\Request $request): bool {
    return (bool) session('admin_id');
},
```

## 自动同步上游

`.github/workflows/sync-ueditor.yml` 每天检查 UEditor Plus 的最新正式版 Release，下载 `dist-min.zip`，铺平到 `resources/assets/ueditor`，更新版本号并打同名 tag。Packagist 安装 GitHub App 或配置 webhook 后即可自动收录新版本。

- 不跟随 `snapshot` 快照
- 产物必须提交进 tag 指向的 commit，Composer 不会下载 Release 附件
- 本地适配代码改动但上游未发版时，使用四段式版本，例如 `v4.5.0.1`
- 浏览器缓存问题：资源目录内含 `version.txt`，前端可在 URL 后追加 `?v=4.5.0`

## 安全建议

- 远程抓取默认关闭，开启后仅允许公网 HTTP/HTTPS 地址
- 上传扩展名使用白名单，禁止 `php`、`phtml` 等可执行类型
- 上传文件名随机化，避免覆盖与路径猜测
- 生产环境为接口配置登录鉴权中间件
- 确保 `public/storage` 不解析 PHP，Web 服务器应关闭该目录的脚本执行

## 许可证

Apache-2.0。内置前端产物来自 [modstart-lib/ueditor-plus](https://github.com/modstart-lib/ueditor-plus)，版权归其作者所有，详见 `NOTICE` 与 `LICENSE`。