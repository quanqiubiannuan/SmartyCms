<?php

namespace application\home\validate;

use library\mysmarty\Validate;

class AuthRule extends Validate
{
    protected array $rule = [
        'name@规则名称' => 'required',
        'is_menu@C菜单' => 'required|integer|in:1,2',
        'sort_num@排序' => 'required|integer',
        'status@状态' => 'required|integer|in:1,2',
    ];

    protected array $scene = [
        'add' => 'name,is_menu,sort_num',
        'edit' => 'name,is_menu,sort_num,status'
    ];
}
