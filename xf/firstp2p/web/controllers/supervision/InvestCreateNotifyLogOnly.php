<?php
/**
 * firstp2p网站-投资验密回调接口只记录无密投资接口的通知
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class InvestCreateNotifyLogOnly extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision investCreateNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision investCreateNotify redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance('supervision')->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision investCreateNotify Request params decode:' . json_encode($requestData));

        // 签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->responseFailure('ERR_SIGNATURE');
            return;
        }

        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['amount'] = isset($requestData['amount']) ? addslashes($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        ///$result = $this->rpc->local('SupervisionDealService\investCreateNotify', [$params]);
        echo $supervision->response(['respCode' => '00', 'respMsg' => '']);
        return;
    }
}
