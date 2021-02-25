<?php

namespace application\home\controller;

use library\mysmarty\Route;

#[Route('/link')]
class Link extends BackendCurd
{
    protected bool $allowAddMethod = true;
    protected bool $allowEditMethod = true;
    protected bool $allowDeleteMethod = true;
    protected string $validate = \application\home\validate\Link::class;
}