<?php
/**
 * 资产端协议支付签约申请-签约确认
 * Index.php
 */

namespace web\controllers\banksign;

use core\dao\deal\OrderNotifyModel;
use core\enum\DealDkEnum;
use libs\utils\Logger;
use core\enum\SupervisionEnum;
use web\controllers\banksign\BkBaseAction;
use core\service\supervision\SupervisionFinanceService;


class Confirm extends BkBaseAction {

    public function invoke() {
        $vcode = trim($_GET['vcode']);
        if(empty($vcode)){
            return $this->show_error('验证码错误','',1);
        }
        $orderInfo = $this->orderInfo;

        // 请求签约确认接口
        $s = new SupervisionFinanceService();
        $res = $s->signConfirm(array('orderId'=>$orderInfo['order_id'],'smsCode'=>$vcode));

        $return = array('status' => 0,'err_msg' => '签约成功');
        if($res['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            $return['status'] = $res['respCode'];
            $return['err_msg'] = $res['respMsg'];
        }

        // 异步通知资产端
        if (!empty($orderInfo['notify_url'])) {
            $orderNotifyInfo = OrderNotifyModel::instance()->findViaOrderId($orderInfo['client_id'], $orderInfo['order_id']);
            if (empty($orderNotifyInfo)) {
                // 回调时，应该将outer_order_id和结果放到回调参数中的
                $insertOrderNotifyData = [
                    'client_id'     => $orderInfo['client_id'],
                    'order_id'      => $orderInfo['order_id'],
                    'notify_url'    => $orderInfo['notify_url'],
                    'notify_params' => ['out_order_id'=>$orderInfo['outer_order_id'],'status'=>$return['status'],'err_msg'=> $return['err_msg']],
                ];
                $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                if (!$orderNotifyRes) {
                    throw new \Exception("插入接口异步通知回调失败");
                }
            }
        }


        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "协议支付-签约确认", 'orderId:'.$this->orderInfo['order_id']." res:".json_encode($res))));
        return ajax_return($return);
    }
}