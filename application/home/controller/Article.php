<?php

namespace application\home\controller;

use library\mysmarty\Image;
use library\mysmarty\Route;
use library\mysmarty\Upload;

#[Route('/article')]
class Article extends BackendCurd
{
    protected array $searchCondition = ['id/s' => '=', 'column_id/s' => '=', 'status/s' => '='];
    protected string $field = 'article.*,column.name,column.type';
    protected array $joinCondition = ['column', 'column.id=article.column_id'];
    protected int $dataType = 3;
    protected string $table = 'article';
    protected bool $allowDeleteMethod = true;
    protected array $columnData = [];

    public function __construct()
    {
        parent::__construct();
        $column = new \application\home\model\Column();
        $columnData = $column->order('pid asc,id asc')
            ->field('id,name,pid,type,status')
            ->notIn('type', [1, 4])
            ->select();
        $this->columnData = $this->dealLevelData($columnData);
        $this->assign('columnList', $this->columnData);
    }

    /**
     * 添加
     */
    public function add()
    {
        $columnData = $this->getColumnData();
        if (isPost()) {
            $data = $_POST;
            if (empty($data['content'])) {
                $this->error('内容不能为空');
            }
            $validate = new \application\home\validate\Article();
            if ($validate->scene('add')->check($data) === false) {
                $this->error($validate->getError());
            }
            $data['admin_id'] = $this->smartyAdmin['id'];
            if (!empty($data['timing'])) {
                $data['timing'] = (int)strtotime($data['timing']);
            } else {
                $data['timing'] = 0;
            }
            if (!empty($data['keywords'])) {
                $data['keywords'] = str_ireplace('，', ',', $data['keywords']);
            } else {
                $data['keywords'] = htmlspecialchars($data['title']);
            }
            if (empty($data['description'])) {
                $data['description'] = getDescriptionforArticle($data['content'], 120);
            }
            $thumbnail = Upload::getInstance()->move('thumbnail');
            if (!empty($thumbnail)) {
                // 缩放缩略图
                if (empty(Image::getInstance(PUBLIC_DIR . $thumbnail)->zoom(64, 64, PUBLIC_DIR . $thumbnail))) {
                    $this->error('缩略图缩放失败');
                }
                $data['thumbnail'] = $thumbnail;
            }
            $article = new \application\home\model\Article();
            $num = $article->allowField(true)->add($data);
            if ($num > 0) {
                $this->success('添加成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('添加失败');
        }
        $this->assign('columnData', $columnData);
        $this->display();
    }

    /**
     * 获取文章栏目数据
     * @return array
     */
    private function getColumnData(): array
    {
        $columnData = $this->columnData;
        // 处理栏目数据
        $article = new \application\home\model\Article();
        $whereMap = [];
        $id = getInt('id');
        if ($id > 0) {
            $whereMap['article.id'] = [$id, '!='];
        }
        foreach ($columnData as $k => $v) {
            // 去掉隐藏的栏目
            if ((int)$v['status'] === 3) {
                unset($columnData[$k]);
                continue;
            }
            switch ($v['type']) {
                case 1:
                case 3:
                    $articleData = $article->field('article.id')
                        ->whereMap($whereMap)
                        ->leftJoin('column', 'column.id=article.column_id')
                        ->eq('column.type', $v['type'])
                        ->eq('column.id', $v['id'])
                        ->find();
                    if (!empty($articleData)) {
                        unset($columnData[$k]);
                    }
                    break;
            }
        }
        return $columnData;
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = getInt('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $article = new \application\home\model\Article();
        $data = $article->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        $columnData = $this->getColumnData();
        if (isPost()) {
            $data = $_POST;
            if (empty($data['content'])) {
                $this->error('内容不能为空');
            }
            $validate = new \application\home\validate\Article();
            if ($validate->scene('edit')->check($data) === false) {
                $this->error($validate->getError());
            }
            $data['admin_id'] = $this->smartyAdmin['id'];
            if (!empty($data['timing'])) {
                $timing = strtotime($data['timing']);
                if (empty($timing) || $timing < time()) {
                    $data['timing'] = time();
                } else {
                    $data['timing'] = $timing;
                }
            } else {
                $data['timing'] = time();
            }
            if (!empty($data['keywords'])) {
                $data['keywords'] = str_ireplace('，', ',', $data['keywords']);
            } else {
                $data['keywords'] = htmlspecialchars($data['title']);
            }
            if (empty($data['description'])) {
                $data['description'] = getDescriptionforArticle($data['content'], 120);
            }
            $thumbnail = Upload::getInstance()->move('thumbnail');
            if (!empty($thumbnail)) {
                // 缩放缩略图
                if (empty(Image::getInstance(PUBLIC_DIR . $thumbnail)->zoom(64, 64, PUBLIC_DIR . $thumbnail))) {
                    $this->error('缩略图缩放失败');
                }
                $data['thumbnail'] = $thumbnail;
            }
            $num = $article->eq('id', $id)
                ->update($data);
            if ($num > 0) {
                $this->success('编辑成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('编辑失败');
        }
        if ($data['timing'] > 0) {
            $data['timing'] = date('Y-m-d\TH:i', $data['timing']);
        } else {
            $data['timing'] = '';
        }
        $this->assign('data', $data);
        $this->assign('columnData', $columnData);
        $this->display();
    }
}