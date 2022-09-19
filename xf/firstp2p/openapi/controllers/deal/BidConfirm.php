<?php

/**
 *
 * @abstract   openapi投资确认接口
 * @date 2015-05-27
 * @author xiaoan <xiaoan@ucfgroup.com>
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDealBidConfirm;
use libs\utils\Aes;
use core\service\UserRiskTestService;
use libs\payment\supervision\Supervision;

use core\dao\EnterpriseModel;
use core\dao\DealModel;

/**
 * 投资确认接口
 *
 * Class Detail
 * @package openapi\controllers\deals
 */
class BidConfirm extends BaseAction {


    private $_forbid_deal_status = array(2, 3, 4, 5);

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'openId' => array("filter" => "string", "message" => "openId is error"),
            'oauth_token' => array("filter" => "required", "message" => "oauth_token is required"),
            'id' => array("filter" => "int", "message" => "id is required"),
            'ecid' => array("filter" => "string", "message" => "ecid is required"),
            'money' => array('filter'=>array($this, "valid_money"), 'message'=> 'ERR_MONEY_FORMAT'),
        );
        /*
         * 与父类系统鉴权验证规则合并
         */
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $user_info = $this->getUserByAccessToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        //p2p标仅仅允许投资户投资
//        $deal = DealModel::instance()->find($data['id']);
//
//        if($this->rpc->local('DealService\isP2pPath', array($deal))){
//            if(!$this->rpc->local('UserService\allowAccountLoan', array($user_info['user_purpose']))){
//                $this->setErr('ERR_INVESTMENT_USER_CAN_BID', $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
//                return false;
//            }
//        }
//
        //强制风险评测
        $needForceAssess = 0;
        $limitMoneyData = array();
        if($user_info->idcardPassed == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($user_info->userId)));
            $needForceAssess = $riskData['needForceAssess'];
            //$limitMoneyData = !empty($riskData['limitMoneyData']) ? $riskData['limitMoneyData'] : array();
        }

        if(isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $data['id'] = Aes::decryptForDeal($data['ecid']);
        } else {
            $data['id'] = intval($data['id']);
        }

        if (!deal_belong_current_site($data['id'])) {
            $this->setErr('2005','站点来源错误');
            return false;
        }

        $request = new RequestDealBidConfirm();
        $request->setId($data['id']);
        $request->setMoney((string) $data['money']);
        $request->setUserId(intval($user_info->userId));
        $request->setUserMoney($user_info->money);
        $request->setSiteId($this->getSiteId());
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'bidConfirm',
            'args' => $request
        ));
        if ($response->resCode){
            $this->errorCode = -1;
            $this->errorMsg = "get deal failed";
            return false;
        }
        $res_data = $response->toArray();
        if($res_data['dealType'] != 0){
            $limitMoneyData = array();
        }
        $res_data['is_full'] = 0;
        if (in_array($res_data['stats'], $this->_forbid_deal_status)) {
            $res_data['is_full'] = 1;
        }
        $res_data['djsRiskTestStatus'] = UserRiskTestService::getTestResult($user_info->userId);
        $res_data['income_base_rate'] = $res_data['otherParams']['income_base_rate'];//年化收益基本利率
        $res_data['expected_repay_start_time'] = $res_data['otherParams']['expected_repay_start_time']; // 预计起息日
        unset($res_data['otherParams']);
        $res_data['needForceAssess'] = $needForceAssess;
        //$res_data['limitMoneyData'] = $limitMoneyData;

        $res_data['wxMoney'] = $user_info->money;
        //是否是存管升级用户
        $isUpgradeAccount = $this->rpc->local('SupervisionService\isUpgradeAccount', array(intval($user_info->userId)));
        $res_data['isUpgradeAccount'] = intval($isUpgradeAccount);
        $res_data['status'] = $res_data['svInfo']['status'];
        if($res_data['svInfo']['status']){
            $res_data['isSvUser'] = $res_data['svInfo']['isSvUser'];
            $res_data['isFreePayment'] = $res_data['svInfo']['isFreePayment'];
            if($res_data['isSvUser']){
                $res_data['svBalance'] = isset($res_data['svInfo']['svBalance']) ? $res_data['svInfo']['svBalance'] : 0;
                $res_data['svFreeze'] = isset($res_data['svInfo']['svFreeze']) ? $res_data['svInfo']['svFreeze'] : 0;
                $res_data['svMoney'] = isset($res_data['svInfo']['svMoney']) ? $res_data['svInfo']['svMoney'] : 0;
            }
        }

        //存管服务降级
        $res_data['isServiceDown'] = Supervision::isServiceDown() ? 1 : 0;
        $this->json_data = $res_data;
        return true;
    }
    public function valid_money($value) {
        if ($value == null) {
            return true;
        }
        if (floatval($value) == 0) {
            return false;
        }
        if (!preg_match("/^[-]{0,1}[\d]*(\.\d{1,2})?$/", $value)) {
            return false;
        }
        return true;
    }

}
