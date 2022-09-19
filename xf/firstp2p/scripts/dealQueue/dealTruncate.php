<?php

require_once(dirname(__FILE__) . '/../../app/init.php');

use core\service\DealQueueService;
use core\service\GoldDealService;
use libs\utils\Logger;
set_time_limit(0);

class dealTruncate{

    public function run(){
        $opts  = getopt("s:");
        $serviceType    = isset($opts['s'])? $opts['s']:'';
        $dealQueueService = new DealQueueService(0,$serviceType);
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,'start')));
        $result=$dealQueueService->isDealSellOut();
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,'queueAndDealInfo:'.json_encode($result))));
        $goldDealService=new GoldDealService();
        $key='GOLD_DEAL_QUEUE_TRUNCATE';
        $i=0;

        if(!empty($result)){
            do{
                try{
                    foreach($result as $k => $v){
                        $updateResult=false;
                        $updateResult=$goldDealService->updateDealAmountAndStatus($v['deal_id']);
                        $content='您运营的'.$v['queue_name'].'队列已于'.date("Y-m-d H:i").'截标完毕,队列状态无效。';
                        $res=$dealQueueService->sendMessage($content,$key);
                    }
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,'截标完毕',json_encode($result))));
                }catch (\Exception $e){
                    $i++;
                    if($i==3){
                        $content='您运营的队列'.$v['queue_name'].'截标失败';
                        $res=$dealQueueService->sendMessage($content,$key);
                    }
                    \libs\utils\Alarm::push('gold_exception',$e->getMessage(),'队列信息:'.json_encode($result).',重试第'.$i.'次');
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,'第'.$i.'次重试','error:'.$e->getMessage())));
                }
            }while(!$updateResult && $i<3);
        }
    }
}
$dealTruncate = new dealTruncate();
$dealTruncate->run();
exit;
