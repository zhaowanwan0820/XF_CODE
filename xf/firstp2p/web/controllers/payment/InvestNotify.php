<?php
/**
 * firstp2p转账回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use core\service\PaymentService;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class InvestNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("InvestNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("InvestNotifyCallback redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['paymentId'] = isset($_POST['paymentId']) ? trim($_POST['paymentId']) : '';
        $params['outOrderId'] = isset($_POST['outOrderId']) ? trim($_POST['outOrderId']) : '';
        $params['orderStatus'] = isset($_POST['orderStatus']) ? trim($_POST['orderStatus']) : '';
        $params['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : '';
        $params['finishTime'] = isset($_POST['finishTime']) ? trim($_POST['finishTime']) : '';
        $params['signature'] = isset($_POST['signature']) ? trim($_POST['signature']) : '';

        $result = array(
            'respCode' => PaymentService::API_RESPONSE_SUCCESS,
            'respMsg' => '',
        );
        try {
            //必填参数验证
            foreach ($params as $key => $value)
            {
                if ($value === '' || $value === 0)
                {
                    PaymentApi::log("param $key is empty", Logger::ERR);
                    \libs\utils\Alarm::push('payment', 'InvestNotify', "param $key is empty. params:".json_encode($params));
                    throw new \Exception("param $key is empty");
                }
            }

            //签名验证
            $signature = isset($_POST['signature']) ? trim($_POST['signature']) : 0;
            unset($_POST['signature']);
            $signatureLocal = PaymentApi::instance()->getGateway()->getSignature($_POST);
            if ($signature !== $signatureLocal)
            {
                PaymentApi::log("Signature failed. get:$signature, local:$signatureLocal", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'InvestNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
                throw new \Exception('signature failed');
            }

            //逻辑处理, 业务处理中通过异常来跳出处理流程
            $this->rpc->local('PaymentService\investNotifyCallback', array($params));
        }
        catch(\Exception $e) {
            PaymentApi::log($e->getMessage());
            $result['respCode'] = PaymentService::API_RESPONSE_FAIL;
            $result['respMsg'] = $e->getMessage();
        }
        echo PaymentApi::instance()->getGateway()->response($result);
        return;
    }
}
