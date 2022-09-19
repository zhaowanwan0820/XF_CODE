<?php
/**
 * 协会上报回调基类
 *
 */
namespace web\controllers\ifapush;

use web\controllers\BaseAction;
use libs\utils\Logger;

class NotifyAction extends BaseAction
{
    //订单锁，用来防止并发
    protected $orderLock = false;

    public function invoke()
    {
        $className = str_replace(__NAMESPACE__ . '\\', '', get_class($this));

        Logger::info("IFAPUSH-{$className} Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            Logger::info("IFAPUSH {$className} 回调接口 只支持post方法 当前方法:".$_SERVER['REQUEST_METHOD']);
            $this->errno = '05';
            $this->error = '请求方法类型不支持';
            $this->json_data = '';
            return;
        }

        // 解密Data数据
        $requestData = $_POST;

        //订单锁
        if ($this->orderLock && !empty($requestData['orderId'])) {
            $uniqOutOrderId = sprintf('IFAPUSH_%s_LOCK_%s', strtoupper($className), $requestData['orderId']);
            $redis = \SiteApp::init()->dataCache;
            $redisState = $redis->setNx($uniqOutOrderId, 1, 10);
            $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
            if ($state !== 'OK') {
                $this->errno = '04';
                $this->error = '访问频率过快';
                $this->json_data = '';
                return;
            }
        }

        //处理回调，子类实现
        $this->process($requestData);
        //增加output日志
        $this->setLog('output', json_encode(array('errno'=>$this->errno,'error'=>$this->error,'data'=>$this->json_data)));

        // 释放订单锁
        if ($this->orderLock && !empty($requestData['orderId'])) {
            $uniqOutOrderId = sprintf('IFAPUSH_%s_LOCK_%s', strtoupper($className), $requestData['orderId']);
            $redis = \SiteApp::init()->dataCache;
            $redis->remove($uniqOutOrderId);
        }

        return;
    }

    /**
     * 处理回调，子类实现
     */
    public function process($requestData) {
        $this->errno = '01';
        $this->error = '失败';
        $this->json_data = '';
        return;
    }

}
