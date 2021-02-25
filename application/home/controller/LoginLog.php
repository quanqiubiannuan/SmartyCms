<?php

namespace application\home\controller;

use library\mysmarty\Route;

#[Route('/log')]
class LoginLog extends BackendCurd
{
    protected array $joinCondition = ['admin', 'admin.id=login_log.admin_id'];
    protected int $dataType = 3;
    protected string $field = 'login_log.*,admin.name as admin_name';
}