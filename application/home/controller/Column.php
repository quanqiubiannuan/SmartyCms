<?php

namespace application\home\controller;

use application\home\model\Article;
use library\mysmarty\Route;

#[Route('/column')]
class Column extends Backend
{
    /**
     * 栏目列表
     */
    public function index()
    {
        $column = new \application\home\model\Column();
        $columnData = $column->order('pid asc,id asc')
            ->select();
        $list = $this->dealLevelData($columnData);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        $column = new \application\home\model\Column();
        $columnData = $column->field('id,name')
            ->eq('pid', 0)
            ->eq('status', 1)
            ->select();
        $setHome = $column->isSetHomeColumn();
        if (isPost()) {
            $data = $_POST;
            $validate = new \application\home\validate\Column();
            if ($validate->scene('add')->check($data) === false) {
                $this->error($validate->getError());
            }
            $this->checkData($data, $setHome);
            $data['admin_id'] = $this->smartyAdmin['id'];
            if (!empty($data['keywords'])) {
                $data['keywords'] = str_ireplace('，', ',', $data['keywords']);
            }
            $num = $column->allowField(true)->add($data);
            if ($num > 0) {
                $this->success('添加成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('添加失败');
        }
        $this->assign('columnData', $columnData);
        $this->assign('setHome', $setHome);
        $this->display();
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
        $column = new \application\home\model\Column();
        $data = $column->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        $setHome = $column->isSetHomeColumn($id);
        $columnData = $column->field('id,name')
            ->eq('pid', 0)
            ->eq('status', 1)
            ->select();
        if (isPost()) {
            $data = $_POST;
            $validate = new \application\home\validate\Column();
            if ($validate->scene('edit')->check($data) === false) {
                $this->error($validate->getError());
            }
            $this->checkData($data, $setHome);
            $data['admin_id'] = $this->smartyAdmin['id'];
            if (!empty($data['keywords'])) {
                $data['keywords'] = str_ireplace('，', ',', $data['keywords']);
            }
            $num = $column->eq('id', $id)
                ->update($data);
            if ($num > 0) {
                $this->success('编辑成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('编辑失败');
        }
        $this->assign('data', $data);
        $this->assign('setHome', $setHome);
        $this->assign('columnData', $columnData);
        $this->display();
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = getInt('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $column = new \application\home\model\Column();
        $data = $column->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        // 查询此角色组是否关联用户
        $article = new Article();
        if ($article->eq('column_id', $id)->find()) {
            $this->error('无法删除已关联文章的栏目');
        }
        $num = $column->eq('id', $id)
            ->delete();
        if ($num > 0) {
            $this->success('删除成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
        }
        $this->error('删除失败');
    }

    /**
     * 检查提交的数据是否有问题
     * @param array $data 表单数据
     * @param bool $setHome 是否设置了首页
     */
    private function checkData(array &$data, bool $setHome)
    {
        switch ($data['type']) {
            case 1:
                if ($setHome) {
                    $this->error('首页栏目类型已设置');
                }
                $data['uri'] = '';
                break;
            case 2:
            case 3:
                if (empty($data['uri'])) {
                    $data['uri'] = getUri();
                }
                break;
            case 4:
                if (!isUrl($data['uri'])) {
                    $this->error('访问路径错误');
                }
                break;
        }
    }
}