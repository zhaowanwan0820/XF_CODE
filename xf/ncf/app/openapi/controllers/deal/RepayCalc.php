<?php

namespace openapi\controllers\deal;

use core\dao\UserBankcardModel;
use core\service\deal\DealDkService;
use core\service\repay\P2pDealRepayService;
use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use core\service\deal\DealService;
use core\dao\repay\DealRepayModel;

class RepayCalc extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'approve_number' => ['filter' => 'required', 'message' => "deal_id is error"],
            'repay_time' => ['filter' => 'required', 'message' => "repay_time is error"],
            'repay_type'   => ['filter' => 'int', 'option' => array('optional' => true)],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $approveNumber = $data['approve_number'];
        $repayTime = $data['repay_time'];
        $repayType = $data['repay_type'];
        if ($repayType != 1 && $repayType != 2) {
            $repayType = 1;
        }

        /*$checkCounts = Block::check('DSD_REPAYTRIAL_DOWN_MINUTE','dsd_repaytrial_down_minute');
        if ($checkCounts === false) {
            $this->setErr('ERR_MANUAL_REASON','请不要频繁发送请求');
            return false;
        }*/

        try{
            $dealInfo  = (new DealService())->getDealByApproveNumber($approveNumber);
            if (!$dealInfo) {
                throw new \Exception("标的信息不存在");
            }

            $repayInfo = DealRepayModel::instance()->getNextRepayByDealId($dealInfo['id']);
            if (!$repayInfo) {
                throw new \Exception("标的还款信息不存在");
            }

            $dealService = new DealService();
            $repayData = $dealService->dealRepayTrial($dealInfo,$repayInfo->id, date('Y-m-d', strtotime($repayTime)), $repayType);
        }catch (\Exception $e){
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",deal_id:".$dealInfo['id'].", repayData:".$repayTime);
            $this->setErr("ERR_REPAY_TRIAL_ERR", $e->getMessage());
            return false;
        }
        $repayData['approve_number'] = $approveNumber;
        $this->json_data = $repayData;
        return true;
    }
}

