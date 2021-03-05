<?php

use library\mysmarty\Query;

/**
 * 检查访问的url是否有权限访问
 * @param string $currentPath 访问的url路径
 * @return bool
 */
function auth(string $currentPath): bool
{
    if (true === getSession('isSuperAdmin')) {
        return true;
    }
    $authRuleUrlData = getSession('authRuleUrlData');
    if (!empty($authRuleUrlData) && in_array($currentPath, $authRuleUrlData, true)) {
        return true;
    }
    return false;
}

/**
 * 权限判断A标签
 * @param string $href 跳转的url路径
 * @param string $title a标签的内容
 * @param string $class a标签的类
 * @param string $target a标签的target
 * @return string
 */
function smartyAdminHref(string $href, string $title, string $class = 'btn-link', string $target = ''): string
{
    if (auth($href)) {
        $attr = 'href="' . getAbsoluteUrl() . '/' . $href . '"';
        $attr .= ' title="' . $title . '"';
        if (!empty($class)) {
            $attr .= ' class="' . $class . '"';
        }
        if (!empty($target)) {
            $attr .= ' target="' . $target . '"';
        }
        return '<a ' . $attr . '>' . $title . '</a>';
    }
    return '';
}

/**
 * 按级别输出字符串
 * @param int $level 分类级别
 * @param string $str 要输出的字符串
 * @return string
 */
function echoLevelStr(int $level, string $str = '　'): string
{
    if ($level <= 1) {
        return '';
    }
    return str_repeat($str, $level - 1);
}

/**
 * 获取指定长度的随机字符串
 * @param int $num 随机字符串长度
 * @param string $salt 加入生成的随机变量值，增加随机
 * @return string
 */
function getUri(int $num = 32, string $salt = ''): string
{
    $str = '';
    $maxLen = ceil($num / 32);
    for ($i = 0; $i < $maxLen; $i++) {
        $str .= md5(time() . mt_rand(1000, 9999) . $salt . $i);
    }
    return substr($str, 0, $num);
}

/**
 * 推送至百度
 * @param string|array $urls 推送链接
 * @param string $type 对提交内容的数据类型说明，快速收录参数：daily
 * @return string
 */
function pushToBaidu(array|string $urls, string $type = 'daily'): string
{
    if (is_string($urls)) {
        $urls = [$urls];
    }
    $api = 'http://data.zz.baidu.com/urls?site=' . getAbsoluteUrl() . '&token=' . config('api.token');
    if (!empty($type)) {
        $api .= '&type=' . $type;
    }
    $ch = curl_init();
    $options = array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    return (string)$result;
}

/**
 * 推送至bing
 * @param array|string $urls 推送链接
 * @return string
 */
function pushToBing(array|string $urls): string
{
    $apikey = config('api.apikey');
    if (is_string($urls)) {
        $urls = [$urls];
    }
    $result = Query::getInstance()->setUrl('https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' . $apikey)
        ->setPostFields(json_encode([
            'siteUrl' => getAbsoluteUrl(),
            'urlList' => $urls
        ]))
        ->setHeader([
            'Content-Type: application/json; charset=utf-8'
        ])
        ->getOne();
    return (string)$result;
}