<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 1/4/2016
 * Time: 20:57
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\helpers\Meta;
use iauth\helpers\Number;

class AuthController extends IAuthController
{

    public function allowActions()
    {
        return [
            /* 非页面 */
            'can'
        ];
    }

    /**
     * 判断是否可执行。
     * @param string $code
     */
    public function actionCan($code)
    {
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : \Yii::app()->iuser->id;
        if (!Number::isIntPk($userId)) {
            $userId = \Yii::app()->user->id;
        }
        if (!$userId) {
            $this->renderJson(Meta::C_USER_NOT_LOGIN);
        } else {
            /* @var \iauth\components\IDbAuthManager $iAuthManager */
            $iAuthManager = \Yii::app()->iDbAuthManager;
            if ($iAuthManager->checkAccess($code, $userId, false)) {
                $this->renderJson(Meta::C_SUCCESS);
            } else {
                $this->renderJson($iAuthManager->getErrCode());
            }
        }
    }
}
