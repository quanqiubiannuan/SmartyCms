<?php

namespace application\home\controller;

use library\mysmarty\Controller;
use library\mysmarty\Route;

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
            ->field('id,uri,pid,name,type,target_blank,keywords,description,status')
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
     * @param string $uri 唯一访问路径
     */
    #[Route('/{uri}', pattern: [
        'uri' => '[0-9a-z.\-_]+'
    ], level: Route::LOW)]
    public function index(string $uri = '')
    {
        $this->display();
    }
}