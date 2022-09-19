<?php
namespace task\controllers\dealfull;

use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;
use core\dao\deal\DealModel;

/**
 * 满标后发送
 * Class SendNotify
 */
class SendNoticeToOperator extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            $dealId = $params['dealId'];
            $deal = DealModel::instance()->find($dealId);
            // TODO 发送微信、短信
            $sendRes = send_full_deal_message_to_operator($deal);
            if($sendRes){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . "微信、短信发送成功 params:".json_encode($params));
            }
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg= $ex->getMessage();
        }
    }
}
