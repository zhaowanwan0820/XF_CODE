<?php
/**
 * 用户签署网信超级账户免密协议
 * @author yanjun
 */
namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;

class SignWxFreepayment extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array("filter" => "required", "message" => "oauth_token is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $user_info = $this->getUserByAccessToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $ret = $this->rpc->local('UserService\signWxFreepayment', array(intval($user_info->userId)));
        if(!$ret) {
            $this->setErr("ERR_SYSTEM", "签署失败");
            return false;
        }
        $this->json_data = $ret;
        return true;
    }
}
