<?php

namespace application\home\controller;

use library\mysmarty\Controller;

/**
 * 网站前台页面控制器
 * @package application\home\controller
 */
class Web extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // 查找所有栏目数据
        $column = new \application\home\model\Column();
        $columnData = $column->order('pid asc,id asc')
            ->field('id,uri,pid,name,type,target_blank,keywords,description')
            ->neq('status', 3)
            ->select();

    }

    /**
     * 网站首页
     */
    public function index()
    {
        $this->display();
    }
}