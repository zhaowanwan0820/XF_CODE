<?php
/**
 * firstp2p网站- 免密提现至银行卡 回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class WithdrawNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision WithdrawNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision WithdrawNotify redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision WithdrawNotify Request params decode:' . json_encode($requestData));

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
        $params['amount'] = isset($requestData['amount']) ? intval($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? intval($requestData['orderId']) : '';

        // 逻辑处理
        $result = $this->rpc->local('SupervisionFinanceService\withdrawNotify', [$params]);
        echo $supervision->response($result);
        return;
    }
}
