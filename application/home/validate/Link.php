<?php

namespace application\home\validate;

use library\mysmarty\Validate;

class Link extends Validate
{
    protected array $rule = [
        'url@网址' => 'required|url',
        'title@网站名称' => 'required',
        'nofollow' => 'required|in:y,n',
        'is_show@显示' => 'required|in:y,n',
    ];

    protected array $scene = [
        'add' => 'url,title,nofollow,is_show',
        'edit' => 'url,title,nofollow,is_show'
    ];
}
