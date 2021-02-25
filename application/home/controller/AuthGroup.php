<?php

namespace application\home\controller;

use library\mysmarty\Route;

#[Route('/auth_group')]
class AuthGroup extends BackendCurd
{
    protected int $dataType = 3;

    /**
     * 首页查询
     */
    public function index()
    {
        $list = $this->dealLevelData($this->getAllAuthGroup('*', [1, 2]), $this->smartyAdmin['auth_group_id']);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        $authGroups = $this->getLevelAuthGroup();
        $authRules = $this->dealLevelData($this->getAuthRuleData('id,name,pid', [1]), init: true);
        if (isPost()) {
            $data = $_POST;
            $validate = new \application\home\validate\AuthGroup();
            if ($validate->scene('add')->check($data) === false) {
                $this->error($validate->getError());
            }
            if (!$this->isSuperAdmin && !in_array($data['id'], array_column($authGroups, 'id'))) {
                $this->error('您没有权限设置此角色组');
            }
            if (!empty(array_diff($data['rules'], array_column($authRules, 'id')))) {
                $this->error('您没有权限设置此规则');
            }
            $data['rules'] = implode(',', $data['rules']);
            $authGroup = new \application\home\model\AuthGroup();
            $num = $authGroup->allowField(true)->add($data);
            if ($num > 0) {
                $this->success('添加成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('添加失败');
        }
        $this->assign('authGroups', $authGroups);
        $this->assign('authRules', $authRules);
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
        $authGroup = new \application\home\model\AuthGroup();
        $data = $authGroup->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        $authGroups = $this->getLevelAuthGroup();
        if (!$this->isSuperAdmin && !in_array($data['id'], array_column($authGroups, 'id'))) {
            $this->error('您没有权限编辑此角色组');
        }
        $authRules = $this->dealLevelData($this->getAuthRuleData('id,name,pid', [1]), init: true);
        if (isPost()) {
            $data = $_POST;
            $validate = new \application\home\validate\AuthGroup();
            if ($validate->scene('edit')->check($data) === false) {
                $this->error($validate->getError());
            }
            if (!empty(array_diff($data['rules'], array_column($authRules, 'id')))) {
                $this->error('您没有权限设置此规则');
            }
            $data['rules'] = implode(',', $data['rules']);
            $num = $authGroup->eq('id', $id)
                ->update($data);
            if ($num > 0) {
                $this->success('编辑成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('编辑失败');
        }
        if (!empty($data['rules'])){
            $data['rules'] = explode(',', $data['rules']);
        } else {
            $data['rules'] = [];
        }
        $this->assign('data', $data);
        $this->assign('authGroups', $authGroups);
        $this->assign('authRules', $authRules);
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
        $authGroup = new \application\home\model\AuthGroup();
        $data = $authGroup->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        $authGroups = $this->getLevelAuthGroup();
        if (!$this->isSuperAdmin && !in_array($data['id'], array_column($authGroups, 'id'))) {
            $this->error('您没有权限删除此角色');
        }
        // 有下级角色的不能删除
        if ($authGroup->eq('pid', $id)->find()) {
            $this->error('无法删除父级角色');
        }
        // 查询此角色组是否关联用户
        $admin = new \application\home\model\Admin();
        if ($admin->eq('auth_group_id', $id)->find()) {
            $this->error('无法删除已关联用户的角色');
        }
        $num = $authGroup->eq('id', $id)
            ->delete();
        if ($num > 0) {
            $this->success('删除成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
        }
        $this->error('删除失败');
    }
}