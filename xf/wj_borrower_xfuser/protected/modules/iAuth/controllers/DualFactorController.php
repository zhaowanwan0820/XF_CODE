<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/24
 * Time: 15:41
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\helpers\Meta;

/**
 * 双因子认证控制器
 * Class DualFactorController
 * @package iauth\controllers
 */
class DualFactorController extends IAuthController
{

    public function allowActions()
    {
        return [
            /* 非页面 */
            'verify'
        ];
    }

    public function allowActionsOnLogin()
    {
        return ['send'];
    }

    /**
     * 发送认证码
     * @param string $operation 操作描述
     */
    public function actionSend($operation = '后台操作')
    {
        /* @var \iauth\components\DualFactor $dualFactor */
        $dualFactor = $this->getModule()->dualFactor;
        $userId = \Yii::app()->iuser->id;
        $sendResult = $dualFactor->send($operation, $userId);
        $data = [
            'phone' => $dualFactor->getReceiver()
        ];
        if ($sendResult) {
            $this->renderJson(Meta::C_SUCCESS, $data);
        } else {
            $errCode = $dualFactor->getErrCode();
            $this->logReqParamsWith(Meta::getMeta($errCode));
            $this->renderJson($errCode, $data);
        }
    }

    /**
     * 验证
     * @param string $code
     */
    public function actionVerify($code)
    {
        /* @var \iauth\components\DualFactor $dualFactor */
        $dualFactor = $this->getModule()->dualFactor;
        if ($dualFactor->verify($code)) {
            $this->renderJson(Meta::C_SUCCESS);
        } else {
            $this->renderJson($dualFactor->getErrCode());
        }
    }
}
