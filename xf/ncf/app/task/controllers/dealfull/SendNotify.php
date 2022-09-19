<?php
namespace task\controllers\dealfull;

use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;
use core\dao\deal\DealModel;

/**
 * 满标后发送 开始发邮件和短信等通知
 * Class SendNotify
 */
class SendNotify extends BaseAction {

    public function invoke() {
        return true;
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            $dealId = $params['dealId'];
            $deal = DealModel::instance()->find($dealId);
            if( empty($deal) || intval($deal['deal_status']) != 2 ){
                Logger::error(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 不满足满标信息发送条件 dealId:".$dealId." deal_status:".$deal['deal_status']);
                throw new \Exception('满标发送消息失败，标信息不存在');
            }
            // TODO 发送邮件、短信
            $sendRes = send_full_failed_deal_message($deal,'full');
            if($sendRes){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ",邮件、短信发送成功 params:".json_encode($params));
            }

            $log = array(
                "type" => "deal",
                "act" => "full",
                "is_succ" => 1,
                "id" => $deal['id'],
                "name" => $deal['name'],
            );
            Logger::info(implode(" | ", $log));
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg= $ex->getMessage();
        }
    }
}
