<?php

/**
 * @abstract openapi 通过手机号码判断跳转接口  手机号码在网信已经开户跳转登录否则注册
 * @date  2015-06-23
 * @author yutao@ucfgroup.com
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class RegLogin extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "type" => array("filter" => 'string'),
            "redirect_uri" => array("filter" => 'string'),
            "response_type" => array("filter" => 'string'),
            "scope" => array("filter" => 'string'),
            "state" => array("filter" => 'string'),
            'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字", 'option' => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/")),
            "site_id" => array("filter" => 'string', "option" => array("optional"=>true)),
            "cn" => array("filter" => 'string', "option" => array("optional"=>true)),
            "event_cn_hidden" => array("filter" => 'string', "option" => array("optional"=>true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $request = new RequestUserMobile();
        try {
            $request->setMobile($data['mobile']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $userResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getUserInfoByMobile',
            'args' => $request,
        ));
        //此用户没有在网信开户
        if ($userResponse->resCode) {
            $url = PRE_HTTP . "www.wangxinlicai.com/user/register?" . $_SERVER['QUERY_STRING'];
        } else {
            $url = "login?" . $_SERVER['QUERY_STRING'];
        }

        header("Location:" . $url);
        return true;
    }

    /**
     * oauth2 认证，返回code码
     */
    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->assign("errorCode", $this->errorCode);
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

}
