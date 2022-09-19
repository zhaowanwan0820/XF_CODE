<?php
/**
 * 网贷p2p大额充值回调接口
 * @author guofeng3<guofeng3@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class OfflineChargeNotify extends BaseAction {
    public function init() {}

    public function invoke() {
        //参数获取
        PaymentApi::log("SupervisionOfflineChargeNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            PaymentApi::log("SupervisionOfflineChargeNotifyCallback redirect.");
            return app_redirect('/');
        }

        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log("SupervisionOfflineChargeNotifyCallback Request params decode:".json_encode($requestData));

        //签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false) {
            echo $supervision->responseFailure('ERR_SIGNATURE');
            return;
        }

        // 防止并发
        $uniqOutOrderId = 'SUPERVISION_OFFLINECHARGE_LOCK_' . $requestData['orderId'];
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($uniqOutOrderId, 1, 60);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        if ($state !== 'OK') {
            echo $supervision->response([
                'status' => 'F',
                'respCode' => '04',
                'respMsg' => '访问频率过快',
            ]);
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('SupervisionFinanceService\chargeNotify', [$requestData]);
        echo PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway()->response($result);

        // 解除redis锁
        $redis->remove($uniqOutOrderId);
        return;
    }
}