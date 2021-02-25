<?php

namespace application\home\model;

use library\mysmarty\Model;

class LoginLog extends Model
{
    /**
     * 添加登录日志
     * @param int $adminId 管理员ID
     * @param int $status 登录状态：1 成功，2 失败
     * @return int
     */
    public function addLoginLog(int $adminId, int $status): int
    {
        return $this->add([
            'admin_id' => $adminId,
            'ip' => getIp(),
            'user_agent' => getUserAgent(),
            'status' => $status,
        ]);
    }
}