<?php
namespace core\service\deal\state;

use core\dao\deal\DealModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\enum\DealEnum;
use core\service\deal\state\State;
use core\service\dealqueue\DealQueueService;
use core\service\project\ProjectService;
use core\service\reserve\ReservationMatchService;
use core\dao\deal\DealLoanTypeModel;
use core\dao\dealqueue\DealQueueModel;
use libs\utils\Logger;


class WaitingState extends State{

    public function work(StateManager $sm) {
        $dp = new ProjectService();
        $dq = new DealQueueService();
        $dealLoanType = DealLoanTypeModel::instance()->find($sm->getDeal()->type_id);
        $dealQueue = DealQueueModel::instance()->getQueueByTypeId($sm->getDeal()->type_id, $sm->getDeal()->repay_time, $sm->getDeal()->loantype);

        $dealId = $sm->getDeal()->id;
        $typeId = $sm->getDeal()->type_id;
        $publishWait = $sm->getDeal()->publish_wait;
        if($publishWait != DealEnum::DEAL_PUBLISH_WAIT_NO){
            Logger::error(__CLASS__, __FUNCTION__, "line:" . __LINE__, "getQueueByTypeId cannot find queue. deal_id:" . $dealId ."type_id:".$typeId,'标的处于未审核状态');
            return false;
        }
        if (!$dealQueue) {
            Logger::error(__CLASS__, __FUNCTION__, "line:" . __LINE__, "getQueueByTypeId cannot find queue. deal_id:" . $dealId ."type_id:".$typeId);
        }

        try {
            Logger::info(__CLASS__, __FUNCTION__, "line:" . __LINE__, " dealId:{$dealId},typeId:{$typeId}");
            $GLOBALS['db']->startTrans();
            // 1、更新项目已上标金额
            $result = $dp->updateProBorrowed($sm->getDeal()->project_id);

            // 2、触发上标队列
            $queueInfo = DealQueueInfoModel::instance()->getQueueByDealId($dealId);

            if(!$queueInfo  && $dealLoanType['auto_start'] == 1 && $dealQueue && $sm->getDeal()->deal_status == DealEnum::DEAL_STATS_WAITING){
                DealQueueModel::instance()->insertDealQueue($dealQueue['id'],$dealId);
            }

            // 检查标的是否符合匹配规则并打TAG
            $reservationMatch = new ReservationMatchService();
            $matchRet = $reservationMatch->checkDealAndSetTag($dealId, true, true, true);
            // 0:匹配成功并打TAG成功    1:未匹配到规则
            if (!isset($matchRet['respCode']) || ($matchRet['respCode'] !== 0 && $matchRet['respCode'] !== 1)) {
                throw new \Exception('检查随心约匹配规则失败');
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            Logger::error(__CLASS__, __FUNCTION__, $dealId,"line:" . __LINE__, $ex->getMessage());
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }
}
