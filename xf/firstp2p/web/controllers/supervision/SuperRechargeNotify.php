<?php
/**
 * 从超级账户充值到网贷账户回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

use core\dao\SupervisionTransferModel;

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class SuperRechargeNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision SuperRechargeNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision SuperRechargeNotifyCallback redirect.");
            return app_redirect('/');
        }


        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log("Supervision SuperRechargeNotifyCallback Request params decode:".json_encode($requestData));

        //签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->responseFailure('ERR_SIGNATURE');
            return;
        }
        // 订单创建60s内不接受异步通知
        $record = SupervisionTransferModel::instance()->getTransferRecordByOutId($requestData['orderId']);
        if (isset($record['create_time']) && time() - $record['create_time'] < 60) {
            echo $supervision->responseFailure('ERR_REQUEST_FREQUENCY_TOO_FAST');
            return;
        }


        //逻辑处理
        $result = $this->rpc->local('SupervisionFinanceService\superRechargeNotify', [$requestData['orderId']]);
        echo PaymentApi::instance('supervision')->getGateway()->response($result);
        return;
    }

}
