<?php

namespace openapi\controllers\deal;

use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use core\dao\JobsModel;
use core\service\P2pIdempotentService;
use core\dao\ThirdpartyDkModel;
use core\service\DealService;
use core\service\DealRepayService;
use NCFGroup\Common\Library\Idworker;
use core\dao\DealModel;

class Repay extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = [
            'out_order_id'   => ['filter' => 'required', 'message' => "out_order_id is error"],
            'approve_number' => ['filter' => 'required', 'message' => "approve_number is error"],
            'repay_id'       => ['filter' => 'required', 'message' => "repay_id is error"],
            'account_type'   => ['filter' => 'int', 'option' => array('optional' => true)],
            'notify_url'   => ['filter' => 'string', 'option' => array('optional' => true)],
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

        $dealInfo = (new DealService())->getDealByApproveNumber($approveNumber);
        $repayInfo = (new DealRepayService())->getInfoById($repayId);

        if ($dealInfo['id'] != $repayInfo['deal_id']) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误');
            return false;
        }

        if ($repayInfo['status'] == 1) {
            $this->json_data = ['status' => 2];
            return true;
        }

        if ($dealInfo['is_during_repay'] == 1) {
            $this->setErr('ERR_REPAY_DEAL_REPAYING');
            return false;
        }

        $thirdpartyDkInfo =  $this->rpc->local('ThirdpartyDkService\getThirdPartyByOutOrderId', [$outerOrderId, $data['client_id']]);
        if (isset($thirdpartyDkInfo['outer_order_id'])) { // 如果有记录 则直接返回
            $this->json_data = ['status' => $thirdpartyDkInfo['status']];
            return true;
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
            $thirdpartyDkModel->status         = ThirdpartyDkModel::REQUEST_STATUS_WATTING;
            $thirdpartyDkModel->notify_url     = $data['notify_url'];
            $thirdpartyDkModel->create_time    = $now;
            $thirdpartyDkModel->update_time    = $now;
            $thirdpartyDkModel->type           = ThirdpartyDkModel::SERVICE_TYPE_REPAY;
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
            $function = '\core\service\P2pDealRepayService::dealRepayRequest';
            $param = ['orderId' => $orderId, 'dealRepayId' => $repayId, 'repayType' => $repayType, 'params' => $jobsParams];
            $jobsModel->priority = JobsModel::PRIORITY_P2P_REPAY_REQUEST;

            $jobsRes = $jobsModel->addJob($function, $param);
            if ($jobsRes === false) {
                throw new \Exception("加入jobs失败");
            }

            $deal = DealModel::instance()->find($dealInfo['id']);
            $dealRes = $deal->changeRepayStatus(DealModel::DURING_REPAY);
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

        $this->json_data = ['status' => ThirdpartyDkModel::REQUEST_STATUS_WATTING];
        return true;
    }

}
