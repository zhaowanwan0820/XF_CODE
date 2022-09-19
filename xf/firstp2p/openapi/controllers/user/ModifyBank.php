<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class ModifyBank extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
           'oauth_token' => array("filter" => "required", "message" => "oauth_token is required"),
           'return_url'  => array("filter" => "required", "message" => "return_url is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        //待完善存管换卡功能, 暂时封换卡
        $this->show_error('抱歉，该功能暂时关闭，请前往PC端完成换卡');
        return;

        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (intval(app_conf("ID5_VALID")) === 3) {
            $clientId = $_REQUEST['client_id'];
            if ($clientId && array_key_exists($clientId, $GLOBALS['sys_config']['OAUTH_SERVER_CONF']) && !empty($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['redirect_uri'])) {
                $temp = explode('/', $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['redirect_uri']);
                $redirectUri = $temp[0] . '//' . $temp[2];
                $this->tpl->assign("redirect_uri", $redirectUri);
            }
            $this->tpl->assign("page_title", "系统维护中");
            $this->tpl->assign("content", app_conf("ID5_MAINTEN_MSG"));
            $this->template = "openapi/views/user/maintain_h5.html";
            return;
        }


        $data = $this->form->data;
        $this->tpl->assign('returnUrl', $data['return_url']);

        $asgn = md5(uniqid());
        \es_session::start();
        \es_session::set('openapi_cr_token', $data['oauth_token']);
        \es_session::set('openapi_cr_asgn', $asgn);
        $this->tpl->assign('asgn', $asgn);

        $userInfo = $userInfo->toArray();
        $this->tpl->assign('userInfo', $userInfo);

        $bankService = new \core\service\BankService();
        $bankResult  = $bankService->getFastPayBanks();
        $bankList = (array) $bankResult['data'];
        $this->tpl->assign('bankList', json_encode($bankList));

        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
        $this->template = $this->getCustomTpl("openapi/views/user/modify_bankcard.html", 'modifyBank');
        if(isset($this->clientConf['js']['modifyBank'])){
                $fzjs = $this->clientConf['js']['modifyBank'].'?'.date("dH");
                $this->tpl->assign('fzjs', $fzjs);
        }

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

}
