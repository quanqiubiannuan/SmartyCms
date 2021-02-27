<?php

namespace application\home\controller;

use library\mysmarty\Controller;

/**
 * 网站前台页面控制器
 * @package application\home\controller
 */
class Web extends Controller
{
    /**
     * 网站首页
     */
    public function index()
    {
        $this->display();
    }
}