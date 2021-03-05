<?php

namespace application\home\controller;

use library\mysmarty\Route;

#[Route('/api')]
class Api extends Backend
{
    /**
     * api配置
     */
    public function index()
    {
        if (isPost()) {
            $token = getPostString('token');
            $apikey = getPostString('apikey');
            // 自定义配置文件夹
            $configDir = APPLICATION_DIR . '/' . MODULE . '/config';
            // api配置
            $apiConfigStr = <<<STR
<?php
return [
    'token' => '{$token}',
    'apikey' => '{$apikey}'
];
STR;
            if (false === file_put_contents($configDir . '/api.php', $apiConfigStr)) {
                $this->error('api配置保存失败');
            }
            $this->success('保存配置成功');
        }
        $this->display();
    }
}