<?php

namespace application\home\validate;

use library\mysmarty\Validate;

class Column extends Validate
{
    protected array $rule = [
        'name@栏目名称' => 'required',
        'type@栏目类型' => 'required|integer|in:1,2,3,4',
        'status@状态' => 'required|integer|in:1,2,3'
    ];

    protected array $scene = [
        'add' => 'name,type,status',
        'edit' => 'name,type,status'
    ];
}
