<?php
/**
 * 弱密码检查
 * @author lvbaosong <lvbaosong@ucfgroup.com>
 * @date 2016-09-27
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class CheckWeakPwd extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $this->json_data = array('risk_weak_pwd'=>0);//0:非弱密码;1弱密码
        $userName = $loginUser['user_name'];
        $mobile = $loginUser['mobile'];
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
