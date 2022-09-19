<?php
require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\Logger;
use core\service\DealQueueService;
set_time_limit(0);
class dealQueueStart{
    public function run(){
        $opts  = getopt("s:");
        //项目类型
        $serviceType    = isset($opts['s'])? $opts['s']:'';
        //获取开始时间大于当前时间的队列
        $dealQueueService = new DealQueueService(0,$serviceType);
        $queues = $dealQueueService->getQueuesList();
        if(!empty($queues)){
            foreach ($queues as $queue){
                //更新这个队列的首标为进行中
                $dealQueueService = new DealQueueService($queue['id']);
                $result = $dealQueueService->setHeadDealProcess();
                if(empty($result)){
                    \libs\utils\Alarm::push('gold_exception',"更新队列有效，并且上标失败",array('serviceType'=>$serviceType,'queueId'=>$queue['id']));
                    Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,'serviceType:'.$serviceType,'queueId'.$queue['id'],"error:更新队列有效，并且上标失败")));
                }
                Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,'serviceType:'.$serviceType,'queueId'.$queue['id'],"error:更新队列有效，并且上标成功")));
            }
        }
    }
}
$dealQueueStart = new dealQueueStart();
$dealQueueStart->run();
exit;
