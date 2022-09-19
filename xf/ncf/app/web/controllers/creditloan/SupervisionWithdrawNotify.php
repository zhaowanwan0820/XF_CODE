<?php
/**
 * firstp2p网站-提现至银信通电子账户的回调接口
 *
 */
namespace web\controllers\creditloan;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class SupervisionWithdrawNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision Creditloan WithdrawNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision Creditloan WithdrawNotifyCallback redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision Creditloan WithdrawNotifyCallback Request params decode:' . json_encode($requestData));

        // 签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->response(['respCode' => '00', 'status'=>'01', 'respMsg' => '签名错误']);
            return;
        }

        // 参数列表
        $params = array();
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['amount'] = isset($requestData['amount']) ? addslashes($requestData['amount']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $loanService = new \core\service\speedLoan\LoanService();
        $result =  $loanService->withdrawNotify($params['orderId'], $params['status'] == 'S' ? '00' : '01', $params['remark']);
        if ($result == false) {
            echo $supervision->response(['respCode'=> '00', 'status' => '00', 'respMsg' => '提现失败']);
        }
        echo $supervision->response(['respCode'=> '00', 'status' => '00', 'respMsg' => '成功']);
        return;
    }
}
