<?php
/**
 * firstp2p 海口银行账户开户回调接口(2.1接口的回调)
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class CreateLoanAccountNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("CreateLoanAccountNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("CreateLoanAccountNotify redirect.");
            return app_redirect('/');
        }

        $params = array();
        // 必填必验参数
        $params['WJnlNo'] = isset($_POST['WJnlNo']) ? trim($_POST['WJnlNo']) : '';
        $params['JnlNo'] = isset($_POST['JnlNo']) ? trim($_POST['JnlNo']) : '';
        $params['TrsResult'] = isset($_POST['TrsResult']) ? trim($_POST['TrsResult']) : '';
        $params['TrsTime'] = isset($_POST['TrsTime']) ? trim($_POST['TrsTime']) : '';
        $params['EAccNo'] = isset($_POST['EAccNo']) ? trim($_POST['EAccNo']) : '';
        $params['PAccNo'] = isset($_POST['PAccNo']) ? trim($_POST['PAccNo']) : '';
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
                \libs\utils\Alarm::push('payment', 'CreateLoanAccountNotify', "param $key is empty. params:".json_encode($_POST));
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
            \libs\utils\Alarm::push('payment', 'CreateLoanAccountNotify', "Signature failed. get:$signature, params:".json_encode($_POST));
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('UniteBankPaymentService\CreateLoanAccountNotifyCallback', array($params));

        echo $gateway->response($result);
        return;
    }

}
