<?php

namespace application\home\controller;

use library\mysmarty\Route;

#[Route('/sitemap')]
class SiteMap extends Backend
{
    /**
     * 网站地图xml
     */
    public function index()
    {
        if (isPost()) {
            $type = getPostInt('type');
            $num = getPostInt('num');
            // 自定义配置文件夹
            $configDir = APPLICATION_DIR . '/' . MODULE . '/config';
            // sitemap配置
            $sitemapConfigStr = <<<STR
<?php
return [
    'type' => '{$type}',
    'num' => '{$num}'
];
STR;
            if (false === file_put_contents($configDir . '/sitemap.php', $sitemapConfigStr)) {
                $this->error('sitemap配置保存失败');
            }
            $this->success('保存配置成功');
        }
        $this->display();
    }
}