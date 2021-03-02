<?php

namespace application\home\controller;

use library\mysmarty\Controller;

/**
 * 网站前台页面控制器
 * @package application\home\controller
 */
class Web extends Controller
{
    // 栏目数据
    private array $columnData;

    public function __construct()
    {
        parent::__construct();
        // 查找所有栏目数据
        $column = new \application\home\model\Column();
        $columnData = $column->order('pid asc,id asc')
            ->field('id,url,pid,name,type,target_blank,keywords,description,status')
            ->neq('status', 3)
            ->select();
        $this->columnData = $columnData;
        $topColumnData = [];
        $bottomColumnData = [];
        foreach ($columnData as $v) {
            if (1 == $v['status']) {
                $topColumnData[] = $v;
            } else if (2 == $v['status']) {
                $bottomColumnData[] = $v;
            }
        }
        $tmpTopColumnData = [];
        foreach ($topColumnData as $v) {
            $tmpTopColumnData[$v['pid']][] = $v;
        }
        $this->assign('topColumnData', $this->generateTree($tmpTopColumnData, $tmpTopColumnData[0]));
        $this->assign('bottomColumnData', $bottomColumnData);
    }

    /**
     * 将数组数据转为树形结构
     * @param array $list 原数组数据
     * @param array $parent 顶级原数组数据
     * @return array
     */
    private function generateTree(array &$list, array $parent): array
    {
        $tree = [];
        foreach ($parent as $k => $v) {
            if (isset($list[$v['id']])) {
                $v['children'] = $this->generateTree($list, $list[$v['id']]);
            }
            $tree[] = $v;
        }
        return $tree;
    }

    /**
     * 网站首页
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 展示栏目类型为首页的数据
     */
    private function showColumnType1()
    {
        // 最新文章
        $article = new \application\home\model\Article();
        $newData = $article->field('id,title,thumbnail,description,target_blank')
            ->order('timing', 'desc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->limit(15)
            ->select();
        $this->assign('newData', $newData);
        // 随机文章
        $this->assign('randomData', $this->getRandomData());
        // 热门文章
        $this->assign('hotData', $this->getHotData());
        $this->display('web/index.html');
    }

    /**
     * 获取随机文章
     * @param string $field 查询的字段
     * @param int $num 查询的数量
     * @return array
     */
    private function getRandomData(string $field = 'id,title', int $num = 5): array
    {
        $article = new \application\home\model\Article();
        $maxId = $article->max('id');
        if ($maxId <= $num) {
            $rid = 0;
        } else {
            $rid = mt_rand(0, $maxId - $num);
        }
        return $article->field($field)
            ->order('timing', 'asc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->gt('id', $rid)
            ->limit($num)
            ->select();
    }

    /**
     * 获取热门文章
     * @param string $field 查询的字段
     * @param int $num 查询的数量
     * @return array
     */
    private function getHotData(string $field = 'id,title', int $num = 5): array
    {
        $article = new \application\home\model\Article();
        return $article->field($field)
            ->order('num', 'desc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->limit($num)
            ->select();
    }
}