<?php
/**
 * firstp2p网站-流标回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class DealCancelNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision dealCancelNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision dealCancelNotify redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance('supervision')->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision dealCancelNotify Request params decode:' . json_encode($requestData));

        // 签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->responseFailure('ERR_SIGNATURE');
        }

        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['bidId'] = isset($requestData['bidId']) ? addslashes($requestData['bidId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';

        // 逻辑处理
        $result = $this->rpc->local('SupervisionDealService\dealCancelNotify', [$params]);
        echo $supervision->response($result);
        return;
    }
}
