<?php

/**
 * @abstract openapi  身份认证接口
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 * @date 2015-04-27
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\UserService;
use core\service\UserVerifyService;

//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/**
 * 个人身份认证加绑卡
 *
 * Class DoCombineRegist
 * @package openapi\controllers\account
 */
class DoCombineRegist extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'name' => array("filter" => "required", "message" => "name is required"),
            'idno' => array("filter" => "required", "message" => "idno is required"),
//            'bankName' => array("filter" => "required", "message" => "bankName is required"),
//            'bankCardNo' => array("filter" => "required", "message" => "bankCardNo is required"),
            'openId' => array("filter" => "string"),
            'asgn' => array("filter" => "required", "message" => "asgn is required"),
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
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->setErr('ERR_MANUAL_REASON', \libs\utils\PaymentApi::maintainMessage());
            return false;
        }

        $data = $this->form->data;
        $combineData = array();
        $combineData['cardNo'] = htmlspecialchars(trim($data['idno']));
        $combineData['realName'] = htmlspecialchars(trim($data['name']));
        //$combineData['cardName'] = htmlspecialchars(trim($data['name']));
        //$combineData['bankName'] = htmlspecialchars(trim($data['bankName']));
        //$combineData['bankCardNo'] = htmlspecialchars(trim($data['bankCardNo']));
        $combineData['source'] = 2;
        if (!preg_match("/^[\x80-\xff]{6,30}$/", $combineData['realName'])) {
            $this->setErr('ERR_MANUAL_REASON', '姓名只支持中文');
            return false;
        }

        $this->form->data['oauth_token'] = $token;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 已经完成身份认证
        if (!empty($userInfo->idno)) {
            return true;
        }
        //身份认证维护页
        //if (intval(app_conf("ID5_VALID")) === 0) {
        //    $this->template = "openapi/views/user/maintain_h5.html";
        //    $this->tpl->assign("page_title", "系统维护中");
        //    $this->tpl->assign("content", app_conf("ID5_MAINTEN_MSG"));
        //    return;
        //}

        try {
            $paymentService = new \core\service\PaymentService();
            $combineData = $paymentService->filterXss($combineData);
            UserVerifyService::PcH5RealNameAuth($userInfo->userId, $userInfo->userType, $userInfo->userPurpose);
            $result = $paymentService->register($userInfo->userId, $combineData);
            if ($result != \core\service\PaymentService::REGISTER_SUCCESS) {
                throw new \Exception($paymentService->getLastError());
            }
            // 用户签署网信超级账户免密协议
            $this->rpc->local('UserService\signWxFreepayment', array($userInfo->userId));
        } catch (\Exception $e) {
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            \libs\utils\PaymentApi::log('CombineRegist fail'.$e->getMessage().":".json_encode($combineData));
            return false;
        }
        return true;
    }

    public function authCheck(){
        return true;
    }

}
