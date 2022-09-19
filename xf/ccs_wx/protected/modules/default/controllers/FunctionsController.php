<?php

/**
 * 后台公用方法处理的控制器
 * @file   FunctionsController.php
 * @author JU<zhaopengju@itouzi.com>
 * @date   2016/09/26
 *
 **/
class FunctionsController extends DController
{
    //public $layout = "main";

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/main';

    /**
     * 分片上传文件接口
     */
    public function ActionUploadSlice()
    {
        $result = FunctionsService::getInstance()->uploadSlice($_FILES, $_POST, $_GET);
        $this->echoJson($result['data'], $result['code'], $result['info']);
    }

    public function actionGet()
    {
        /*$data = array(
            'service'  => 'PackageService',
            'function' => 'lists'
        );
        $result = FunctionsService::getInstance()->get($data);
        $this->echoJson($result['data'], $result['code'], $result['info']);*/

    }

}

