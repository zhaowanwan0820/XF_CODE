<?php
/**
 * 存管回调基类
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\common\ErrCode;
use core\service\supervision\SupervisionService;

class NotifyAction extends BaseAction
{
    //订单锁，用来防止并发
    protected $orderLock = false;

    public function invoke()
    {
        $className = str_replace(__NAMESPACE__ . '\\', '', get_class($this));

        PaymentApi::log("Supervision {$className} Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision {$className} redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $supervisionObj = new SupervisionService();
        $requestData = $supervisionObj->getApi()->decode(json_encode($_POST));
        PaymentApi::log('Supervision ' . $className . ' Request params decode:' . json_encode($this->formatData($requestData)));

        // 签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervisionObj->getApi()->verify($requestData);
        if ($verifyResult === false)
        {
            $errorData = $supervisionObj->responseFailure(ErrCode::getCode('ERR_SIGNATURE'), ErrCode::getMsg('ERR_SIGNATURE'));
            echo $supervisionObj->getApi()->response($errorData);
            return;
        }

        //订单锁
        if ($this->orderLock && !empty($requestData['orderId'])) {
            $uniqOutOrderId = sprintf('SUPERVISION_%s_LOCK_%s', strtoupper($className), $requestData['orderId']);
            $redis = \SiteApp::init()->dataCache;
            $redisState = $redis->setNx($uniqOutOrderId, 1, 10);
            $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
            if ($state !== 'OK') {
                echo $supervisionObj->getApi()->response([
                    'status' => 'F',
                    'respCode' => '04',
                    'respMsg' => '访问频率过快',
                ]);
                return;
            }
        }

        //处理回调，子类实现
        $result = $this->process($requestData);

        // 释放订单锁
        if ($this->orderLock && !empty($requestData['orderId'])) {
            $uniqOutOrderId = sprintf('SUPERVISION_%s_LOCK_%s', strtoupper($className), $requestData['orderId']);
            $redis = \SiteApp::init()->dataCache;
            $redis->remove($uniqOutOrderId);
        }

        echo $supervisionObj->getApi()->response($result);
        return;
    }

    /**
     * 处理回调，子类实现
     */
    public function process($requestData) {
        return [
            'respCode'  => '01',
            'status'    => 'F',
            'respMsg'   => '回调处理失败',
        ];
    }

    /**
     * 格式化数据
     * 脱敏等
     */
    private function formatData($data) {
        if (!is_array($data)) {
            return $data;
        }
        if (!empty($data['receiveBankCardNo']) && is_string($data['receiveBankCardNo'])) {
            $data['receiveBankCardNo'] = bankNoFormat($data['receiveBankCardNo']);
        }
        return $data;
    }
}
