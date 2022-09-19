<?php
namespace core\service\ifapush;

use core\dao\ifapush\IfaLoanModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\service\duotou\DuotouService;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;
use core\enum\MsgbusEnum;


class PushLoan extends PushBase
{
    public $params;
    public $topic;
    public $dbModel;
    public $loadList;
    public $loadInfo;
    public $dealInfo;


    public function __construct($topic,$params){
        $this->topic = $topic;
        $this->params = $params;
        $this->dbModel = new IfaLoanModel();
    }

    public function collectData(){
        if ($this->topic == MsgbusEnum::TOPIC_DEAL_MAKE_LOANS){
            $data = [
                'order_id' => Idworker::instance()->getId(),
                'configId' => $this->loadInfo->id,
                'finClaimId' => $this->loadInfo->id,
                'sourceFinancingCode' => $this->loadInfo->deal_id, // 标的编号
                'sourceProductCode' => $this->loadInfo->deal_id, //标id
                'userIdcard' => $this->getUserIdcard($this->loadInfo->user_id),
                'startTime' => date('Y-m-d H:i:s', $this->dealInfo->repay_start_time + 28800),   //放款时间
            ];
        }elseif($this->topic == MsgbusEnum::TOPIC_DT_TRANSFER){
            $receiveData = $this->getTransferData($this->params);
            $data = [
                'order_id' => Idworker::instance()->getId(),
                'configId' => $receiveData['subOrderId'],
                'finClaimId' => $receiveData['subOrderId'],
                'sourceFinancingCode' => $receiveData['bidId'],
                'sourceProductCode' => $receiveData['bidId'], //标id
                'userIdcard' => $this->getUserIdcard($receiveData['assigneeUserId']),
                'startTime' => date('Y-m-d H:i:s', $receiveData['createTime']),     // 承接时间
                'transferId' => $receiveData['subOrderId'], // 转让编号
            ];
        }

        return $data;
    }

    private function getTransferData($params)
    {
        $request = array(
            'token' => $this->params['orderId'],
            'contractId' => $this->params['contractId'],
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\LoanMappingContract', 'getMappingInvestByToken', $request));
        if (!$response || empty($response['data'])) {
            Logger::error(__CLASS__.','.__FUNCTION__.",智多新拉取债单条承接数据失败 orderId:{$request['token']}");
            throw new \Exception('智多新拉取承接数据失败');
        }
        return $response['data'];
    }


    public function saveLoanData()
    {
        try {
            $GLOBALS['db']->startTrans();
            foreach ($this->loadList as $key => $loadInfo) {
                $this->loadInfo = $loadInfo;
                $res = parent::saveData();
                if (!$res) {
                    throw new \Exception('还款计划信息保存失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            throw $ex;
        }
        return true;
    }

    public function saveDealLoadData()
    {
        try {
            $this->dealInfo = DealModel::instance()->getDealInfo($this->params['dealId']);
            if (empty($this->dealInfo)) {
                throw new \Exception('标的不存在');
            }
            $this->loadList = DealLoadModel::instance()->getDealLoanList($this->params['dealId']);
            if (empty($this->loadList)) {
                throw new \Exception('投资记录不存在');
            }
            $this->saveLoanData();
            return true;
        } catch (\Exception $ex) {
            Logger::error(implode(' | ', array(__FILE__, __FUNCTION__, __LINE__, '失败原因:' . $ex->getMessage())));
            throw $ex;
        }
    }

}
