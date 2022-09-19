<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\deal\DealService;
use core\service\user\UserService;

use core\dao\deal\DealModel;
use libs\sms\SmsServer;

class PublishTransferMsgBorrower extends ApiAction
{
    public function invoke()
    {
        $param = $this->getParam();
        $p2pDealId = intval($param['p2pDealId']);
        $dealService = new DealService();
        $p2pDealInfo = $dealService->getDeal($p2pDealId);
        if (empty($p2pDealInfo)) { //参数不对
            return false;
        }

        $dealInfo =  DealModel::instance()->find($p2pDealId);
        $user = UserService::getUserById($p2pDealInfo['user_id']);

        $smsContent = array(
            'deal_name' => $dealInfo['name'],
        );

        $mobile = $user['mobile'];
        $this->json_data = SmsServer::instance('p2pcn')->send($mobile, 'TPL_SMS_PUBLISH_TRANSFER_PUSH', $smsContent);
    }
}
