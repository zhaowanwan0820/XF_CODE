<?php
namespace core\service\deal\state;

use core\dao\deal\DealModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\enum\DealEnum;
use core\enum\MsgbusEnum;
use core\service\deal\state\State;
use core\service\dealqueue\DealQueueService;
use core\service\msgbus\MsgbusService;
use core\service\project\ProjectService;
use core\dao\deal\DealLoanTypeModel;
use core\dao\dealqueue\DealQueueModel;
use libs\utils\Logger;


class FullState extends State{

    public function work(StateManager $sm) {

        $deal = $sm->getDeal();

        if($deal->deal_status != DealEnum::DEAL_STATUS_FULL){
            throw new \Exception("当前状态不允许进行满标操作");
        }

        try {
            $GLOBALS['db']->startTrans();
            $QueueModel = DealQueueModel::instance()->getDealQueueByFirstDealId($deal->id);
            if (!empty($QueueModel) && $QueueModel->startDealAutoByQueue() === false) {
                throw new \Exception("满标触发自动上标失败 deaId:".$deal->id);
            }
            $message = array('dealId'=>$deal->id);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_FULL,$message);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . "," .__FUNCTION__ . ",line:" . __LINE__ ."," . $ex->getMessage());
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }
}