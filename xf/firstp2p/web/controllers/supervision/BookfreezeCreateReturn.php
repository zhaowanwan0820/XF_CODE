<?php
/**
 * firstp2p网站-智多鑫-预约冻结回调-同步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\common\WXException;
use core\service\SupervisionBaseService AS Supervision;

class BookfreezeCreateReturn extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("SupervisionDT bookfreezeCreateReturnCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log('SupervisionDT bookfreezeCreateReturnCallback redirect.');
            return app_redirect('/');
        }

        try {
            $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
            //参数获取
            $requestData = $supervision->getData($_POST);
            PaymentApi::log('SupervisionDT bookfreezeCreateReturnCallback Request params decode:' . json_encode($requestData));

            //签名验证
            $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
            $verifyResult = $supervision->verifySignature($requestData, $signature);
            if ($verifyResult === false)
            {
                throw new WXException('ERR_SIGNATURE');
            }

            // 参数列表
            $params = array();
            $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
            $params['userId'] = isset($requestData['userId']) ? (int)$requestData['userId'] : '';
            $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
            $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
            $params['freezeAccountAmount'] = isset($requestData['freezeAccountAmount']) ? (int)$requestData['freezeAccountAmount'] : '';
            $params['freezeType'] = isset($requestData['freezeType']) ? addslashes($requestData['freezeType']) : '';
            $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
            $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

            // 逻辑处理
            $result = $this->rpc->local('DtPaymenyService\bookfreezeCreateNotify', array($params));
            if ($result['respCode'] == Supervision::RESPONSE_CODE_SUCCESS && $result['status'] == Supervision::RESPONSE_SUCCESS) {
                echo '<h1>预约冻结成功</h1>';
            } else {
                throw new WXException('ERR_DT_BOOKCREATENOTIFY_FAILED');
            }
        } catch (\Exception $e) {
            echo '<h1>预约冻结异常:'.$e->getCode().' '.$e->getMessage().'</h1>';
        }
    }
}