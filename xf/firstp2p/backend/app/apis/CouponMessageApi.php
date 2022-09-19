<?php

namespace NCFGroup\Ptp\Apis;

use core\service\CouponDealService;
use libs\utils\Logger;

class CouponMessageApi{

    private $result = array('code' => 0, 'msg' => 'success', 'data' =>'');

    public function notify() {
            $input = file_get_contents('php://input');
            Logger::info(implode('|', [__METHOD__, $input]));
            $params = json_decode($input, true);
            $data = json_decode($params['Message'], true);
            $dealId = intval($data['dealId']);
            $payType = intval($data['payType']);
            $payAuto = intval($data['payAuto']);
            $rebateDays = intval($data['rebateDays']);
            $service = new CouponDealService('ncfph');
            $result = $service->handleCoupon($dealId,$payType,$payAuto,$rebateDays);
            if(!$result) {
                $this->result['code'] = -1;
                $this->result['msg'] = '处理失败';
            }

        return $this->formatResult($this->result);
    }

    private function formatResult($res) {
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
