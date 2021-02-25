<?php

namespace application\home\controller;

use application\home\model\AuthGroup;
use application\home\model\AuthRule;
use library\mysmarty\Controller;

/**
 * 后台基础控制器
 * @package application\home\controller
 */
class Backend extends Controller
{
    // 关闭缓存
    protected bool $myCache = false;
    // 当前登录用户信息
    protected array $smartyAdmin = [];
    // 菜单组
    protected array $smartyMenu = [];
    // 分组
    protected array $authGroup = [];
    // 菜单规则
    protected array $authRule = [];
    // 是否为超级管理员
    protected bool $isSuperAdmin = false;
    // 当前访问路径
    protected string $currentPath = '';
    // 当前访问的菜单
    protected array $currentMenu = [];
    // 当前页面是否为菜单页面
    protected bool $isMenuPage = false;

    public function __construct()
    {
        parent::__construct();
        // 未登录跳转到登录页面
        $smartyAdmin = getSession(config('app.smarty_admin_session', 'smartyAdmin'));
        if (empty($smartyAdmin)) {
            redirect('/login');
        }
        // 验证session用户是否正确
        $admin = new \application\home\model\Admin();
        $smartyAdmin = $admin->eq('id', $smartyAdmin['id'])
            ->eq('status', 1)
            ->find();
        if (empty($smartyAdmin)) {
            redirect('/login');
        }
        unset($smartyAdmin['password']);
        // 初始化当前登录用户信息
        $this->smartyAdmin = $smartyAdmin;
        if (0 === (int)$this->smartyAdmin['auth_group_id']) {
            $this->isSuperAdmin = true;
        }
        setSession('isSuperAdmin', $this->isSuperAdmin);
        // 初始化当前用户组
        if (!$this->isSuperAdmin) {
            $authGroup = new AuthGroup();
            $this->authGroup = $authGroup->eq('id', $this->smartyAdmin['auth_group_id'])
                ->eq('status', 1)
                ->find();
        }
        // 当前访问路径
        $currentPath = getPath();
        if (empty($currentPath)) {
            $currentPath = ROUTE['home']['uri'];
        }
        $this->currentPath = $currentPath;
        // 初始化菜单
        $this->smartyMenu = $this->getSmartyMenu();
        if (empty($this->smartyMenu)) {
            $this->error('您无权访问此页面');
        }
        // 判断用户是否有权限
        if (!$this->isSuperAdmin) {
            $authRuleUrlData = array_column($this->authRule, 'url');
            if (!in_array($this->currentPath, $authRuleUrlData, true)) {
                $this->error('您无权访问此页面');
            }
            setSession('authRuleUrlData', $authRuleUrlData);
        }
        // 分配相关变量到页面
        $this->assign('smartyMenu', $this->smartyMenu);
        $this->assign('currentMenu', $this->currentMenu);
        $this->assign('smartyAdmin', $this->smartyAdmin);
        $this->assign('isMenuPage', $this->isMenuPage);
    }

    /**
     * 获取当前用户的菜单规则
     * @param string $field 要查询的字段
     * @param array $status 要查询的状态
     * @param string $order 排序规则
     * @return array
     */
    protected function getAuthRuleData(string $field, array $status, string $order = 'pid asc,sort_num asc'): array
    {
        $authRule = new AuthRule();
        if ($this->isSuperAdmin) {
            // 超级管理员
            $authRuleData = $authRule->field($field)
                ->order($order)
                ->in('status', $status)
                ->select();
        } else {
            if (!empty($this->authGroup)) {
                // 分组用户
                $authRuleData = $authRule->in('id', $this->authGroup['rules'])
                    ->field($field)
                    ->order($order)
                    ->in('status', $status)
                    ->select();
            } else {
                // 其它用户
                return [];
            }
        }
        return $authRuleData;
    }

