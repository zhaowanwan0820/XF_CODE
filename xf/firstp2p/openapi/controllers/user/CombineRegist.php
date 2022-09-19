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
 * 个人身份认证加绑卡
 *
 * Class CombineRegist
 * @package openapi\controllers\account
 */
class CombineRegist extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'oauth_token' => array("filter" => "required", "message" => "oauth_token is required"),
                'return_url' => array("filter" => "required", "message" => "return_url is required"),
                'type' => array("filter" => "string", "option" => array('optional' => true)),
                'hide_micropayment' => array("filter" => "int", "option" => array('optional' => true)),
                );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userInfo = $userInfo->toArray();
        if (false !== stripos($data['return_url'], 'wangxinlicai.com')) {
            $returnUrl = parse_url($data['return_url']);
            $backUrl = $returnUrl['scheme'].'://'.$returnUrl['host'];
        } else {
            $backUrl = $data['return_url'];
        }
        $hide_micropayment = empty($data['hide_micropayment']) ? 0:intval($data['hide_micropayment']);
        $asgn = md5(uniqid());
        \es_session::start();
        \es_session::set('openapi_cr_token', $data['oauth_token']);
        \es_session::set('openapi_cr_asgn', $asgn);

        if (!empty($userInfo['idno'])) {
            if (!empty($userInfo['cardVerify'])) {
                app_redirect($backUrl);
            } else {
                $params = [
                    'userId' => $userInfo['userId'],
                    'returnUrl' => $backUrl,
                    'failUrl' => $backUrl,
                    'reqSource' => 2
                    ];
                if ($hide_micropayment) {
                    $params['isNeedTransfer'] = 0;
                }
                try {
                    $service = new \core\service\PaymentUserAccountService();
                    $bindUrl = $service->h5AuthBindCard($params);
                } catch (\Exception $e) {
                    $this->setErr(-1, $e->getMessage());
                    return false;
                }
                app_redirect($bindUrl);
            }
        }
        //身份认证维护页
        if (intval(app_conf("ID5_VALID")) === 3) {
            $client_id=$_REQUEST['client_id'];
            if ($client_id && array_key_exists($client_id, $GLOBALS['sys_config']['OAUTH_SERVER_CONF']) && isset($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$client_id]['redirect_uri']) && $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$client_id]['redirect_uri']!='') {
                $redirect_uri=$GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$client_id]['redirect_uri'];
                $temp=explode('/',$redirect_uri);
                $redirect_uri=$temp[0].'//'.$temp[2];
                $this->tpl->assign("redirect_uri", $redirect_uri);
            }
            $this->template = "openapi/views/user/maintain_h5.html";
            $this->tpl->assign("page_title", "系统维护中");
            $this->tpl->assign("content", app_conf("ID5_MAINTEN_MSG"));
            return;
        }

        $bankResult = (new \core\service\BankService())->getFastPayBanks();
        $bankList = @$bankResult['data'];

        $this->template = $this->getCustomTpl("openapi/views/user/combine_regist.html", 'combineRegist');
        if(isset($this->clientConf['js']['combineRegist'])){
            $fzjs = $this->clientConf['js']['combineRegist'].'?'.date("dH");
            $this->tpl->assign('fzjs', $fzjs);
        }
        $this->tpl->assign('userInfo', $userInfo);
        $this->tpl->assign('openId', $data['openId']);
        $this->tpl->assign('bankList', json_encode($bankList));
        $this->tpl->assign('returnUrl', $backUrl);
        $this->tpl->assign('asgn', $asgn);
        $this->tpl->assign('hide_micropayment', $hide_micropayment);
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

}

