<?php
/**
 * SmartyCms内容管理系统安装向导
 */
define('ROOT_DIR', dirname(__DIR__));
$installLockFile = ROOT_DIR . '/public/install.lock';
if (file_exists($installLockFile)) {
    exit('您已经安装了SmartyCms内容管理系统，如需重新安装，请删除本文件所在目录下的install.lock文件！');
}
// 检查PHP版本
if (version_compare(PHP_VERSION, '8.0.0') < 0) {
    exit('SmartyCms内容管理系统需要PHP版本 >= 8.0.0，您安装的PHP版本为：' . PHP_VERSION);
}
// 检查扩展
if (!extension_loaded('PDO') || !extension_loaded('pdo_mysql')) {
    exit('未安装PDO扩展库，Mysql无法连接！');
}
if (!extension_loaded('mbstring')) {
    exit('未安装mbstring扩展库，中文字符串无法截取！');
}
if (!extension_loaded('curl')) {
    exit('未安装curl扩展库，无法使用PHP请求外部链接！');
}
if (!extension_loaded('openssl')) {
    exit('未安装openssl扩展库，无法对数据加密！');
}
if (!extension_loaded('gd')) {
    exit('未安装GD扩展库，图片验证码无法使用！');
}
// 检查runtime目录是否可读可写
$runtimeDir = ROOT_DIR . '/runtime/';
$testFile = $runtimeDir . '/test.txt';
if (!is_dir($runtimeDir)) {
    $result = mkdir($runtimeDir, 0777, true);
    if (!$result) {
        exit('创建runtime文件夹（' . $runtimeDir . '）失败，请给此文件夹可读可写权限！');
    }
}
$testData = mt_rand(1000, 9999);
if (false === file_put_contents($testFile, $testData)) {
    exit('runtime文件夹（' . $runtimeDir . '）无法写入文件，请给此文件夹可读可写权限！');
}
if (file_get_contents($testFile) != $testData) {
    exit('runtime文件夹（' . $runtimeDir . '）无法读取文件，请给此文件夹可读可写权限！');
}
if (!unlink($testFile)) {
    exit('runtime文件夹（' . $runtimeDir . '）无法删除文件，请给此文件夹可读可写权限！');
}
// 开始安装
// 1 未安装，2 安装成功
$installResult = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqlHost = $_POST['mysql_host'];
    $mysqlUser = $_POST['mysql_user'];
    $mysqlPassword = $_POST['mysql_password'];
    $mysqlPort = $_POST['mysql_port'];
    $mysqlDatabase = $_POST['mysql_database'];
    $adminName = $_POST['admin_name'];
    $adminPassword = trim($_POST['admin_password']);

    $len = mb_strlen($adminPassword, 'utf-8');
    if (preg_match('/[^a-z0-9]/i', $adminPassword) || $len < 6 || $len > 20) {
        exit('管理员密码由6-20位字母或数字组成');
    }

    // 添加数据库数据
    $dsn = 'mysql:host=' . $mysqlHost . ';port=' . $mysqlPort . ';charset=utf8mb4';
    try {
        $dbh = new PDO($dsn, $mysqlUser, $mysqlPassword);
        $insertSql = <<<SQL
CREATE DATABASE IF NOT EXISTS `{$mysqlDatabase}`;
USE `{$mysqlDatabase}`;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '用户名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `gender` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '性别：0 未知，1 男，2 女',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `auth_group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属组',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 正常，2 停用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `column_id` int(10) unsigned NOT NULL COMMENT '栏目ID',
  `title` varchar(255) NOT NULL COMMENT '标题',
  `thumbnail` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `content` text NOT NULL COMMENT '内容',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描叙',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员id',
  `target_blank` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '新窗口打开：1 否，2 是',
  `num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章阅读量',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 显示，2 隐藏',
  `timing` bigint(20) unsigned NOT NULL COMMENT '定时发布文章',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='文章表';


CREATE TABLE IF NOT EXISTS `auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '组名',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级角色',
  `rules` tinytext NOT NULL COMMENT '菜单规则id，多个逗号分隔',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 正常，2 停用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='规则组表';

CREATE TABLE IF NOT EXISTS `auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL COMMENT '链接',
  `name` varchar(255) NOT NULL COMMENT '规则名',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '规则图标',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级规则',
  `is_menu` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否为菜单：1 是，2 不是',
  `sort_num` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '排序，越小则越靠前',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 正常，2 停用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COMMENT='规则表';


INSERT INTO `auth_rule` (`id`, `url`, `name`, `icon`, `pid`, `is_menu`, `sort_num`, `status`, `create_time`, `update_time`) VALUES
	(1, 'admin/home', '控制台', 'fas fa-tachometer-alt', 0, 1, 1, 1, '2021-01-19 10:44:09', '2021-01-19 10:47:50'),
	(2, NULL, '常规管理', 'fas fa-cogs', 0, 1, 2, 1, '2021-01-19 10:45:37', '2021-01-19 10:46:04'),
	(3, 'admin/profile', '个人资料', 'fas fa-user-circle', 2, 1, 1, 1, '2021-01-19 10:46:47', '2021-01-19 10:48:02'),
	(4, 'log/index', '登录日志', 'fas fa-list-alt', 2, 1, 2, 1, '2021-01-19 10:47:19', '2021-01-19 10:49:09'),
	(5, NULL, '权限管理', 'fas fa-user-cog', 0, 1, 3, 1, '2021-01-19 10:49:32', '2021-01-19 10:49:40'),
	(6, 'admin/index', '管理员列表', 'fas fa-users', 5, 1, 1, 1, '2021-01-19 10:49:58', '2021-01-19 10:50:12'),
	(7, 'auth_group/index', '角色组', 'fas fa-users-cog', 5, 1, 2, 1, '2021-01-19 10:50:29', '2021-01-20 16:17:07'),
	(8, 'auth_rule/index', '菜单列表', 'fas fa-bars', 5, 1, 3, 1, '2021-01-19 10:50:48', '2021-01-21 11:34:36'),
	(9, 'admin/phpinfo', 'phpinfo()', '', 1, 2, 1, 1, '2021-01-19 10:52:18', '2021-01-19 10:52:36'),
	(10, 'admin/update_profile', '更新个人资料', '', 3, 2, 1, 1, '2021-01-19 10:53:03', '2021-01-19 10:53:12'),
	(11, 'admin/add', '添加', '', 6, 2, 1, 1, '2021-01-20 15:47:19', '2021-01-20 16:20:23'),
	(12, 'admin/edit', '编辑', '', 6, 2, 2, 1, '2021-01-20 15:48:12', '2021-01-20 16:20:25'),
	(13, 'admin/delete', '删除', '', 6, 2, 3, 1, '2021-01-20 15:48:28', '2021-01-20 16:20:27'),
	(14, 'auth_group/add', '添加', '', 7, 2, 1, 1, '2021-01-20 16:58:35', '2021-01-20 16:58:37'),
	(15, 'auth_group/edit', '编辑', '', 7, 2, 2, 1, '2021-01-20 16:58:58', '2021-01-20 16:58:58'),
	(16, 'auth_group/delete', '删除', '', 7, 2, 3, 1, '2021-01-20 16:59:21', '2021-01-20 16:59:21'),
	(17, 'auth_rule/add', '添加', '', 8, 2, 1, 1, '2021-01-21 11:35:01', '2021-01-21 11:35:01'),
	(18, 'auth_rule/edit', '编辑', '', 8, 2, 2, 1, '2021-01-21 11:35:31', '2021-01-21 11:35:31'),
	(19, 'auth_rule/delete', '删除', '', 8, 2, 3, 1, '2021-01-21 11:35:45', '2021-01-21 11:35:51'),
	(20, NULL, '网站管理', 'fas fa-th', 0, 1, 4, 1, '2021-02-09 10:25:34', '2021-02-09 10:25:34'),
	(21, 'basic_settings/index', '基本设置', 'fas fa-cog', 20, 1, 1, 1, '2021-02-09 10:27:22', '2021-02-09 10:43:22'),
	(22, 'column/index', '栏目列表', 'fas fa-columns', 20, 1, 4, 1, '2021-02-09 10:28:30', '2021-02-14 13:47:38'),
	(23, 'article/index', '文章列表', 'fas fa-file', 20, 1, 5, 1, '2021-02-09 10:29:56', '2021-02-22 16:09:23'),
	(24, 'link/index', '友情链接', 'fas fa-link', 20, 1, 6, 1, '2021-02-09 10:31:11', '2021-02-10 15:53:33'),
	(25, 'api/index', 'API设置', 'fas fa-cogs', 20, 1, 2, 1, '2021-02-10 14:28:42', '2021-03-05 16:21:12'),
	(26, 'sitemap/index', 'sitemap', 'fas fa-map', 20, 1, 3, 1, '2021-02-10 14:30:11', '2021-03-05 15:07:04'),
	(27, 'link/add', '添加', '', 24, 2, 1, 1, '2021-02-10 16:08:45', '2021-02-10 16:08:45'),
	(28, 'link/edit', '编辑', '', 24, 2, 2, 1, '2021-02-10 16:09:19', '2021-02-10 16:09:19'),
	(29, 'link/delete', '删除', '', 24, 2, 3, 1, '2021-02-10 16:09:42', '2021-02-10 16:10:12'),
	(30, 'column/add', '添加', '', 22, 2, 1, 1, '2021-02-22 16:12:43', '2021-02-22 16:12:43'),
	(31, 'column/edit', '编辑', '', 22, 2, 2, 1, '2021-02-22 16:13:08', '2021-02-22 16:13:08'),
	(32, 'column/delete', '删除', '', 22, 2, 3, 1, '2021-02-22 16:13:31', '2021-02-22 16:13:31'),
	(33, 'article/add', '添加', '', 23, 2, 1, 1, '2021-02-22 17:04:06', '2021-02-22 17:04:06'),
	(34, 'article/edit', '编辑', '', 23, 2, 2, 1, '2021-02-22 17:04:22', '2021-02-22 17:04:22'),
	(35, 'article/delete', '删除', '', 23, 2, 3, 1, '2021-02-22 17:04:41', '2021-02-22 17:04:41'),
	(36, 'common/upload', '图片上传', '', 23, 2, 4, 1, '2021-02-28 09:54:54', '2021-02-28 09:54:54'),
	(37, 'article/push', '文章推送', '', 23, 2, 5, 1, '2021-03-05 18:46:17', '2021-03-05 18:46:17');


CREATE TABLE IF NOT EXISTS `column` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '栏目名称',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '栏目类型：1 首页， 2 数据列表，3 单页面，4 第三方链接',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '第三方跳转链接',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级栏目ID',
  `sort_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序：越小越靠前',
  `target_blank` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '新窗口打开：1 否，2 是',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描叙',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员id',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 显示在顶部导航，2，显示在底部导航，3 隐藏',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='栏目表';

CREATE TABLE IF NOT EXISTS `link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL COMMENT '网址',
  `title` varchar(255) NOT NULL COMMENT '名称',
  `nofollow` enum('y','n') NOT NULL DEFAULT 'n' COMMENT '是否添加nofollow',
  `is_show` enum('y','n') NOT NULL DEFAULT 'y' COMMENT '是否显示',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='友情链接表';

CREATE TABLE IF NOT EXISTS `login_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL COMMENT '管理员id',
  `ip` varchar(255) NOT NULL COMMENT '登录IP',
  `user_agent` varchar(255) NOT NULL COMMENT '浏览器user agent',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1 成功，2 失败',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';
SQL;
        // 添加管理员记录表
        $curTime = date('Y-m-d H:i:s');
        $adminPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $insertSql .= "INSERT INTO `admin` (`id`, `name`, `avatar`, `gender`, `password`, `auth_group_id`, `status`, `create_time`, `update_time`) VALUES (1, '{$adminName}', '', 1, '{$adminPassword}', 0, 1, '{$curTime}', '{$curTime}')";
        $count = $dbh->exec($insertSql);
        if ($count !== 1) {
            exit('Mysql初始化数据失败！');
        }
        // 创建数据库配置文件
        $databaseFile = ROOT_DIR . '/config/database.php';
        $databaseData = <<<DATA
<?php
/**
 * 数据库配置
 */
return [
    /**
     * 不同的数据库可以配置不同的名称，mysql为默认连接名称
     */
    'mysql' => [
        // 主机ip
        'host' => '{$mysqlHost}',
        // mysql 用户名
        'user' => '{$mysqlUser}',
        // mysql 密码
        'password' => '{$mysqlPassword}',
        // mysql 端口
        'port' => {$mysqlPort},
        // mysql 默认数据库
        'database' => '{$mysqlDatabase}',
        // mysql 字符编码
        'charset' => 'utf8mb4'
    ],
    'redis' => [
        // 主机ip
        'host' => '127.0.0.1',
        // redis 端口
        'port' => 6379,
        // redis 密码
        'pass' => ''
    ],
    'elasticsearch' => [
        // 协议
        'protocol' => 'http',
        // 主机ip
        'ip' => '127.0.0.1',
        // 端口
        'port' => 9200,
        // 默认 数据库，索引
        'database' => 'test',
        // 默认 表，文档
        'table' => 'library'
    ]
];
DATA;
        if (false === file_put_contents($databaseFile, $databaseData)) {
            exit('数据库配置文件（' . $databaseFile . '）写入数据失败，可能没有权限读写！');
        }

        // 写入安装文件
        if (!file_put_contents($installLockFile, $curTime)) {
            exit('无法写入安装锁文件（' . $installLockFile . '）!');
        }
        $installResult = 2;
    } catch (PDOException $e) {
        exit('Mysql连接失败: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/css/all.min.css" rel="stylesheet">
    <link href="/css/icheck-bootstrap.min.css" rel="stylesheet">
    <link href="/css/adminlte.min.css" rel="stylesheet">
    <title>安装-SmartyCms内容管理系统</title>
</head>
<body class="bg-dark">
<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card mt-3">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">SmartyCms内容管理系统</p>
                    <?php if (1 === $installResult) { ?>
                        <form action="/install.php" method="post" enctype="multipart/form-data">
                            <h5 class="mb-3 text-danger">数据库信息</h5>
                            <div class="mb-3">
                                <label>Mysql主机IP</label>
                                <input type="text" name="mysql_host" required="required" class="form-control"
                                       value="127.0.0.1">
                            </div>
                            <div class="mb-3">
                                <label>Mysql用户名</label>
                                <input type="text" name="mysql_user" required="required" class="form-control"
                                       value="root">
                            </div>
                            <div class="mb-3">
                                <label>Mysql密码</label>
                                <input type="password" name="mysql_password" required="required" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Mysql端口</label>
                                <input type="number" name="mysql_port" required="required" class="form-control"
                                       value="3306">
                            </div>
                            <div class="mb-3">
                                <label>Mysql数据库</label>
                                <input type="text" name="mysql_database" required="required" class="form-control"
                                       value="smartycms">
                            </div>
                            <h5 class="mb-3 text-danger">管理员信息</h5>
                            <div class="mb-3">
                                <label>用户名</label>
                                <input type="text" name="admin_name" required="required" class="form-control"
                                       value="admin">
                            </div>
                            <div class="mb-3">
                                <label>密码</label>
                                <input type="password" name="admin_password" required="required" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-block">安装</button>
                                </div>
                            </div>
                        </form>
                    <?php } else { ?>
                        <div class="alert alert-success" role="alert">
                            恭喜您，SmartyCms内容管理系统已经安装成功了！
                        </div>
                        <div class="text-center">
                            <a href="/login" class="btn btn-link">登录后台</a>
                            <a href="/" class="btn btn-link">前台首页</a>
                        </div>
                        <div class="text-danger mt-2">
                            <small>由于安装时没有安装初始化数据，前台首页无内容可以查看，需要您在后台设置好栏目、文章等内容后，前台首页布局内容才会展现。</small>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>