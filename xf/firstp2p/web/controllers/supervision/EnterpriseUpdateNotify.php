<?php
/**
 * firstp2p网站-企业用户资料修改的回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class EnterpriseUpdateNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision enterpriseUpdateNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision enterpriseUpdateNotifyCallback redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision enterpriseUpdateNotifyCallback Request params decode:' . json_encode($requestData));

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
        $params['userId'] = isset($requestData['userId']) ? addslashes($requestData['userId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $result = $this->rpc->local('SupervisionAccountService\enterpriseUpdateNotify', [$params]);
        echo $supervision->response($result);
        return;
    }
}