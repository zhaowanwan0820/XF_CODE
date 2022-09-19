<?php
/**
 * 代偿回调
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class ReplaceRepayNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision ReplaceRepayNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision ReplaceRepayNotifyCallback redirect.");
            return app_redirect('/');
        }


        $supervision = PaymentApi::instance('supervision')->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log("Supervision ReplaceRepayNotifyCallback Request params decode:".json_encode($requestData));
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? trim($requestData['merchantId']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $params['status'] = isset($requestData['status']) ? trim($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? intval($requestData['remark']) : 0;

        //签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->responseFailure('ERR_SIGNATURE');
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('SupervisionDealService\dealRepayNotify', [$params]);
        echo PaymentApi::instance('supervision')->getGateway()->response($result);
        return;
    }
}
