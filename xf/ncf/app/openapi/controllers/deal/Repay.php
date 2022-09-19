<?php

namespace openapi\controllers\deal;

use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\ThirdpartyDkEnum;
use core\enum\UserAccountEnum;
use core\enum\DealRepayEnum;
use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use core\dao\jobs\JobsModel;
use core\dao\deal\DealModel;
use core\service\deal\P2pIdempotentService;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\service\deal\DealService;
use core\service\repay\DealRepayService;
use core\service\thirdparty\ThirdpartyDkService;
use core\service\account\AccountService;
use NCFGroup\Common\Library\Idworker;

class Repay extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = [
            'out_order_id'   => ['filter' => 'required', 'message' => "out_order_id is error"],
            'approve_number' => ['filter' => 'required', 'message' => "approve_number is error"],
            'repay_id'       => ['filter' => 'required', 'message' => "repay_id is error"],
            'account_type'   => ['filter' => 'int', 'option' => array('optional' => true)],
            'notify_url'     => ['filter' => 'string', 'option' => array('optional' => true)],
        ];

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data          = $this->form->data;
        $outerOrderId  = trim($data['out_order_id']);
        $approveNumber = trim($data['approve_number']);
        $repayId       = trim($data['repay_id']);
        $repayType     = intval($data['account_type']);
        $notifyUrl     = trim($data['notify_url']);

        if($repayType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误,不接收间接代偿还款');
            return false;
        }
        if($repayType == DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误,不接收代扣还款');
            return false;
        }

        $dealInfo = (new DealService())->getDealByApproveNumber($approveNumber);
        $repayInfo = (new DealRepayService())->getInfoById($repayId);

        if ($dealInfo['id'] != $repayInfo['deal_id']) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误');
            return false;
        }

        if ($repayInfo['status'] == 1) {
            // 本期已还清
            $this->setErr("ERR_REPAYED");
            return true;
        }

        if ($dealInfo['is_during_repay'] == 1) {
            $this->setErr('ERR_REPAY_DEAL_REPAYING');
            return false;
        }

        $thirdpartyDkService = new ThirdpartyDkService();
        $thirdpartyDkInfo =  $thirdpartyDkService->getThirdPartyByOutOrderId($outerOrderId, $data['client_id']);
        if (isset($thirdpartyDkInfo['outer_order_id'])) { // 如果有记录 则直接返回
            // 类型要一致，以免使用划扣订单号来进行还款
            if($thirdpartyDkInfo['type'] != ThirdpartyDkEnum::SERVICE_TYPE_REPAY){
                $this->setErr('ERR_USED_ORDER');
                return false;
            }
            // 已有的订单中参数需要相同
            if($thirdpartyDkInfo['repay_id'] != $repayId){
                $this->setErr('ERR_USED_ORDER');
                return false;
            }
            $this->json_data = ['status' => $thirdpartyDkInfo['status']];
            return true;
        }
        // 借款人还款 校验余额是否足额还款
        if ($repayType == DealRepayEnum::DEAL_REPAY_TYPE_SELF){
            $userBankMoney = AccountService::getAccountMoney($dealInfo['user_id'], UserAccountEnum::ACCOUNT_FINANCE);
            if ((bcsub($userBankMoney['money'], $repayInfo['repay_money'], 2)) < 0) {
                $this->setErr('ERR_NOT_ENOUGH_MONEY');
                return false;
            }
        }

        try{
            $GLOBALS['db']->startTrans();
            $thirdParams = array(
                'accountType'   => $repayType,
                'approveNumber' => $approveNumber
            ); //订单参数

            $now = time();
            $orderId = Idworker::instance()->getId();
            $thirdpartyDkModel = new ThirdpartyDkModel();

            $thirdpartyDkModel->outer_order_id = $outerOrderId;
            $thirdpartyDkModel->order_id       = $orderId;
            $thirdpartyDkModel->deal_id        = $dealInfo['id'];
            $thirdpartyDkModel->repay_id       = $repayId;
            $thirdpartyDkModel->client_id      = $data['client_id'];
            $thirdpartyDkModel->status         = ThirdpartyDkEnum::REQUEST_STATUS_WATTING;
            $thirdpartyDkModel->notify_url     = $data['notify_url'];
            $thirdpartyDkModel->create_time    = $now;
            $thirdpartyDkModel->update_time    = $now;
            $thirdpartyDkModel->type           = ThirdpartyDkEnum::SERVICE_TYPE_REPAY;
            $thirdpartyDkModel->params         = addslashes(json_encode($thirdParams));

            if ($thirdpartyDkModel->insert() === false) {
                throw new \Exception(sprintf('insert fail: %s', 'ThirdpartyDk'));
            }

            //插入jobs
            $jobsParams = [
                'deal_repay_id' => $repayId,
                'ignore_impose_money' => true,
                'admin' => ['adm_name' => 'system', 'adm_id' => 0],
                'negative'=>0,
                'repayType'=>$repayType,
                'submitUid' => 0,
                'auditType' => 3
            ];

            $jobsModel = new JobsModel();
            $function = '\core\service\repay\P2pDealRepayService::dealRepayRequest';
            $param = ['orderId' => $orderId, 'dealRepayId' => $repayId, 'repayType' => $repayType, 'params' => $jobsParams];
            $jobsModel->priority = JobsEnum::PRIORITY_P2P_REPAY_REQUEST;

            $jobsRes = $jobsModel->addJob($function, $param);
            if ($jobsRes === false) {
                throw new \Exception("加入jobs失败");
            }

            $deal = DealModel::instance()->find($dealInfo['id']);
            $dealRes = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
            if(!$dealRes) {
                throw new \Exception("改变标的还款状态失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", [__CLASS__, __FUNCTION__, __LINE__, "正常还款", $e->getMessage(), json_encode($data)]));
            $this->setErr('ERR_DK_REQUEST', $e->getMessage());
            return false;
        }

        $this->json_data = ['status' => ThirdpartyDkEnum::REQUEST_STATUS_WATTING];
        return true;
    }

}
