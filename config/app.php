<?php
/**
 * 应用配置
 */

return [
    // 调试，false 关闭，true 开启
    'debug' => true,
    // nginx转发的请求参数名称。rewrite ^(.*)$ /index.php?s=$1 last;
    'query_str' => 's',
    // 设置X-Powered-By信息，支持中文
    'x_powered_by' => 'MySmarty',
    // 应用初始化执行方法
    'app_init' => '',
    // 加密 key，定义之后不要修改，否则会导致之前加密的数据无法解密
    'encryption_key' => '',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 应用默认url，不要以http开头或以 / 结尾，如：www.baidu.com
    'app_url' => '',
    // SmartyCms版本号
    'smarty_cms_version' => '0.0.1',
    // 框架版本号
    'smarty_admin_version' => '0.0.2',
    // 管理员登录session名
    'smarty_admin_session' => 'smartyAdmin',
];