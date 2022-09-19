<?php
namespace task\controllers\dealprepay;

use core\dao\deal\DealModel;
use core\service\user\UserService;
use libs\sms\SmsServer;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\repay\DealRepayMsgService;


/**
 * 提前还款完成--回款短信整合
 * Class SendNotify
 * @package task\controllers\dealcreate
 */
class SendSms extends BaseAction {

    public function invoke() {
        return true;
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $repayId = $params['repayId'];
            $deal = DealModel::instance()->getDealInfo($dealId,true);

            $user = UserService::getUserById($deal['user_id']);
            $arr = array(
                'name' => $deal['name'],
            );
            // SMSSend 提前还款审核通过短信
            // SmsServer::instance()->send($user['mobile'], 'TPL_SMS_LOAN_REPAY_MERGE_NEW', $arr, $user['id']);

            DealRepayMsgService::sendSms($dealId,$repayId);
            Logger::info("Task dealprepay SendSms dealId:{$dealId},repayId:{$repayId}");

        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
