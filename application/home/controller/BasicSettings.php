<?php

namespace application\home\controller;

use library\mysmarty\Redis;
use library\mysmarty\Route;

#[Route('/basic_settings')]
class BasicSettings extends Backend
{
    /**
     * 基本设置
     */
    public function index()
    {
        if (isPost()) {
            $appUrl = getPostString('app_url');
            $title = getPostString('title');
            if (empty($title)) {
                $title = 'SmartyCms内容管理系统';
            }
            $keywords = getPostString('keywords');
            if (!empty($keywords)) {
                $keywords = str_ireplace('，', ',', $keywords);
            }
            $description = getPostString('description');
            $lifetime = getPostInt('lifetime');
            if (empty($lifetime)) {
                $lifetime = 604800;
            }
            $cachingType = getPostString('caching_type');
            $cacheLifeTime = getPostInt('cache_life_time');
            if (empty($cacheLifeTime)) {
                $cacheLifeTime = 3600;
            }
            $host = getPostString('host');
            $port = getPostInt('port');
            $pass = getPostString('pass');
            $js = getPostString('js');
            $db = getPostInt('db');
            $loadOutputFilter = getPostInt('load_output_filter') === 1 ? 'true' : 'false';
            // 自定义配置文件夹
            $configDir = APPLICATION_DIR . '/' . MODULE . '/config';
            // app配置
            $appConfigStr = <<<STR
<?php
return [
    'app_url' => '{$appUrl}',
];
STR;
            if (false === file_put_contents($configDir . '/app.php', $appConfigStr)) {
                $this->error('app配置保存失败');
            }
            $icp = getPostString('icp');
            $pns = getPostString('pns');
            $pnscode = '';
            if (!empty($pns)) {
                if (preg_match('/([\d]+)/', $pns, $mat)) {
                    $pnscode = $mat[1];
                }
            }
            // 网站配置
            $templetConfigStr = <<<STR
title = {$title}
keywords = {$keywords}
description = {$description}
icp = {$icp}
pns = {$pns}
pnscode = {$pnscode}
js = """{$js}"""
STR;
            if (false === file_put_contents($configDir . '/templet.conf', $templetConfigStr)) {
                $this->error('网站配置保存失败');
            }
            // cookie/session有效期配置
            $cookieConfigStr = <<<STR
<?php
return [
    'expire' => {$lifetime},
];
STR;
            if (false === file_put_contents($configDir . '/cookie.php', $cookieConfigStr)) {
                $this->error('cookie配置保存失败');
            }
            $sessionConfigStr = <<<STR
<?php
return [
    'lifetime' => {$lifetime},
];
STR;
            if (false === file_put_contents($configDir . '/session.php', $sessionConfigStr)) {
                $this->error('session配置保存失败');
            }
            // Redis配置
            $redisConfigStr = <<<STR
<?php
return [
     'redis' => [
        'host' => '{$host}',
        'port' => {$port},
        'pass' => '{$pass}'
    ],
];
STR;
            if (false === file_put_contents($configDir . '/database.php', $redisConfigStr)) {
                $this->error('Redis配置保存失败');
            }
            // 缓存配置
            $cache = empty($cachingType) ? 0 : 1;
            $mysmartyConfigStr = <<<STR
<?php
return [
    'cache' => {$cache},
    'caching_type' => '{$cachingType}',
    'caching_type_params' => [
        'redis' => [
            'db' => $db
        ]
    ],
    'cache_life_time' => {$cacheLifeTime},
    'load_output_filter' => {$loadOutputFilter},
];
STR;
            if (false === file_put_contents($configDir . '/mysmarty.php', $mysmartyConfigStr)) {
                $this->error('mysmarty配置保存失败');
            }
            $this->success('保存配置成功');
        }
        $this->display();
    }
}