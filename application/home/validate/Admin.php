<?php

namespace application\home\validate;

use library\mysmarty\Validate;

class Admin extends Validate
{
    protected array $rule = [
        'auth_group_id@角色组' => 'required|integer|gt:0',
        'name@用户名' => 'required',
        'password@密码' => 'required|length:6,20|alphaNum',
        'gender@性别' => 'required|integer|in:1,2',
        'status@状态' => 'required|integer|in:1,2',
    ];

    protected array $scene = [
        'add' => 'name,password,gender,auth_group_id',
        'edit' => 'name,gender,auth_group_id,status'
    ];
}
