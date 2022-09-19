<?php
/**
 * firstp2p网站- 存管实名更改 回调接口
 *
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class MemberInfoModifyNotify extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Supervision MemberInfoModifyNotify Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision MemberInfoModifyNotify redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        //PaymentApi::log('Supervision MemberInfoModifyNotify Request params decode:' . json_encode($requestData));

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
        $params['userId'] = isset($requestData['userId']) ? intval($requestData['userId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['failReason'] = isset($requestData['failReason']) ? addslashes($requestData['failReason']) : '';
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';

        //防并发redis锁
        $uniqOutOrderId = 'MEMBER_INFO_MODIFY_LOCK_'.$params['orderId'];
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($uniqOutOrderId, 1, 5);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        if ($state !== 'OK') {
            echo $supervision->response([
                'status' => 'F',
                'respCode' => '04',
                'respMsg' => '访问频率过快',
            ]);
            return;
        }

        // 逻辑处理
        $result = $this->rpc->local('SupervisionAccountService\memberInfoModifyNotify', [$params]);
        echo $supervision->response($result);

        // 解除redis锁
        $redis->remove($uniqOutOrderId);

        return;
    }
}
