<?php

namespace application\home\model;

use library\mysmarty\Model;

class Column extends Model
{
    /**
     * 判断当前是否设置了首页栏目
     * @param int $id
     * @return bool
     */
    public function isSetHomeColumn(int $id = 0): bool
    {
        return (bool)$this->eq('type', 1)
            ->neq('id', $id)
            ->find();
    }
}