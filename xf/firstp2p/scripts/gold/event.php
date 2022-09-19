<?php


require_once(dirname(__FILE__) . '/../../app/init.php');

FP::import("libs.utils.logger");
FP::import("libs.common.dict");

set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(E_ALL ^ E_NOTICE);

use core\service\GoldBidEventService;



class event {

    public function run($argv){
        $eventRecordList = array();
        if(isset($argv[1])){
            $recordInfo = $this->getEventRecordById($argv[1]);
            if(!empty($recordInfo)){
                $eventRecordList[] = $recordInfo;
            }
        }else{
            $eventRecordList = $this->getEventRecordList();
        }

        if(!empty($eventRecordList)){
            foreach ($eventRecordList as $eventRecord){
                //发放失败尝试三次
                $i = 0;
                do{
                    $i++;
                    $result = $this->doEvent($eventRecord['user_id'],$eventRecord['gold'],$eventRecord['pay_user_id'],$eventRecord['event_id'],$eventRecord['id'],$eventRecord['order_id']);
                    sleep(1);
                }while ($result == false && $i < 3);
                if($result == false){
                    \libs\utils\Alarm::push('gold_exception', "gold event error,userId {$userId},errMsg:发放黄金失败",$eventRecord);
                }
            }
        }
    }

    private function getEventRecordList(){
        $sql = "select id,event_id,user_id,gold,pay_user_id,order_id from firstp2p_gold_event_record where status = 1";
        $eventRecordList  = $GLOBALS['db']->get_slave()->getAll($sql ,true);
        return $eventRecordList;
    }

    private function getEventRecordById($id){
        $id = intval($id);
        $sql = "select id,event_id,user_id,gold,pay_user_id,order_id from firstp2p_gold_event_record where id='{$id}' and status = 1";
        $eventRecord = $GLOBALS['db']->get_slave()->getRow($sql);
        return $eventRecord;
    }


    private function doEvent($userId,$buyAmount,$wxUserId,$eventId,$recordId,$orderId){
        try {
            $log_info = array(__CLASS__,__FUNCTION__,$userId,$buyAmount,$wxUserId,$eventId,$recordId,$orderId);

            $goldPriceService = (new core\service\GoldService())->getGoldPrice();

            $buyPrice = $goldPriceService['data']['gold_price'];
            if($buyPrice == 0){
                throw new \Exception('获取金价失败');
            }
            $log_info[] = $buyPrice;

            $isAuth =(new core\service\GoldService())->isAuth($userId);
            if($isAuth['data'] == false){
                throw new \Exception('用户未授权');
            }

            $goldBidEventService = new GoldBidEventService($userId,$buyAmount,$buyPrice,'',$orderId,$wxUserId,$eventId);
            $res = $goldBidEventService->doBid();
            if (empty($res)) {
                throw new \Exception('发放黄金失败');
            }

            //更新状态为执行成功
            $res =$GLOBALS['db']->update("firstp2p_gold_event_record", array('status' => 2,'finish_time' => date('Y-m-d H:s:i')), "id=".$recordId);
            if (empty($res)) {
                throw new \Exception('更新记录失败');
            }

        } catch (\Exception $e) {
            Logger::error(implode(" | ", array_merge($log_info, array("errMsg:".$e->getMessage()))));
            \libs\utils\Alarm::push('gold_exception', "gold event error,userId {$userId},errMsg:".$e->getMessage(),array_merge($log_info, array("errMsg:".$e->getMessage())));
            return false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array("success"))));

        return true;
    }

}

$event = new event();
$event->run($argv);
