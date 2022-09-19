<?php
/**
 * 资产端协议支付签约申请-重发短信
 * Index.php
 */

namespace web\controllers\banksign;

use libs\utils\Logger;
use core\enum\SupervisionEnum;
use web\controllers\banksign\BkBaseAction;
use core\service\supervision\SupervisionFinanceService;

class Resend extends BkBaseAction {

    public function invoke() {
        $orderInfo = $this->orderInfo;
        $s = new SupervisionFinanceService();
        $res = $s->signResendMessage(array('orderId'=>$orderInfo['order_id']));

        $return = array('status' => 0,'err_msg' => '签约申请成功');
        if($res['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            $return['status'] = $res['respCode'];
            $return['err_msg'] = $res['respMsg'];
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "协议支付-短信重发", 'orderId:'.$this->orderInfo['order_id']." res:".json_encode($res))));
        return ajax_return($return);
    }

}