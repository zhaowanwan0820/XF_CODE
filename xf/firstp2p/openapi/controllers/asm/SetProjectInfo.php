<?php

/**
 * @abstract openapi  项目信息提交接口
 * @author yutao <yutao@ucfgroup.com>
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;

/**
 * 项目信息提交接口
 *
 * Class SetProjectInfo
 * @package openapi\controllers\asm
 */
class SetProjectInfo extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
            "name" => array("filter" => "required", "message" => "name is required"),
            "real_name" => array("filter" => "required", "message" => "real_name is required"),
            "idno" => array("filter" => "required", "message" => "idno is required"),
//            "mobile" => array("filter" => "required", "message" => "mobile is required"),
            "borrow_amount" => array("filter" => "required", "message" => "borrow_amount is required"),
            "loan_type" => array("filter" => "int", "message" => "loan_type is not int type"),
            "repay_period" => array("filter" => "int", "message" => "repay_period is not int type"),
            "rate" => array("filter" => "float", "message" => "rate is not float type"),
            "credit" => array("filter" => "required", "message" => "credit is required"),
            "project_info_url" => array("filter" => "required", "message" => "project_info_url is required"),
            "project_extrainfo_url" => array("filter" => "string", "message" => "project_extrainfo_url is not string type"),
            "deal_type" => array("filter" => "int", "message" => "deal_type is not int type"),
            "lock_period" => array("filter" => "int", "message" => "lock_period is not int type"),
            "redemption_period" => array("filter" => "int", "message" => "redemption_period is not int type"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;

        if (empty($params['approve_number'])) {
            $this->setErr("ERR_PARAMS_ERROR", "approve_number不能为空");
            return false;
        }
        /**
         * 验证clientID 和 access_token 合法性
         */
        $clientInfo = $this->getClientIdByAccessToken();
        if (!$clientInfo || $clientInfo['client_id'] !== $params['client_id']) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        /**
         * 验证借款人信息
         */
        $request = new ProtoUser();
        try {
            $request->setIdno($params['idno']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $userResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getUserInfoByIdno',
            'args' => $request,
        ));
        if ($userResponse->resCode) {
            $this->errorCode = 1;
            $this->errorMsg = "借款人不存在";
//            $this->json_data_err = array("userId" => "", "userName" => "", "realName" => "", "sex" => "");
            return false;
        }
        if ($userResponse->realName != $params['real_name']) {
            $this->errorCode = 2;
            $this->errorMsg = "借款人信息不一致";
            return false;
        }

        $borrow_amount = doubleval($params['borrow_amount']);
        if (!$borrow_amount) {
            $this->setErr("ERR_PARAMS_ERROR", "borrow_amount is not double");
            return false;
        }
        $credit = doubleval($params['credit']);
        if (!$credit) {
            $this->setErr("ERR_PARAMS_ERROR", "credit is not double");
            return false;
        }
        $request = new \NCFGroup\Protos\Ptp\ProtoDealProject();
        try {
            $request->setApproveNumber($params['approve_number']);
            $request->setUserId($userResponse->userId);
            $request->setBorrowAmount($borrow_amount);
            $request->setCredit($credit);
            $request->setLoanType($params['loan_type']);
            $request->setName($params['name']);
            $request->setRate($params['rate']);
            $request->setRepayReriod($params['repay_period']);
            $request->setProjectInfoUrl($params['project_info_url']);
            $request->setProjectExtrainfoUrl($params['project_extrainfo_url']);
            $request->setDealType($params['deal_type']);
            $request->setLockPeriod($params['lock_period']);
            $request->setRedemptionPeriod($params['redemption_period']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        if ($params['deal_type'] == 1) {
            $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpDealProject',
                'method' => 'addDealProjectCompound',
                'args' => $request
            ));
        } else {
            $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpDealProject',
                'method' => 'addDealProject',
                'args' => $request
            ));
        }


        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "insert dealProject failed";
            return false;
        }

        $result['project_id'] = $response->projectId;
        $result['approve_number'] = $response->approveNumber;
        $this->json_data = $result;
    }

}
