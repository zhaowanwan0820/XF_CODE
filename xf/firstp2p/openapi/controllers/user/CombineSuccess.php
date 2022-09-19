<?php

/**
 * @abstract openapi  身份认证接口
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 * @date 2015-04-27
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

/**
 * 个人身份认证成功
 *
 * @package openapi\controllers\account
 */
class CombineSuccess extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'return_url' => array("filter" => "required", "message" => "return_url is required"),
            'asgn' => array("filter" => "required", "message" => "asgn is required"),
            'hide_micropayment' => array("filter" => "int", "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

	$asgn = \es_session::get('openapi_cr_asgn');
        if ($asgn != $this->form->data['asgn']) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
	//检查本站传入的token
        $token = \es_session::get('openapi_cr_token');
        if (empty($token)){
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
        $this->form->data['oauth_token'] = $token;

        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userInfo = $userInfo->toArray();
        if (!empty($userInfo['idno']) && !empty($userInfo['bankNo']) && $userInfo['bankNo'] != '无') {
            app_redirect($data['return_url']);
        }

        if (false !== stripos($data['return_url'], 'wangxinlicai.com')) {
            $returnUrl = parse_url($data['return_url']);
            $backUrl = $returnUrl['scheme'].'://'.$returnUrl['host'];
        } else {
            $backUrl = $data['return_url'];
        }

        $params = [
            'userId' => $userInfo['userId'],
            'returnUrl' => $backUrl,
            'failUrl' => $backUrl,
            'reqSource' => 2
                ];
        if (!empty($data['hide_micropayment'])) {
            $params['isNeedTransfer'] = 0;
        }
        try {
            $service = new \core\service\PaymentUserAccountService();
            $bindUrl = $service->h5AuthBindCard($params);
        } catch (\Exception $e) {
            $this->setErr(-1, $e->getMessage());
            return false;
        }

        $this->template = "openapi/views/user/combine_success.html";
        $this->tpl->assign('userInfo', $userInfo);
        $this->tpl->assign('openId', $data['openId']);
        $this->tpl->assign('bindUrl', $bindUrl);
        $this->tpl->assign('returnUrl', $backUrl);
        $this->tpl->assign('asgn', $asgn);
        $this->tpl->assign('showNav', intval($_GET['showNav']));
        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
        return true;
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->assign("errorCode", $this->errorCode);
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

    public function authCheck(){
        return true;
    }
}
