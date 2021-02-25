<?php

namespace application\home\controller;

use application\home\model\LoginLog;
use library\mysmarty\Captcha;
use library\mysmarty\Controller;
use library\mysmarty\Route;
use library\mysmarty\Session;

/**
 * 无需登录的控制器
 * @package application\home\controller
 */
class Index extends Controller
{
    // 关闭缓存
    protected bool $myCache = false;

    /**
     * 用户登录
     */
    #[Route('/login')]
    public function login()
    {
        if (isPost()) {
            $code = getPostString('code');
            if (empty($code)) {
                $this->error('验证码不能为空');
            }
            $name = getPostString('name');
            if (empty($name)) {
                $this->error('账号不能为空');
            }
            $password = getPostString('password');
            if (empty($password)) {
                $this->error('密码不能为空');
            }
            if (!Captcha::check($code)) {
                $this->error('验证码错误');
            }
            $loginLog = new LoginLog();
            // 判断账号当前60分钟内的失败次数
            $startTime = date('Y-m-d H:i:s', time() - 3600);
            if ($loginLog->eq('ip', getIp())
                    ->eq('status', 2)
                    ->egt('create_time', $startTime)
                    ->count() >= 3) {
                $this->error('登录失败');
            }
            $admin = (new \application\home\model\Admin())
                ->eq('name', $name)
                ->find();
            if (empty($admin)) {
                $this->error('账号或密码错误');
            }
            if (!password_verify($password, $admin['password'])) {
                $loginLog->addLoginLog($admin['id'], 2);
                $this->error('账号或密码错误');
            }
            if (1 !== (int)$admin['status']) {
                $loginLog->addLoginLog($admin['id'], 2);
                $this->error('账号已停用');
            }
            // 判断角色
            $authRule = new \application\home\model\AuthRule();
            if (0 === (int)$admin['auth_group_id']) {
                // 超级管理员
                $authRuleData = $authRule->order('pid asc,sort_num asc')
                    ->field('url')
                    ->notNull('url')
                    ->eq('status', 1)
                    ->find();
            } else {
                // 其他角色组
                $authGroup = new \application\home\model\AuthGroup();
                $authGroupData = $authGroup->field('rules')
                    ->eq('id', $admin['auth_group_id'])
                    ->eq('status', 1)
                    ->find();
                if (empty($authGroupData)) {
                    $loginLog->addLoginLog($admin['id'], 2);
                    $this->error('角色组已停用');
                }
                if (empty($authGroupData['rules'])) {
                    $loginLog->addLoginLog($admin['id'], 2);
                    $this->error('角色组未设置菜单规则');
                }
                $authRuleData = $authRule->order('pid asc,sort_num asc')
                    ->field('url')
                    ->notNull('url')
                    ->in('id', $authGroupData['rules'])
                    ->eq('status', 1)
                    ->find();
            }
            if (empty($authRuleData)) {
                $loginLog->addLoginLog($admin['id'], 2);
                $this->error('菜单规则为空');
            }
            $url = $authRuleData['url'];
            $loginLog->addLoginLog($admin['id'], 1);
            unset($admin['password']);
            setSession(config('app.smarty_admin_session', 'smartyAdmin'), $admin);
            redirect($url);
        }
        Session::getInstance()->clear();
        $this->display();
    }

    /**
     * 验证码
     */
    #[Route('/captcha')]
    public function code()
    {
        // 输出验证码图片
        Captcha::code()->output();
    }
}