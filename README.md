# SmartyAdmin
基于MySmarty框架开发的SmartyAdmin后台极速开发框架。

**介绍**

SmartyAdmin后台极速开发框架是基于PHP开发的后台管理系统，它完成了基础的后台管理系统的开发工作，让您专注于后台业务的开发。

**特点**

① 服务端渲染。

② 管理员管理，可为管理员设置角色。一个管理员只能对应一个角色。

③ 角色组管理，支持角色继承，父级角色可查看子级角色等数据。但父级角色的规则与子级角色的菜单规则可以不同，言外之意就是父级角色的菜单与子级角色的菜单没有继承关系。同时，角色的菜单规则设置只能在当前管理员拥有的菜单规则里，当前管理员没有的菜单规则无法设置。

④ 菜单管理，同样支持菜单继承，但不应超过2级菜单。

⑤ 自适应，支持手机端后台管理。

⑥ 不需要学习新技术，会MySmarty框架开发就会用这个极速后台框架开发。

⑦ 除了权限管理需要继承 `Backend` 或 `BackendCurd` 类外，你可以任意的开发，没有任何的限制。

**第三方库**

**后端**

MySmarty 1.0.1

**前端**

主框架： AdminLTE 3.1.0

Css框架： Bootstrap v4.6.0

图标：Font Awesome Free 5.15.2

**开发文档**

MySmarty框架：https://github.com/quanqiubiannuan/MySmarty

AdminLTE：https://adminlte.io/docs/3.1/index.html

Bootstrap：https://getbootstrap.com/docs/4.6/getting-started/introduction/

Font Awesome：https://fontawesome.com/how-to-use/on-the-web/referencing-icons/basic-use

**预览**

https://github.com/quanqiubiannuan/files/tree/main/smartyadmin/0.0.2/%E9%A2%84%E8%A7%88%E5%9B%BE

https://gitee.com/daiji2/files/tree/main/smartyadmin/0.0.2/%E9%A2%84%E8%A7%88%E5%9B%BE

**安装**

`php >= 8`

数据库统一使用  `utf8mb4` 编码

1、创建数据库 `smartyadmin`

```sql
CREATE DATABASE IF NOT EXISTS `smartyadmin`;
```

2、创建管理员表

```sql
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
```

3、创建角色表

```sql
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
```

4、创建菜单表

```sql
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COMMENT='规则表';
```

5、创建登录日志表

```sql
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
```

6、添加超级管理员数据，账号：admin，密码：123456

```sql
INSERT INTO `admin` (`id`, `name`, `avatar`, `gender`, `password`, `auth_group_id`, `status`, `create_time`, `update_time`) VALUES
	(1, 'admin', '', 0, '$2y$10$3RtE7uID0oX6kclR986EhuK5MKNROxv22JmC4G4SMtWyxq4veHh7u', 0, 1, '2021-01-19 10:41:42', '2021-01-19 10:42:48');
```

7、添加菜单规则数据

```sql
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
	(18, 'auth_rule/edit', '编辑', '', 8, 2, 2, 1, '2021-01-2s1 11:35:31', '2021-01-21 11:35:31'),
	(19, 'auth_rule/delete', '删除', '', 8, 2, 3, 1, '2021-01-21 11:35:45', '2021-01-21 11:35:51');
```

完整数据库参考文件：https://github.com/quanqiubiannuan/files

8、修改框架默认配置

项目根目录：config文件夹

完成以上工作后，就可以访问后台了，开始你的开发工作吧。

**部署**

只需要将项目下的public目录作为网站的根目录，同时将所有请求转发至`index.php`即可。

参考文档：https://github.com/quanqiubiannuan/MySmarty

**开发**

如果你的功能需要权限管理，则需要继承 `application/home/controller/Backend.php` 类。

如果你的功能需要权限管理，且拥有简单的列表、添加、编辑、删除功能，则可以继承 `application/home/controller/BackendCurd.php` 类。

`BackendCurd` 继承了 `Backend` 类。

当实现了功能，你需要将菜单规则在后台添加，否则只有管理员才可以访问。不需要权限管理的则不需要添加。

不需要权限管理代表任何人都可以访问，不需要登录就可以访问。

在实际开发中可以参考：

需要权限管理

`application/home/controller/Admin.php`

`application/home/controller/AuthGroup.php`

不需要权限管理

`application/home/controller/Index.php`

除了权限管理需要继承 `Backend` 或 `BackendCurd` 类外，你可以任意的开发，没有任何的限制。





