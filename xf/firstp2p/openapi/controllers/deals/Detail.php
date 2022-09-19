<?php

/**
 *
 * @abstract   openapi标项目详情接口
 * @date 2014-11-28
 * @author yutao <yutao@ucfgroup.com>
 */

namespace openapi\controllers\deals;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDealInfo;
use NCFGroup\Protos\Ptp\ResponseDealInfo;
use libs\utils\Aes;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package openapi\controllers\deals
 */
class Detail extends BaseAction {

    private $_forbid_deal_status;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            /*
             * 标项目ID
             */
            'dealId' => array("filter" => "int", "message" => "dealId is error"),
            'ecid' => array("filter" => "string", "message" => "dealId is error"),
            'dealLoanSize' => array('filter' => 'int'),
        );
        /*
         * 与父类系统鉴权验证规则合并
         */
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        $this->_forbid_deal_status = array(2, 3, 4, 5);
    }

    public function invoke() {
        $data = $this->form->data;

        if(isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $dealId = Aes::decryptForDeal($data['ecid']);
        } else {
            $dealId = intval($this->form->data['dealId']);
        }

        if ($dealId <= 0) {
            $this->setErr("ERR_PARAMS_ERROR", "dealId is error");
            return false;
        }

        if (!deal_belong_current_site($dealId)) {
            $this->setErr('2005','站点来源错误');
            return false;
        }

        $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($data)));
        $userInfo = $this->getUserByAccessToken();

        //强制风险评测
        $needForceAssess = 0;
        if(is_object($userInfo) && !$userInfo->resCode && $userInfo->idcardPassed == 1 && $userInfo->userType == 0){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($userInfo->userId)));
            $needForceAssess = $riskData['needForceAssess'];
        }

        $request = new RequestDealInfo();
        try {
            if (is_object($userInfo) && !$userInfo->resCode) {
                $request->setUserId($userInfo->getUserId());
            }
            $request->setForbidDealStatus($this->_forbid_deal_status);
            $request->setDealId($dealId);
            $dealLoanSize = intval($data['dealLoanSize']);
            if ($dealLoanSize > 0) $request->setDealLoanSize($data['dealLoanSize']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $dealInfoResponse = new ResponseDealInfo();
        $dealInfoResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getDealInfo',
            'args' => $request
        ));

        if ($dealInfoResponse->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get dealInfo failed";
            return false;
        }

        $dealInfo = $dealInfoResponse->toArray();
        if ($dealInfo['dealInfo']['isBxt'] == 1) {
            if (!$userInfo) {
                $this->setErr('ERR_TOKEN_ERROR');
                return false;
            }
        }

        $dealInfo['dealInfo']['ecid'] = Aes::encryptForDeal($dealInfo['dealInfo']['id']);//加密标的Id
        $dealInfo['needForceAssess'] = $needForceAssess;

        $this->json_data = $dealInfo;
        return true;
    }

}
