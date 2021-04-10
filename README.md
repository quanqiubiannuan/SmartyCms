# SmartyCms内容管理系统

基于SmartyAdmin后台极速开发框架开发的SmartyCms内容管理系统。

**介绍**

SmartyCms内容管理系统是基于PHP开发的现代化cms内容管理系统，不需要您会编程，即可快速搭建一个文章网站、资讯网站、简单企业展示网站、作文网站、语录网站、答案网站等之类的内容网站。

**特点**

① 后台多用户管理、角色管理、权限管理。

② 采用服务端渲染技术，更易于搜索引擎抓取内容。

③ 网站自适应，后台和前端均可在PC、手机端浏览。

④ 栏目导航可自定义设置，支持首页、数据列表、单页面、第三方链接栏目类型。

⑤ 内置sitemap、主动推送API功能，发布的文章可以主动推送到站长平台。

⑥ 支持友情链接功能，与他人换链，更利于搜索引擎发现网站。

⑦ 代码无加密，完全免费使用，可商用。基于Apache License 2.0 开源协议。

**安装**

一、环境要求

`php >= 8`

二、代码上传

 将代码上传至您的服务器，如`/usr/share/nginx/html/`文件夹下

三、权限设置

 更改文件夹权限（所有者，读取、执行，组，读取，其它，啥都没有）

```
chmod -R 540 SmartyCms
```

 确保`SmartyCms/runtime`,`SmartyCms/public/upload`具有可写权限

```
chmod -R 740 SmartyCms/runtime
chmod -R 740 SmartyCms/public/upload
```

 确保`SmartyCms/public/runtime`具有可读可写权限

```
chmod -R 740 SmartyCms/public/runtime
```

四、服务器配置

 添加转发，将所有请求转发至`public/index.php`

```nginx
location / {
	try_files $uri $uri/ /index.php?$query_string;
}
```

以nginx为例

参考示例

```nginx
server {
    #端口
    listen 80;
    #域名
    server_name localhost;
    #代码位置
    root /usr/share/nginx/html/SmartyCms/public;
    #首页默认文件
    index index.html index.htm index.php;
    #文件编码
    charset utf-8;
    #错误页面
    error_page 500 502 503 504 /50x.html;
    #所有请求转发至index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    #配置favicon.ico请求
    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }
    #配置robots.txt请求
    location = /robots.txt {
        access_log off;
        log_not_found off;
    }
    #处理PHP文件
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

五、开始安装

在浏览器中打开 `您的域名/install.php` 开始安装

按界面内的要求填写好数据库信息和管理员信息

完成安装后，需要登录后台设置栏目、添加文章后，前台页面才会显示相应布局内容

六、出现问题？

 页面出现403/500？

 `（一般为文件没有可写权限）`

 `确保启动用户和nginx工作用户一致`

 `确保nginx配置了默认主页文件`

 `确保文件具有写文件权限`

 SELinux设置为开启状态（enabled）的原因：

 查看当前selinux的状态： `/usr/sbin/sestatus`

 关闭selinux：`vi /etc/selinux/config`，将`SELINUX=enforcing` 修改为 `SELINUX=disabled` 状态

 重启系统，`reboot`





