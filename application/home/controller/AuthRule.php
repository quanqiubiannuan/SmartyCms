<?php

namespace application\home\controller;

use library\mysmarty\Route;

#[Route('/auth_rule')]
class AuthRule extends BackendCurd
{
    protected int $dataType = 3;

    /**
     * 首页查询
     */
    public function index()
    {
        $list = $this->dealLevelData($this->getAuthRuleData('*', [1, 2]));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        $authRules = $this->dealLevelData($this->getAuthRuleData('id,name,pid,is_menu,sort_num', [1]));
        if (isPost()) {
            $data = $_POST;
            $validate = new \application\home\validate\AuthRule();
            if ($validate->scene('add')->check($data) === false) {
                $this->error($validate->getError());
            }
            if (!$this->isSuperAdmin && !in_array($data['pid'], array_column($authRules, 'id'))) {
                $this->error('您没有权限设置此规则');
            }
            if (empty($data['url'])) {
                $data['url'] = null;
            } else {
                if (!in_array($data['url'], array_column(ROUTE, 'uri'))) {
                    $this->error('链接不存在');
                }
            }
            $authRule = new \application\home\model\AuthRule();
            $num = $authRule->allowField(true)->add($data);
            if ($num > 0) {
                $this->success('添加成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('添加失败');
        }
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
        $authRule = new \application\home\model\AuthRule();
        $data = $authRule->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        $authRules = $this->dealLevelData($this->getAuthRuleData('id,name,pid,is_menu,sort_num', [1]));
        if (!$this->isSuperAdmin && !in_array($data['pid'], array_column($authRules, 'id'))) {
            $this->error('您没有权限编辑此规则');
        }
        if (isPost()) {
            $data = $_POST;
            $validate = new \application\home\validate\AuthRule();
            if ($validate->scene('edit')->check($data) === false) {
                $this->error($validate->getError());
            }
            if (empty($data['url'])) {
                $data['url'] = null;
            } else {
                if (!in_array($data['url'], array_column(ROUTE, 'uri'))) {
                    $this->error('链接不存在');
                }
            }
            $num = $authRule->eq('id', $id)
                ->update($data);
            if ($num > 0) {
                $this->success('编辑成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('编辑失败');
        }
        $this->assign('data', $data);
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
        $authRule = new \application\home\model\AuthRule();
        $data = $authRule->eq('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        $authRules = $this->dealLevelData($this->getAuthRuleData('id,name,pid,is_menu,sort_num', [1]));
        if (!$this->isSuperAdmin && !in_array($data['pid'], array_column($authRules, 'id'))) {
            $this->error('您没有权限删除此规则');
        }
        // 有下级规则的不能删除
        if ($authRule->eq('pid', $id)->find()) {
            $this->error('无法删除父级规则');
        }
        // 查询此规则是否关联角色
        $authGroup = new \application\home\model\AuthGroup();
        if ($authGroup->where('FIND_IN_SET(' . $id . ',rules)')->find()) {
            $this->error('无法删除已关联角色的规则');
        }
        $num = $authRule->eq('id', $id)
            ->delete();
        if ($num > 0) {
            $this->success('删除成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
        }
        $this->error('删除失败');
    }
}