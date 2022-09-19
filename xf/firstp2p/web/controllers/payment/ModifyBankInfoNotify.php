<?php
/**
 * firstp2p 修改银行卡同步支付， 支付风控审核结果通知接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class ModifyBankInfoNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("ModifyBankNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("ModifyBankNotifyCallback redirect.");
            return app_redirect('/');
        }
        // 如果审核不通过，记录告警
        if (!isset($_POST['orderStatus']) || $_POST['orderStatus'] != '00')
        {
            \libs\utils\Alarm::push('payment', 'ModifyBankInfoNotify', "银行卡信息修改，支付风控审核没通过，请求参数:".json_encode($_POST));
        }

        echo PaymentApi::instance()->getGateway()->response(array(
            'respCode' => '00',
            'respMsg' => '',
            'status' => '00',
        ));

        return;
    }

}
