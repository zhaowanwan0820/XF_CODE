<?php
/**
 * firstp2p 海口银行放款回调接口(3.4接口的回调)
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class LoanLendNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("LoanLendNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("LoanLendNotify redirect.");
            return app_redirect('/');
        }

        $params = array();
        // 必填必验参数
        $params['OWJnlNo'] = isset($_POST['OWJnlNo']) ? trim($_POST['OWJnlNo']) : '';
        $params['JnlNo'] = isset($_POST['JnlNo']) ? trim($_POST['JnlNo']) : '';
        $params['TrsResult'] = isset($_POST['TrsResult']) ? trim($_POST['TrsResult']) : '';
        $params['Amount'] = isset($_POST['Amount']) ? trim($_POST['Amount']) : '';
        $params['TrsTime'] = isset($_POST['TrsTime']) ? trim($_POST['TrsTime']) : '';
        $params['PTime'] = isset($_POST['PTime']) ? trim($_POST['PTime']) : '';
        $params['PRate'] = isset($_POST['PRate']) ? trim($_POST['PRate']) : '';
        $params['PAmount'] = isset($_POST['PAmount']) ? trim($_POST['PAmount']) : '';
        $params['TrsState'] = isset($_POST['TrsState']) ? trim($_POST['TrsState']) : '';

        $gateway = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_UNITEBANK)->getGateway();

        //必填参数验证
        foreach ($params as $key => $value)
        {
            if ($value === '')
            {
                echo $gateway->response(array(
                    'respCode' => '01',
                    'respMsg' => "param $key is empty",
                ));
                PaymentApi::log("param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'LoanLendNotify', "param $key is empty. params:".json_encode($_POST));
                return;
            }
        }

        //签名验证
        $signature = isset($_POST['Sign']) ? trim($_POST['Sign']) : 0;
        unset($_POST['Sign']);
        if (!$gateway->verifySignature($_POST, $signature))
        {
            echo $gateway->response(array(
                'respCode' => '02',
                'respMsg' => 'signature failed',
            ));
            PaymentApi::log("Signature failed. get:$signature", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'LoanLendNotify', "Signature failed. get:$signature, params:".json_encode($_POST));
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('UniteBankPaymentService\LoanLendNotifyCallback', array($params));

        echo $gateway->response($result);
        return;
    }

}
