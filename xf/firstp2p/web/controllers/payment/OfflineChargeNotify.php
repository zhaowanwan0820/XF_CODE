<?php
/**
 * firstp2p网站线下充值回调接口
 * 支持pos,线下充值业务
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
//ini_set('display_errors', 1);
class OfflineChargeNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("OfflineChargeNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("OfflineChargeNotifyCallback redirect.");
            return app_redirect('/');
        }

        $memo = isset($_POST['remark']) ? addslashes(trim($_POST['remark'])) : '';

        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['curType'] = isset($_POST['curType']) ? trim($_POST['curType']) : '';
        $params['userId'] = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $params['bizType'] = isset($_POST['bizType']) ? trim($_POST['bizType']) : '';
        $params['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $params['outOrderId'] = isset($_POST['outOrderId']) ? trim($_POST['outOrderId']) : '';
        $params['tranTime'] = isset($_POST['tranTime']) ? trim($_POST['tranTime']) : '';
        $params['orderStatus'] = isset($_POST['orderStatus']) ? trim($_POST['orderStatus']) : '';

        $uniqOutOrderId = 'OFFLINECHARGE_'.$params['outOrderId'];
        // fuck 支付赎回订单的重发
        if ($params['bizType'] == 'fund_redeem') {
            $redis = \SiteApp::init()->dataCache;
            $redisState = $redis->setNx($uniqOutOrderId, 1, 10);
            $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
            if ($state !== 'OK') {
                echo PaymentApi::instance()->getGateway()->response([
                    'status' => '01',
                    'respCode' => '04',
                    'respMsg' => '访问频率过快',
                    ]);
                return;
            }
        }

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
                \libs\utils\Alarm::push('payment', 'OfflineChargeNotify', "param $key is empty. params:".json_encode($params));
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
            \libs\utils\Alarm::push('payment', 'OfflineChargeNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('PaymentService\offlineChargeResultCallback', array($params['userId'], $params['outOrderId'], $params['orderStatus'], $params['amount'], $params['bizType'], $memo));

        $result['status'] = (!isset($result['respCode']) || $result['respCode'] != '00') ? '01' : '00';
        \SiteApp::init()->dataCache->remove($uniqOutOrderId);
        echo PaymentApi::instance()->getGateway()->response($result);
        return;
    }

}
