<?php
/**
 * 充值检查 (Ajax接口)
 */
namespace web\controllers\payment;

use libs\web\Form;
use web\controllers\BaseAction;

class PayCheckAsyn extends BaseAction
{

    public function init()
    {
        if (!$this->check_login()) return false;
    }

    public function invoke()
    {
        $userId = intval($GLOBALS['user_info']['id']);
        $orderSn = isset($_GET['orderSn']) ? trim($_GET['orderSn']) : '';

        //参数检查
        if (empty($orderSn) || !is_numeric($orderSn)) {
            return ajax_return(array('status' => -1));
        }

        //状态查询
        $status = $this->rpc->local('PaymentService\getChargeStatusCache', array($userId, $orderSn));
        if (!$status) {
            return ajax_return(array('status' => 0));
        }

        return ajax_return(array('status' => 1));
    }

}
