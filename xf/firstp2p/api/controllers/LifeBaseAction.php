<?php
namespace api\controllers;

use api\conf\Error;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Life\Enum\ErrorCode;
use NCFGroup\Protos\Life\Enum\CommonEnum;

/**
 * LifeBaseAction
 * 网信生活基类
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author weiwei12@ucfgroup.com
 */
class LifeBaseAction extends AppBaseAction
{

    public function _before_invoke()
    {
        parent::_before_invoke();
        return true;
    }

    /**
     * 检测访问是否正常
     */
    public function isServiceOpen()
    {
        if (app_conf(CommonEnum::LIFE_SWITCH) == 1) {
            return true;
        }
        return false;
    }

    /**
     * 如果出错，允许表层设置错误
     * 读取NCFGroup\Protos\Life\Enum\ErrorCode下面定义的错误码
     */
    public function setApiErr($errCode, $errMsg = '') {
        $this->errno = addslashes($errCode);
        if (!empty(ErrorCode::$errMsg[$errCode])) {
            $this->error = !empty($errMsg) ? $errMsg : ErrorCode::$errMsg[$errCode];
        } else if (!empty(ErrorCode::$errMsgMap[$errCode])) {
            $this->error = ErrorCode::$errMsgMap[$errCode]['msg'];
            $this->errno = ErrorCode::$errMsgMap[$errCode]['code'];
        }else if (!empty($errMsg)) {
            $this->error = $errMsg;
        }
    }
}