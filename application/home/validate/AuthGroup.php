<?php

namespace application\home\validate;

use library\mysmarty\Validate;

class AuthGroup extends Validate
{
    protected array $rule = [
        'name@角色名' => 'required',
        'status@状态' => 'required|integer|in:1,2',
        'rules@菜单规则' => 'required|array',
    ];

    protected array $scene = [
        'add' => 'name,rules',
        'edit' => 'name,status,rules'
    ];
}
