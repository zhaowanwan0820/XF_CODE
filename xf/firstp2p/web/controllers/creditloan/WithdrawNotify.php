<?php
/**
 * firstp2p网站 银行还款回调
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\creditloan;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class WithdrawNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("WithdrawTrustBankNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("WithdrawTrustBankNotifyCallback redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['curType'] = isset($_POST['curType']) ? trim($_POST['curType']) : '';
        $params['userId'] = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $params['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $params['outOrderId'] = isset($_POST['outOrderId']) ? trim($_POST['outOrderId']) : '';
        $params['bankCardNo'] = isset($_POST['bankCardNo']) ? trim($_POST['bankCardNo']) : '';
        $params['tranTime'] = isset($_POST['tranTime']) ? trim($_POST['tranTime']) : '';
        $params['orderStatus'] = isset($_POST['orderStatus']) ? trim($_POST['orderStatus']) : '';

        //必填参数验证
        foreach ($params as $key => $value)
        {
            if ($value === '' || $value === 0)
            {
                echo PaymentApi::instance()->getGateway()->response(array(
                    'respCode' => '01',
                    'respMsg' => "param $key is empty",
                    'status' => '01',
                ));
                PaymentApi::log("param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'WithdrawTrustBankNotify', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        //签名验证
        $signature = isset($_POST['signature']) ? trim($_POST['signature']) : 0;
        unset($_POST['signature']);
        $signatureLocal = PaymentApi::instance()->getGateway()->getSignature($_POST);
        if ($signature !== $signatureLocal)
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '02',
                'respMsg' => 'signature failed',
                'status' => '01',
            ));
            PaymentApi::log("Signature failed. get:$signature, local:$signatureLocal", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'WithdrawTrustBankNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        //逻辑处理
        $result = ['respCode' => '00', 'status' => '00', 'respMsg' => '成功'];
        $loanService = new \core\service\speedLoan\LoanService();
        $withdrawResult = $loanService->withdrawNotify($params['outOrderId'], $params['orderStatus'], $_POST['assignOrderId']);
        if (!$withdrawResult) {
            $result = [
                'respCode' => '01',
                'respMsg' => '业务操作失败',
                'status' => '01',
            ];
        }
        echo PaymentApi::instance()->getGateway()->response($result);
        return;
    }
}
