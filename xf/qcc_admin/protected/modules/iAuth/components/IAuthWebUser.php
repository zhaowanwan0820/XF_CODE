<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 1/4/2016
 * Time: 10:04
 */

namespace iauth\components;

use iauth\helpers\Meta;

class IAuthWebUser extends \CWebUser
{
    public $errCode = 0;

    /**
     * 判断用户是否有权限执行指定操作
     * @param string $operation
     * @param bool $willRun
     * @return bool $res
     */
    public function can($operation = null, $willRun = false)
    {
        if ($this->getIsGuest()) {
            $this->errCode = Meta::C_USER_NOT_LOGIN;
            return false;
        } else {
            /* @var IDbAuthManager $iDbAuthManager */
            $iDbAuthManager = \Yii::app()->iDbAuthManager;
            $res = $iDbAuthManager->checkAccess($operation, $this->getId(), $willRun);
            $this->errCode = $iDbAuthManager->getErrCode();
            return $res;
        }
    }

    /**
     * 该方法用于兼容原系统的判断
     * @param string $operation
     * @param array $data
     * @param array $param
     * @return bool|void
     */
    public function checkAccess($operation, $data = [], $param = [])
    {
        return $this->canShow($operation);
    }

    /**
     * 根据 errCode 判断是否可以显示
     * @param $operation
     * @return bool
     */
    public function canShow($operation)
    {
        $showCodes = [
            /* 正常状态 */
            Meta::C_SUCCESS,
            /* 正常但需要双因子认证， 也可显示*/
            Meta::C_AUTH_NEED_DUAL_FACTOR
        ];
        $this->can($operation);
        return in_array($this->getErrCode(), $showCodes);
    }

    public function getErrCode()
    {
        return $this->errCode;
    }

}