<?php
/**
 * 易宝-充值回调接口
 * @author 郭峰 <guofeng3@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\YeepayPaymentService;

class YeepayChargeNotify extends BaseAction
{
    public function init()
    {
    }

    /**
     * 
     * 易宝异步通知商户支付请求传过来的callbackurl地址，每2秒通知一次，共通知3次
     * 商户收到通知后需要回写，需要返回字符串大写的"SUCCESS"，否则会一直通知多次
     * @see \libs\web\Action::invoke()
     */
    public function invoke()
    {
        // 请求方式
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        //参数获取
        PaymentApi::log("YeepayChargeNotifyCallback Request. method:{$requestMethod}, POST:".json_encode($_POST));

        if ($requestMethod !== 'POST')
        {
            PaymentApi::log("YeepayChargeNotifyCallback Request Method Is Illegal.");
            return app_redirect('/');
        }

        // 解析易宝的回调参数
        $requestData = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->parseJsonData($_POST);
        if (empty($requestData) || false === $requestData)
        {
            PaymentApi::log("YeepayChargeNotifyCallback Request. Params_Parse_Error, requestData:".json_encode($requestData));
            echo PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->response('Params_Parse_Error');
            exit;
        }
        // 记录解析后的数据
        PaymentApi::log("YeepayChargeNotifyCallback Request. method:{$requestMethod}, requestData:".json_encode($requestData));

        //参数获取
        $requiredParams = array();
        $requiredParams['merchantno'] = isset($requestData['merchantno']) ? trim($requestData['merchantno']) : '';
        $requiredParams['requestno'] = isset($requestData['requestno']) ? trim($requestData['requestno']) : '';
        $requiredParams['yborderid'] = isset($requestData['yborderid']) ? trim($requestData['yborderid']) : '';
        $requiredParams['amount'] = isset($requestData['amount']) ? intval($requestData['amount']) : '';
        $requiredParams['status'] = isset($requestData['status']) ? intval($requestData['status']) : '';
        $requiredParams['sign'] = isset($requestData['sign']) ? trim($requestData['sign']) : '';
        //必填参数验证
        foreach ($requiredParams as $key => $value)
        {
            if ($value === '')
            {
                echo PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->response(array(
                    'respCode' => '01',
                    'respMsg' => "param [{$key}] is empty",
                ));
                PaymentApi::log("YeepayChargeNotifyCallback, param [{$key}'] is empty", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'YeepayChargeNotify', "param {$key} is empty. params:" . json_encode($requestData));
                return;
            }
        }

        //签名验证
        $requestData['amount'] = bcadd($requestData['amount'], '0.00', 2);
        $isVerify = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->verifySignature($requestData, $requestData['sign']);
        if (!$isVerify)
        {
            echo PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->response(array(
                'respCode' => '02',
                'respMsg' => 'sign failed',
            ));
            PaymentApi::log("YeepayChargeNotifyCallback, Sign failed. get:{$requestData['sign']}", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'YeepayChargeNotify', "Sign failed. get:{$requestData['sign']}, params:" . json_encode($requestData));
            return;
        }

        //逻辑处理
        $paymentNoticeInfo = \core\dao\PaymentNoticeModel::instance()->getInfoByNoticeSn($requiredParams['requestno']);
        if (empty($paymentNoticeInfo))
        {
            echo PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->response(['respCode' => 1007, 'respMsg' => '充值订单不存在']);
            return;
        }
        $requiredParams['identityid'] = $requestData['identityid'] = $paymentNoticeInfo['user_id'];
        $result = $this->rpc->local('YeepayPaymentService\payYeepayChargeCallback', array($requestData));
        $resultJson = json_encode($result);
        PaymentApi::log(sprintf('YeepayChargeNotifyCallback, YeepayPaymentService::payYeepayChargeCallback. userId:%d, orderId:%s, result:%s', $requiredParams['identityid'], $requiredParams['requestno'], $resultJson), Logger::INFO);
        $output = (isset($result['respCode']) && $result['respCode'] == '00') ? 'SUCCESS' : 'FAILED';

        echo PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->response($output);
        return;
    }

}
