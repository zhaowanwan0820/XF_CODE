<?php
/**
 * firstp2p网站- 提现至超级账户 回调接口
 *
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use core\service\ChargeService;
use core\dao\SupervisionTransferModel;

class SuperWithdrawNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision SuperWithdrawNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision SuperWithdrawNotify redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        //PaymentApi::log('Supervision SuperWithdrawNotify Request params decode:' . json_encode($requestData));

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
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';

        // 逻辑处理
        $result = $this->rpc->local('SupervisionFinanceService\superRechargeNotify', [$params['orderId'], SupervisionTransferModel::DIRECTION_TO_WX]);
        echo $supervision->response($result);
        return;
    }
}