    /**
     * 获取当前用户所有的权限菜单规则
     * @return array
     */
    private function getSmartyMenu(): array
    {
        $smartyMenu = [];
        $authRuleData = $this->getAuthRuleData('id,url,name,icon,pid,is_menu', [1]);
        if (!empty($authRuleData)) {
            $this->authRule = $authRuleData;
            $newAuthRuleData = [];
            $pid = 0;
            foreach ($authRuleData as $v) {
                if (1 === (int)$v['is_menu']) {
                    // 菜单规则
                    if ($this->currentPath === $v['url']) {
                        $this->currentMenu = $v;
                        $this->isMenuPage = true;
                        setSession('lastCurrentMenu', $v);
                    }
                    $newAuthRuleData[$v['pid']][] = $v;
                } else {
                    if ($this->currentPath === $v['url']) {
                        $pid = $v['pid'];
                    }
                }
            }
            if (empty($this->currentMenu)) {
                // 当前菜单为空，非菜单规则
                $currentMenu = $this->getCurrentMenuByPath($authRuleData, $pid);
                $this->currentMenu = $currentMenu ?: $authRuleData[0];
            }
            $smartyMenu = $this->generateTree($newAuthRuleData, $newAuthRuleData[0]);
        }
        return $smartyMenu;
    }

    /**
     * 如果获取不到当前菜单，根据path获取上级菜单
     * @param array $authRuleData 菜单规则
     * @param int $pid 当前规则的pid
     * @return array
     */
    private function getCurrentMenuByPath(array $authRuleData, int $pid): array
    {
        foreach ($authRuleData as $v) {
            if ($pid == $v['id']) {
                if ($v['is_menu']) {
                    return $v;
                } else {
                    return $this->getCurrentMenuByPath($authRuleData, $v['pid']);
                }
            }
        }
        return getSession('lastCurrentMenu') ?: [];
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
                $v['expanded'] = false;
                if (!empty($this->currentMenu['pid']) && ($this->currentMenu['pid'] === $v['id'])) {
                    $v['expanded'] = true;
                }
                $v['children'] = $this->generateTree($list, $list[$v['id']]);
            }
            $tree[] = $v;
        }
        return $tree;
    }

    /**
     * 获取当前角色下的所有角色，不包括当前角色
     * @param string $field 需要查询的字段
     * @param array $status 取哪些状态的角色组
     * @return array
     */
    protected function getAllAuthGroup(string $field = 'id,name,pid', array $status = [1]): array
    {
        $authGroup = new AuthGroup();
        $authGroupData = $authGroup->field($field)
            ->in('status', $status)
            ->order('pid asc,id asc')
            ->select();
        if ($this->isSuperAdmin) {
            return $authGroupData;
        }
        $authGroupIds = [];
        $data = [];
        foreach ($authGroupData as $v) {
            if ($this->smartyAdmin['auth_group_id'] === $v['pid'] || in_array($v['pid'], $authGroupIds)) {
                $data[] = $v;
                $authGroupIds[] = $v['id'];
            }
        }
        return $data;
    }

    /**
     * 返回具有层级的权限数组（获取当前角色下的所有角色，不包括当前角色）
     * @return array
     */
    protected function getLevelAuthGroup(): array
    {
        return $this->dealLevelData($this->getAllAuthGroup(), $this->smartyAdmin['auth_group_id']);
    }

    /**
     * 将数据转为层级结构
     * @param array $data 原始数组数据
     * @param int $pid 第一个父级id
     * @param int $level 级别
     * @param bool $init 是否初始化数据
     * @return array
     */
    protected function dealLevelData(array $data, int $pid = 0, int $level = 0, bool $init = false): array
    {
        static $tree = [];
        if ($init && $level === 0) {
            $tree = [];
        }
        foreach ($data as $v) {
            if ($pid === (int)$v['pid']) {
                $v['level'] = $level + 1;
                $tree[] = $v;
                $this->dealLevelData($data, $v['id'], $v['level']);
            }
        }
        return $tree;
    }

    /**
     * 获取当前角色下的所有用户id（不包括当前角色，包括当前角色下的角色，包括自己的id）
     * @return array
     */
    protected function getAdminIds(): array
    {
        $adminIds = [$this->smartyAdmin['id']];
        $authGroupData = $this->getAllAuthGroup();
        $authGroupIds = array_column($authGroupData, 'id');
        if (!empty($authGroupIds)) {
            $admin = new \application\home\model\Admin();
            $result = $admin->in('auth_group_id', $authGroupIds)
                ->field('id')
                ->select();
            if (!empty($result)) {
                $adminIds = array_merge($adminIds, array_column($result, 'id'));
            }
        }
        return $adminIds;
    }
}