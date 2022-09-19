<?php
/**
 * 用户迁移到经讯时代确认
 * @author 王传路
 */
namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\UserService;

class TransferConfirm extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = $this->sys_param_rules ;
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $ret = $this->rpc->local('UserService\updateUserToJXSD', array($userInfo->userId));
        if($ret) {
            $this->json_data = array();
            return true;
        } else {
            $this->errorCode = 1;
            $this->errorMsg = "确认迁移失败，请稍后重试";
            return false;
        }
    }
}
