<?php

namespace application\home\controller;

use library\mysmarty\Route;
use library\mysmarty\Upload;

#[Route('/article')]
class Article extends BackendCurd
{
    protected string $field = 'article.*,column.name';
    protected array $joinCondition = ['column', 'column.id=article.column_id'];
    protected int $dataType = 3;
    protected string $table = 'article';

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
                $timing = strtotime($data['timing']);
                if (empty($timing) || $timing < time()) {
                    $data['timing'] = time();
                } else {
                    $data['timing'] = $timing;
                }
            } else {
                $data['timing'] = time();
            }
            if (empty($data['uri'])) {
                $data['uri'] = getUri();
            }
            if (!empty($data['keywords'])) {
                $data['keywords'] = str_ireplace('，', ',', $data['keywords']);
            }
            $thumbnail = Upload::getInstance()->move('thumbnail');
            if (!empty($thumbnail)) {
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
        $column = new \application\home\model\Column();
        $columnData = $column->field('id,name,type,pid')
            ->neq('status', 3)
            ->neq('type', 4)
            ->order('pid asc,id asc')
            ->select();
        $columnData = $this->dealLevelData($columnData);
        // 处理栏目数据
        $article = new \application\home\model\Article();
        foreach ($columnData as $k => $v) {
            switch ($v['type']) {
                case 1:
                case 3:
                    $articleData = $article->field('article.id')
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

    }

    /**
     * 删除
     */
    public function delete()
    {

    }
}