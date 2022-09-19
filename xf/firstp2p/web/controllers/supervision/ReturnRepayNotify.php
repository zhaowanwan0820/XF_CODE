<?php
/**
 * 还代偿款回调
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class ReturnRepayNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision ReturnRepayNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision ReturnRepayNotifyCallback redirect.");
            return app_redirect('/');
        }

        $supervision = PaymentApi::instance('supervision')->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log("Supervision ReturnRepayNotifyCallback Request params decode:".json_encode($requestData));

        //签名验证
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
        $params['amount'] = isset($requestData['amount']) ? intval($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $result = [
            'respCode'  => '00',
            'status'    => 'S',
            'respMsg'   => '',
        ];
        $processResult = $this->rpc->local('NongdanService\processReturnRepay', [$params]);
        if ($processResult != true)
        {
            $result['respCode'] = '01';
            $result['status']   = 'F';
        }
        echo $supervision->response($result);
        return;
   }
}
