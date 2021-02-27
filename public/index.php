<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/library/mysmarty/Start.php';
//定义默认模块
define('MODULE', 'home');
//定义默认控制器
define('CONTROLLER', 'Web');
//定义默认方法
define('ACTION', 'index');

use library\mysmarty\Start;

Start::forward();