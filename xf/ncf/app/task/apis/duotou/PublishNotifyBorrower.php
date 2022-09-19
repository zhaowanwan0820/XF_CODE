<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\deal\DealService;
use core\service\msgbox\MsgboxService;
use core\enum\MsgBoxEnum;

class PublishNotifyBorrower extends ApiAction
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
        $mailTitle = '债权转让通知';
        $mailContent = '您借款的“'.$p2pDealInfo['name'].'”项目发生债权转让，您的到期还款届时将直接还款至最新债权持有人，请您到电脑端“我的账户-标的还款计划”处查看该项目的最新债权持有人及相关信息。';
        $msgbox = new MsgboxService();
        $this->json_data = $msgbox->create($p2pDealInfo['user_id'], MsgBoxEnum::TYPE_DUOTOU_LOAN_USER_CHANGED, $mailTitle, $mailContent);
    }
}
