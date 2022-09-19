<?php

namespace openapi\controllers\deal;

use core\service\P2pDealRepayService;
use libs\web\Form;
use libs\utils\Logger;
use openapi\controllers\BaseAction;
use core\dao\ThirdpartyDkModel;
use core\service\DealPrepayService;
use NCFGroup\Common\Library\Idworker;
use core\dao\DealModel;
use core\service\DealService;

class Prepay extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'out_order_id' => ['filter' => 'required', 'message' => "out_order_id is error"],
            'approve_number'      => ['filter' => 'required', 'message' => "approve_number is error"],
            'account_type'   => ['filter' => 'int', 'option' => array('optional' => true)],
            'notify_url'   => ['filter' => 'string', 'option' => array('optional' => true)],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $outerOrderId = trim($data['out_order_id']);
        $approveNumber= trim($data['approve_number']);
        $repayType    = intval($data['account_type']);
        $notifyUrl    = trim($data['notify_url']);

        $thirdpartyDkInfo =  $this->rpc->local('ThirdpartyDkService\getThirdPartyByOutOrderId', array($outerOrderId,$data['client_id']));
        if (isset($thirdpartyDkInfo['outer_order_id'])) { // 如果有记录 则直接返回
            $this->json_data = ['status' => $thirdpartyDkInfo['status']];
            return true;
        }
        $dealInfo  = (new DealService())->getDealByApproveNumber($approveNumber);
        $dealId = $dealInfo['id'];

        if ($dealInfo['is_during_repay'] == 1) {
            $this->setErr('ERR_REPAY_DEAL_REPAYING');
            return false;
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
            $thirdpartyDkModel->status         = ThirdpartyDkModel::REQUEST_STATUS_WATTING;
            $thirdpartyDkModel->notify_url     = $data['notify_url'];
            $thirdpartyDkModel->create_time    = $now;
            $thirdpartyDkModel->update_time    = $now;
            $thirdpartyDkModel->type           = ThirdpartyDkModel::SERVICE_TYPE_PREPAY;
            $thirdpartyDkModel->params         = addslashes(json_encode($thirdParams));

            if ($thirdpartyDkModel->insert() === false) {
                throw new \Exception(sprintf('insert fail: %s', 'ThirdpartyDk'));
            }

            $prepayRes = (new DealPrepayService())->prepayPipeline($dealId, date("Y-m-d"), $repayType, [], false, $orderId);
            if (!$prepayRes) {
                throw new \Exception("提前还款失败");
            }

            $deal = DealModel::instance()->find($dealInfo['id']);
            $dealRes = $deal->changeRepayStatus(DealModel::DURING_REPAY);
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

        $this->json_data = ['status' => ThirdpartyDkModel::REQUEST_STATUS_WATTING];
        return true;
    }

}
