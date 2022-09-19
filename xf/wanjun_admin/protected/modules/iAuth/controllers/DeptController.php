<?php

/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/21
 * Time: 17:18
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\models\Dept;
use iauth\helpers\Meta;

class DeptController extends IAuthController
{
    public function allowActions()
    {
        return [
            /* 非操作 */
            'list'
        ];
    }

    public function actionList()
    {
        $list = (new Dept())->getList();
        $this->renderJson(Meta::C_SUCCESS, [
            'count' => count($list),
            'list' => $list
        ]);
    }
}