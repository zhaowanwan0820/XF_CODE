<?php
/**
 * firstp2p 支付四要素审核回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\UserBankcardService;

class AuthCardNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("AuthCardNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("AuthCardNotifyCallback redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['cardNo'] = isset($_POST['cardNo']) ? trim($_POST['cardNo']) : '';
        $params['theTime'] = isset($_POST['theTime']) ? trim($_POST['theTime']) : '';
        $params['status'] = isset($_POST['status']) ? trim($_POST['status']) : '';
        $params['userId'] = isset($_POST['userId']) ? trim($_POST['userId']) : '';
        $params['bankName'] = isset($_POST['bankName']) ? trim($_POST['bankName']) : '';
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['orderId'] = isset($_POST['orderId']) ? trim($_POST['orderId']) : '';
        $params['bankCode'] = isset($_POST['bankCode']) ? trim($_POST['bankCode']) : '';

        //防止并发
        $uniqOutOrderId = 'AUTHCARD_'.$params['orderId'];
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
                \libs\utils\Alarm::push('payment', 'AuthCardNotify', "param $key is empty. params:".json_encode($params));
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
            \libs\utils\Alarm::push('payment', 'AuthCardNotify', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }
        $params['cardNo'] = PaymentApi::instance()->getGateway()->decode($params['cardNo']);

        $response = [
            'respCode' => '00',
            'respMsg' => '',
            'status' => '00',
        ];
        if (!empty($params) && isset($params['userId']) && !empty($params['cardNo'])) {
            PaymentApi::log('decrypt authcard, id:'.$params['userId'].' status:'.$params['status']);
            $redisKey = 'authcard_result_'.$params['userId'];
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            // 写入缓存
            $redis->set($redisKey, json_encode($params));

            //自动换卡
            $userBankcardService = new UserBankcardService();
            $autoUpdateResult = $userBankcardService->autoUpdateUserBankCard($params);
            PaymentApi::log("autoUpdateUserBankCard, result: " . json_encode($autoUpdateResult));
            if (!empty($autoUpdateResult['status']) && !in_array($autoUpdateResult['status'], ['02', '03', '07'])) {
                $response = $autoUpdateResult;
            }

            if ($autoUpdateResult['status'] == '00') {
                try {
                    \core\service\partner\PartnerService::modifyCardNotify($params['userId']);
                } catch (\Exception $e) {
                    PaymentApi::log("modifyCardNotify failed. Err:".$e->getMessage(), Logger::ERR);
                }
            }
        }

        \SiteApp::init()->dataCache->remove($uniqOutOrderId);
        echo PaymentApi::instance()->getGateway()->response($response);
        return;
    }

}
