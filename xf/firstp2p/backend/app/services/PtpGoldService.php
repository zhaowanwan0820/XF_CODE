<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Gold\RequestCommon;
use core\event\Gold\GoldDealChangeInterestMsgEvent;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\GoldService;


class PtpGoldService extends ServiceBase {

    public function isWhite(SimpleRequestBase $request) {
        $userId = $request->getParam('userId');

        $goldService = new GoldService;
        $res = $goldService->isWhite($userId);
        $response = new ResponseBase();
        $response->data = $res ? 1 : 0;
        return $response;
    }

    public function getRedeemHolidays(SimpleRequestBase $request){

        \FP::import("libs.common.dict");

        return \dict::get('REDEEM_HOLIDAYS');
    }
    /**
     * 黄金发送短信和消息
     * @param
     */
    public function sendInterestMsg(RequestCommon $request){
        $vars = $request->getVars();
        $dealId = intval($vars['deal_id']);
        $response = new ResponseBase;
        if (empty($dealId)) {
            $response->resCode = RPCErrorCode::REQUEST_PARAMS_ERROR;
           return $response;
        }
        // 异步发送合并投资短信
        $obj = new GTaskService();
        $event = new GoldDealChangeInterestMsgEvent($dealId);
        $obj->doBackground($event, 1);

        $response->resCode = RPCErrorCode::SUCCESS;

        return $response;
    }
}
