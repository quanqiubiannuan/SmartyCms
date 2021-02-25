<?php

namespace application\home\controller;

use library\mysmarty\Route;
use library\mysmarty\Upload;

/**
 * 公共类，需要登录后才可以使用
 * @package application\home\controller
 */
#[Route('/common')]
class Common extends Backend
{
    /**
     * 图片上传
     */
    public function upload()
    {
        $pic = Upload::getInstance()
            ->setLimitType(['image/png', 'image/jpeg', 'image/gif', 'image/jpg'])
            ->move('upload');
        if (empty($pic)) {
            http_response_code(500);
            exit();
        }
        echo json_encode(['default' => $pic, 'url' => $pic]);
        exit();
    }
}