<?php
/**
 * 存管充值成功页面跳转落地页
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\gateway\GatewayService;
use core\service\SupervisionBaseService AS Supervision;

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

class ChargeResult extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        PaymentApi::log("Supervision Gateway Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision Gateway redirect.");
            return app_redirect('/');
        }
        try {
            $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
            //参数获取
            $requestData = $supervision->getData($_POST);
            //签名验证
            $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
            $verifyResult = $supervision->verifySignature($requestData, $signature);
            if ($verifyResult === false)
            {
                throw new \Exception('签名验证失败');
            }
            $result = $this->rpc->local('SupervisionFinanceService\chargeNotify', array($requestData, '充值到存管账户'));
            if ($result['respCode'] == Supervision::RESPONSE_CODE_SUCCESS && $result['status'] == Supervision::RESPONSE_SUCCESS) {
                echo '<h1>充值成功</h1>';
            } else {
                throw new \Exception('订单处理失败', '20001');
            }
        } catch (\Exception $e) {
            echo '<h1>充值失败:'.$e->getCode().' '.$e->getMessage().'</h1>';
        }
    }
}
