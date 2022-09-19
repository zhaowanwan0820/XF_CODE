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

class TransferNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("TransferNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("TransferNotifyCallback redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['transCount'] = isset($_POST['transCount']) ? intval($_POST['transCount']) : 0;
        $params['transOrders'] = isset($_POST['transOrders']) ? trim($_POST['transOrders']) : '';
        $params['createTime'] = isset($_POST['createTime']) ? trim($_POST['createTime']) : '';
        $params['signature'] = isset($_POST['signature']) ? trim($_POST['signature']) : '';

        $result = array(
            'respCode' => PaymentService::API_RESPONSE_SUCCESS,
            'respMsg' => '',
            'transOrders' => '',
        );
        try {
            //必填参数验证
            foreach ($params as $key => $value)
            {
                if ($value === '' || $value === 0)
                {
                    PaymentApi::log("param $key is empty", Logger::ERR);
                    \libs\utils\Alarm::push('payment', 'TransferNotify', "param $key is empty. params:".json_encode($params));
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
                \libs\utils\Alarm::push('payment', 'TransferNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
                throw new \Exception('signature failed');
            }

            //逻辑处理
            $retResult = $this->rpc->local('PaymentService\transferNotifyCallback', array($params['transCount'],$params['transOrders']));
            if (is_array($retResult)) {
                $result['transOrders'] = json_encode($retResult);
            }

        }
        catch(\Exception $e) {
            $result['respCode'] = PaymentService::API_RESPONSE_FAIL;
            $result['respMsg'] = $e->getMessage();
            $result['transOrders'] = "";
        }
        echo PaymentApi::instance()->getGateway()->response($result);
        return;
    }

}
