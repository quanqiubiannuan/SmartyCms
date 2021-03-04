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
    // 可以查询的文章列表所在栏目ID
    private array $inColumnIds;
    // 可以查询的文章列表所在栏目数据
    private array $inColumnData;

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
        $inColumnData = [];
        foreach ($columnData as $v) {
            if (1 == $v['status']) {
                $topColumnData[] = $v;
            } else if (2 == $v['status']) {
                $bottomColumnData[] = $v;
            }
            if (2 == $v['type']) {
                $inColumnData[] = $v;
            }
        }
        $this->inColumnData = $inColumnData;
        $this->inColumnIds = array_column($inColumnData, 'id');
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
        if (empty($this->columnData)) {
            $this->error('未设置栏目');
        }
        $this->showColumn($this->columnData[0]);
    }

    /**
     * 根据单条栏目数据，显示不同的页面
     * @param array $columnData 本栏目数据
     */
    private function showColumn(array $columnData)
    {
        $this->assign('title', $columnData['name']);
        $this->assign('keywords', $columnData['keywords']);
        $this->assign('description', $columnData['description']);
        switch ($columnData['type']) {
            case 1:
                $this->assign('title', '');
                $this->showColumnType1();
                break;
            case 2:
                $this->showColumnType2($columnData['id'], $columnData['name']);
                break;
            case 3:
                $this->showColumnType3($columnData['id']);
                break;
            case 4:
                redirect($columnData['url']);
                break;
        }
    }

    /**
     * 展示栏目类型为首页的数据
     */
    private function showColumnType1()
    {
        // 最新文章
        $article = new \application\home\model\Article();
        $newData = $article->field('id,title,thumbnail,description,target_blank')
            ->order('id', 'desc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->in('column_id', $this->inColumnIds)
            ->limit(15)
            ->select();
        $this->assign('newData', $newData);
        // 随机文章
        $this->assign('randomData', $this->getRandomData());
        // 热门文章
        $this->assign('hotData', $this->getHotData());
        // 友情链接
        $link = new \application\home\model\Link();
        $linkData = $link->field('url,title,nofollow')
            ->eq('is_show', 'y')
            ->select();
        $this->assign('linkData', $linkData);
        $this->display('web/index.html');
    }

    /**
     * 展示栏目类型为数据列表的数据
     * @param int $columnId 栏目ID
     * @param string $columnName 栏目名称
     */
    private function showColumnType2(int $columnId, string $columnName)
    {
        $columnIds = [$columnId];
        foreach ($this->inColumnData as $c) {
            if (in_array($c['pid'], $columnIds)) {
                $columnIds[] = $c['id'];
            }
        }
        // 最新文章
        $article = new \application\home\model\Article();
        $newData = $article->field('id,title,thumbnail,description,target_blank')
            ->order('id', 'desc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->in('column_id', $columnIds)
            ->limit(15)
            ->select();
        $this->assign('newData', $newData);
        // 随机文章
        $this->assign('randomData', $this->getRandomData());
        // 热门文章
        $this->assign('hotData', $this->getHotData());
        $this->assign('columnName', $columnName);
        $this->display('web/column.html');
    }


    /**
     * 展示栏目类型为单页面的数据
     * @param int $columnId 栏目ID
     */
    private function showColumnType3(int $columnId)
    {
        $article = new \application\home\model\Article();
        $data = $article->field('id,title,content,keywords,description')
            ->eq('status', 1)
            ->eq('column_id', $columnId)
            ->find();
        if (!empty($data)) {
            $this->assign('title', $data['title']);
            $this->assign('id', $data['id']);
            $this->assign('content', $data['content']);
            $this->assign('keywords', $data['keywords']);
            $this->assign('description', $data['description']);
        } else {
            $this->assign('content', '');
        }
        $this->display('web/single.html');
    }

    /**
     * 获取随机文章
     * @param string $field 查询的字段
     * @param int $num 查询的数量
     * @return array
     */
    private function getRandomData(string $field = 'id,title,target_blank', int $num = 5): array
    {
        $article = new \application\home\model\Article();
        $maxId = $article->max('id');
        if ($maxId <= $num) {
            $rid = 0;
        } else {
            $rid = mt_rand(0, $maxId - $num);
        }
        return $article->field($field)
            ->order('id', 'asc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->gt('id', $rid)
            ->in('column_id', $this->inColumnIds)
            ->limit($num)
            ->select();
    }

    /**
     * 获取热门文章
     * @param string $field 查询的字段
     * @param int $num 查询的数量
     * @return array
     */
    private function getHotData(string $field = 'id,title,target_blank', int $num = 5): array
    {
        $article = new \application\home\model\Article();
        return $article->field($field)
            ->order('num', 'desc')
            ->elt('timing', time())
            ->eq('status', 1)
            ->in('column_id', $this->inColumnIds)
            ->limit($num)
            ->select();
    }

    /**
     * 显示栏目数据
     * @param int $id 栏目ID
     */
    #[Route('/column/{id}.html', pattern: [
        'id' => '[0-9]+'
    ])]
    public function column(int $id)
    {
        foreach ($this->columnData as $v) {
            if ($v['id'] == $id) {
                $this->showColumn($v);
                break;
            }
        }
        $this->error('栏目不存在');
    }

    /**
     * 显示文章数据
     * @param int $id 文章ID
     */
    #[Route('/article/{id}.html', pattern: [
        'id' => '[0-9]+'
    ])]
    public function article(int $id)
    {
        $article = new \application\home\model\Article();
        $data = $article->field('article.id,article.column_id,article.title,article.content,article.keywords,article.description,article.num,article.create_time,column.name')
            ->eq('article.id', $id)
            ->eq('article.status', 1)
            ->elt('article.timing', time())
            ->leftJoin('column', 'column.id=article.column_id')
            ->find();
        if (empty($data)) {
            $this->error('文章不存在');
        }
        $this->assign('data', $data);
        $this->assign('title', $data['title']);
        $this->assign('keywords', $data['keywords']);
        $this->assign('description', $data['description']);
        // 随机文章
        $this->assign('randomData', $this->getRandomData());
        // 热门文章
        $this->assign('hotData', $this->getHotData());
        $this->display('web/article.html');
    }
}