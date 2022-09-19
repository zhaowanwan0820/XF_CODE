<?php
/**
 * firstp2p网站提现回调接口
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace web\controllers\payment;

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
        PaymentApi::log("WithdrawNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("WithdrawNotifyCallback redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['curType'] = isset($_POST['curType']) ? trim($_POST['curType']) : '';
        $params['userId'] = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $params['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $params['bankCardNo'] = isset($_POST['bankCardNo']) ? trim($_POST['bankCardNo']) : '';
        $params['outOrderId'] = isset($_POST['outOrderId']) ? intval($_POST['outOrderId']) : 0;
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
                \libs\utils\Alarm::push('payment', 'WithdrawNotify', "param $key is empty. params:".json_encode($params));
                return;
            }
        }
        $params['respMsg'] = isset($_POST['respMsg']) ? trim($_POST['respMsg']) : '';
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
            \libs\utils\Alarm::push('payment', 'WithdrawNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('PaymentService\withdrawCallback', array($params));
        PaymentApi::log('withdraw return '.var_export($result, 1));
        $responseData = array(
            'respCode' => '00',
            'respMsg' => '',
            'status' => '00',
        );
        $responseData['respCode'] = $result['result'] === true ? '00' : '03';
        $responseData['respMsg'] = $result['reason'];
        $responseData['status'] = $result['result'] === true ? '00' : '01';
        //返回
        echo PaymentApi::instance()->getGateway()->response($responseData);
        return;
    }

}
