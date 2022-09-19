<?php

namespace openapi\controllers\deal;

use core\enum\DealEnum;
use core\enum\DealExtEnum;
use core\enum\ThirdpartyDkEnum;
use core\enum\DealRepayEnum;
use core\enum\UserAccountEnum;
use core\service\repay\P2pDealRepayService;
use libs\web\Form;
use libs\utils\Logger;
use openapi\controllers\BaseAction;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\dao\repay\DealRepayModel;
use core\service\repay\DealPrepayService;
use core\service\thirdparty\ThirdpartyDkService;
use NCFGroup\Common\Library\Idworker;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\service\deal\DealService;
use core\service\account\AccountService;
use core\dao\supervision\SupervisionWithdrawAuditModel;

class Prepay extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'out_order_id'   => ['filter' => 'required', 'message' => "out_order_id is error"],
            'approve_number' => ['filter' => 'required', 'message' => "approve_number is error"],
            'account_type'   => ['filter' => 'int', 'option' => array('optional' => true)],
            'notify_url'     => ['filter' => 'string', 'option' => array('optional' => true)],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $outerOrderId   = trim($data['out_order_id']);
        $approveNumber  = trim($data['approve_number']);
        $repayType      = intval($data['account_type']);
        $repayId        = trim($data['repay_id']);
        $notifyUrl      = trim($data['notify_url']);

        if($repayType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误,不接收间接代偿还款');
            return false;
        }
        if($repayType == DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误,不接收代扣还款');
            return false;
        }
        $dealService = new DealService();
        $dealInfo  = $dealService->getDealByApproveNumber($approveNumber);
        if(empty($dealInfo)){
            $this->setErr("ERR_DEAL_FIND_NULL");
            return false;
        }
        $dealId = $dealInfo['id'];

        if ($dealInfo['is_during_repay'] == 1) {
            $this->setErr('ERR_REPAY_DEAL_REPAYING');
            return false;
        }
        if($dealInfo['deal_status'] != DealEnum::$DEAL_STATUS['repaying']){
            $this->setErr("ERR_REPAY_DEAL_STATUS");
            return false;
        }

        $thirdpartyDkService = new ThirdpartyDkService();
        $thirdpartyDkInfo =  $thirdpartyDkService->getThirdPartyByOutOrderId($outerOrderId,$data['client_id']);
        if (isset($thirdpartyDkInfo['outer_order_id'])) { // 如果有记录 则直接返回
            // 类型要一致，以免使用划扣订单号来进行还款
            if($thirdpartyDkInfo['type'] != ThirdpartyDkEnum::SERVICE_TYPE_PREPAY){
                $this->setErr('ERR_USED_ORDER');
                return false;
            }
            // 已有的订单中的参数需要匹配请求中的参数
            if($thirdpartyDkInfo['deal_id'] != $dealId){
                $this->setErr('ERR_USED_ORDER');
                return false;
            }
            $this->json_data = ['status' => $thirdpartyDkInfo['status']];
            return true;
        }

        $repayInfo = DealRepayModel::instance()->getNextRepayByDealId($dealInfo['id']);

        if (empty($repayInfo)) {
            $this->setErr("ERR_DEAL_REPAY_INFO");
            return false;
        }
        // 借款人还款 校验余额是否足额还款
        if ($repayType == DealRepayEnum::DEAL_REPAY_TYPE_SELF) {
            $repayData = $dealService->dealRepayTrial($dealInfo, $repayInfo->id, date('Y-m-d'), $repayType);
            $userBankMoney = AccountService::getAccountMoney($dealInfo['user_id'], UserAccountEnum::ACCOUNT_FINANCE);
            if ((bcsub($userBankMoney['money'], $repayData['total_repay'], 2)) < 0) {
                $this->setErr('ERR_NOT_ENOUGH_MONEY');
                return false;
            }
        }

        try{
            $GLOBALS['db']->startTrans();
            $thirdParams = array(
                'accountType' => $repayType
            ); //订单参数

            $now = time();
            $orderId = Idworker::instance()->getId();
            $thirdpartyDkModel = new ThirdpartyDkModel();

            $thirdpartyDkModel->outer_order_id = $outerOrderId;
            $thirdpartyDkModel->order_id       = $orderId;
            $thirdpartyDkModel->deal_id        = $dealId;
            $thirdpartyDkModel->repay_id       = $repayId;
            $thirdpartyDkModel->client_id      = $data['client_id'];
            $thirdpartyDkModel->status         = ThirdpartyDkEnum::REQUEST_STATUS_WATTING;
            $thirdpartyDkModel->notify_url     = $data['notify_url'];
            $thirdpartyDkModel->create_time    = $now;
            $thirdpartyDkModel->update_time    = $now;
            $thirdpartyDkModel->type           = ThirdpartyDkEnum::SERVICE_TYPE_PREPAY;
            $thirdpartyDkModel->params         = addslashes(json_encode($thirdParams));

            if ($thirdpartyDkModel->insert() === false) {
                throw new \Exception(sprintf('insert fail: %s', 'ThirdpartyDk'));
            }

            $prepayRes = (new DealPrepayService())->prepayPipeline($dealId, date("Y-m-d"), $repayType, [], $orderId);
            if (!$prepayRes) {
                throw new \Exception("提前还款失败");
            }

            $deal = DealModel::instance()->find($dealInfo['id']);
            $dealRes = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
            if(!$dealRes) {
                throw new \Exception("改变标的还款状态失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "提前还款", $e->getMessage(), json_encode($data))));
            $this->setErr('ERR_DK_REQUEST', $e->getMessage());
            return false;
        }

        $this->json_data = ['status' => ThirdpartyDkEnum::REQUEST_STATUS_WATTING];
        return true;
    }

}
