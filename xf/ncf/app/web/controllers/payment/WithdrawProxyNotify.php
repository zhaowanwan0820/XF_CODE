<?php
/**
 * firstp2p 支付代发回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use NCFGroup\Common\Library\StandardApi;
use core\service\WithdrawProxyService;

class WithdrawProxyNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("WithdrawProxyNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("WithdrawProxyNotifyCallback redirect.");
            return app_redirect('/');
        }
        $api = StandardApi::instance(StandardApi::UCFPAY_GATEWAY);
        $api->setLogId(Logger::getLogId());
        // 验签
        if(!$api->verify($_POST))
        {
            PaymentApi::log('验签失败');
            echo $api->response('verify signature failed.');
            exit;
        }
        $response = 'SUCCESS';
        try {
            $data = $api->decode(json_encode($_POST));
            $merchantId = isset($_POST['merchantId']) ? $_POST['merchantId'] : '';
            if (empty($merchantId))
            {
                throw new \Exception('empty merchant_id');
            }
            if (WithdrawProxyService::handleResponse($data, $merchantId) !== true)
            {
                throw new \Exception('process order#'.$data['merchantNo'].' failed');
            }

            //echo $api->responseSuccess();
        } catch (\Exception $e) {
            $response = $e->getMessage();
            //echo $api->responseFailure($e->getMessage());
        }

        echo $api->response($response);
        return;
    }

}
