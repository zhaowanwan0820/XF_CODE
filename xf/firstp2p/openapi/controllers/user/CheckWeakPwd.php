<?php

/**
 * 弱密码检查
 * @author lvbaosong <lvbaosong@ucfgroup.com>
 * @date 2016-09-27
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;

class CheckWeakPwd extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = $this->sys_param_rules;

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if(!$userInfo){
            $this->errorCode = -1;
            $this->errorMsg = "获取用户信息失败!";
            return false;
        }
        $this->json_data = array('risk_weak_pwd'=>0);//0:非弱密码;1弱密码
        $userName = $userInfo->userName;
        $mobile = $userInfo->mobile;
        try{
            $values = \SiteApp::init()->dataCache->getRedisInstance()->mget(array("risk_rmm_{$mobile}","risk_rmm_{$userName}"));
            if(!empty($values)&&($values[0]==1||$values[1]==1)){
                $this->json_data = array('risk_weak_pwd'=>1);
                \SiteApp::init()->dataCache->getRedisInstance()->del(array("risk_rmm_{$mobile}","risk_rmm_{$userName}"));
            }
        }catch (\Exception $e){
            $this->errorCode = -1;
            $this->errorMsg = "服务异常!";
            return false;
        }
        return true;
    }
}

