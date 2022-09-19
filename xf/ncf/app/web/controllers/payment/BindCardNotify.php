<?php
/**
 * firstp2p 绑卡回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class BindCardNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("BindCardNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("BindCardNotify redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['userId'] = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $params['cardNo'] = isset($_POST['cardNo']) ? trim($_POST['cardNo']) : '';
        $params['bankCode'] = isset($_POST['bankCode']) ? trim($_POST['bankCode']) : '';
        $params['bankName'] = isset($_POST['bankName']) ? trim($_POST['bankName']) : '';
        //$params['bankId'] = isset($_POST['bankId']) ? trim($_POST['bankId']) : '';
        //$params['province'] = isset($_POST['province']) ? trim($_POST['province']) : '';
        //$params['city'] = isset($_POST['city']) ? trim($_POST['city']) : '';
        $params['theTime'] = isset($_POST['theTime']) ? trim($_POST['theTime']) : '';

        $gateway = PaymentApi::instance()->getGateway();

        //必填参数验证
        foreach ($params as $key => $value)
        {
            if ($value === '' || $value === 0)
            {
                echo $gateway->response(array(
                    'respCode' => '01',
                    'respMsg' => "param $key is empty",
                ));
                PaymentApi::log("param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'BindCardNotify', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        //签名验证
        $signature = isset($_POST['signature']) ? trim($_POST['signature']) : 0;
        unset($_POST['signature']);
        $signatureLocal = $gateway->getSignature($_POST);
        if ($signature !== $signatureLocal)
        {
            echo $gateway->response(array(
                'respCode' => '02',
                'respMsg' => 'signature failed',
            ));
            PaymentApi::log("Signature failed. get:$signature, local:$signatureLocal", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'BindCardNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('PaymentService\bindcardNotifyCallback', array($_POST));

        echo $gateway->response($result);
        return;
    }

}
