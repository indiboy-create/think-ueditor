<?php

namespace Youvision\ThinkUeditor\Command;

use think\console\Command;
use think\console\input\Option;

class Publish extends Command
{
    protected function configure(): void
    {
        $this->setName('ueditor:publish')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在的静态资源')
            ->setDescription('发布 UEditor Plus 静态资源到 public/vendor/ueditor');
    }

    protected function handle(): void
    {
        $source = dirname(__DIR__, 2) . '/resources/assets/ueditor';
        $target = $this->app->getRootPath() . 'public/vendor/ueditor';

        if (!is_dir($source)) {
            $this->output->error("静态资源不存在：{$source}");
            return;
        }

        $force = (bool) $this->input->getOption('force');
        $count = $this->copyDir($source, $target, $force);

        $this->output->writeln("<info>UEditor Plus 已发布到 {$target}（{$count} 个文件）</info>");
    }

    private function copyDir(string $src, string $dst, bool $force): int
    {
        is_dir($dst) || mkdir($dst, 0755, true);

        $count = 0;

        foreach (scandir($src) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $from = $src . DIRECTORY_SEPARATOR . $item;
            $to = $dst . DIRECTORY_SEPARATOR . $item;

            if (is_dir($from)) {
                $count += $this->copyDir($from, $to, $force);
                continue;
            }

            if ($force || !is_file($to)) {
                copy($from, $to);
            }

            $count++;
        }

        return $count;
    }
}