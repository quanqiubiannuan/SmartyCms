<?php

namespace application\home\validate;

use library\mysmarty\Validate;

class Article extends Validate
{
    protected array $rule = [
        'column_id@栏目ID' => 'required|integer',
        'title@标题' => 'required',
        'content@内容' => 'required',
        'target_blank@新窗口打开' => 'required|integer|in:1,2',
        'status@状态' => 'required|integer|in:1,2'
    ];

    protected array $scene = [
        'add' => 'column_id,title,status,content,target_blank',
        'edit' => 'column_id,title,status,content,target_blank'
    ];
}
