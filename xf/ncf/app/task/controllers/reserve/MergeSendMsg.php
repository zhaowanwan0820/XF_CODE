<?php
namespace task\controllers\reserve;

use core\service\reserve\ReserveMsgService;
use libs\utils\Logger;
use task\controllers\BaseAction;

/**
 * 随鑫约合并发送信息
 * Class MergeSendMsg
 * @package task\controllers\dealcreate
 */
class MergeSendMsg extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info("Task MergeSendMsg params ".json_encode($params));

            $userId = $params['userId'];
            $startTime = $params['startTime'];
            $endTime = $params['endTime'];
            $type = $params['type'];
            if(!$userId || !$startTime || !$endTime || !$type){
                throw new \Exception('参数错误');
            }

            $msgService = new ReserveMsgService();
            $msgService->mergeSendMsg($userId, $startTime, $endTime, $type);

        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }

    }
}
