<?php
namespace task\controllers\makeloans;

use core\dao\deal\DealExtModel;
use core\enum\DealExtEnum;
use task\controllers\BaseAction;
use core\service\deal\DealService;
use core\service\makeloans\MakeLoansMsgService;
use libs\utils\Logger;

/**
 * 放款后通知邮件、短信等通知
 */
class Notify extends BaseAction {
    public function invoke() {
        return true;
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            $dealId = $params['dealId'];
            $mlmsService = new MakeLoansMsgService();
            //发送短信
            $mlmsService->sendSms($dealId);
            //发送站内信
            $mlmsService->sendMsg($dealId);

        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
