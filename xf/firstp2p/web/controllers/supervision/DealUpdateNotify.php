<?php
/**
 * firstp2p网站-标的更新回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class DealUpdateNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision dealUpdateNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision dealUpdateNotifyCallback redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision dealUpdateNotifyCallback Request params decode:' . json_encode($requestData));

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
        $params['bidId'] = isset($requestData['bidId']) ? addslashes($requestData['bidId']) : '';
        $params['bidStatus'] = isset($requestData['bidStatus']) ? addslashes($requestData['bidStatus']) : '';
        $params['bankAuditStatus'] = isset($requestData['bankAuditStatus']) ? addslashes($requestData['bankAuditStatus']) : '';

        // 逻辑处理
        $result = $this->rpc->local('SupervisionDealService\dealReportNotify', [$params]);
        echo $supervision->response($result);
        return;
    }
}