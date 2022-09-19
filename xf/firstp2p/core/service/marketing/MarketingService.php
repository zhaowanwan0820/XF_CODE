<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class MarketingService {

    public static function discountBind($userId, $mobile) {
        if (empty($mobile) || !is_numeric($mobile)) {
            throw new \Exception('手机号不能为空或者包含非数字');
        }

        $request = new SimpleRequestBase();
        $request->setParamArray(array('userId' => $userId, 'mobile' => $mobile));
        $response = self::requestMarketing('NCFGroup\Marketing\Services\Seckill', 'discountBind', $request, 1);

        return $response;
    }

    public function acqiureLogQuestionnaire( $mobile, $couponId, $eventId = 1, $userId = 0)
    {
        $request = new SimpleRequestBase();
        $request->setParamArray(array('userId' => $userId, 'mobile' => $mobile, 'couponId' => $couponId, 'eventId' => $eventId));
        $response = self::requestMarketing('NCFGroup\Marketing\Services\AcquireLog', 'acqiureLogQuestionnaire', $request, 1);

        return $response;
    }

    public static function getBonusTaskInfo($taskId)
    {
        $req = new SimpleRequestBase;
        $req->taskId = $taskId;
        $resp = self::requestMarketing('NCFGroup\Marketing\Services\BonusPushTask', 'getTaskRPC', $req, 1);
        return $resp;
    }

    /**
     * 请求marketing服务
     */
    public static function requestMarketing($service, $method, $request, $timeOut = 3, $retry = true) {
        $beginTime = microtime(true);
        // 考虑到统一处理的便捷，后期可以考虑集成到phalcon-common框架中
        if ($request instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase) {
            // 跨系统日志id的统一
            $request->_log_id_ = Logger::getLogId();
        }

        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        Logger::info("[req]MarketingService.{$className}.{$method}:" . json_encode($request, JSON_UNESCAPED_UNICODE));
        // 增加重试
        $maxTryTimes = 3;
        $retryTimes = 0;
        do {
            try {
                if ($maxTryTimes != 3) {
                    ++$retryTimes;
                    Logger::info("MarketingService retry {$retryTimes}.$service.$method:" . json_encode($request, JSON_UNESCAPED_UNICODE));
                }

                if (!isset($GLOBALS['marketingRpc']) || !($GLOBALS['marketingRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)) {
                    $marketingRpcConfig = $GLOBALS['components_config']['components']['rpc']['marketing'];
                    $GLOBALS['marketingRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($marketingRpcConfig['rpcServerUri'],
                        $marketingRpcConfig['rpcClientId'], $marketingRpcConfig['rpcSecretKey']);
                }

                $response = $GLOBALS['marketingRpc']->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
                ));

                if (!empty($response) || !$retry) {
                    break;
                }
            } catch (\Exception $e) {
                $exceptionName = get_class($e);
                // 超时，重试
                if ($exceptionName == 'Yar_Client_Transport_Exception' && $e->getCode() == 16) {
                    if ($maxTryTimes == 1) {
                        Logger::warn("MarketingService.{$className}.{$method}:" . $e->getMessage());
                        throw new \Exception('系统繁忙,请稍后再试');
                    }
                } else {
                    Logger::error("[resp]MarketingService.{$className}.{$method}:" . $e->getMessage());
                    throw $e;
                }
            }
        } while(--$maxTryTimes > 0);

        if (gettype($response) == 'object') {
            $response = $response->toArray();
        }

        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        Logger::info("[resp][cost:{$elapsedTime}]MarketingService.{$className}.{$method}:" . json_encode($response, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * getTemplateBySiteId
     *
     * @param int $siteId
     * @access public
     * @return void
     */
    public function getTemplateInfoBySiteId($siteId = 1)
    {
        if ($siteId <= 0) {
            $siteId = 1;
        }
        $request = new SimpleRequestBase();
        $request->setParamArray(array('siteId' => $siteId));
        $response = self::requestMarketing('NCFGroup\Marketing\Services\WxDiscountTemplate', 'getTemplateInfoBySiteId', $request, 1);

        return $response;
    }

    /**
     * getAcquiredDiscountInfo
     *
     * @param mixed $discountId
     * @access public
     * @return void
     */
    public function getGivenDiscountInfo($discountId)
    {
        $request = new SimpleRequestBase();
        $request->setParamArray(array('discountId' => $discountId));
        $response = self::requestMarketing('NCFGroup\Marketing\Services\WxShareLog', 'getGivenDiscountInfo', $request, 1);

        return $response;
    }

    /**
     * collectDiscount
     *
     * @param mixed $discountId
     * @param mixed $mobile
     * @param int $userId
     * @access public
     * @return void
     */
    public function collectDiscount($discountId, $mobile, $userId = 0, $fromUserId = 0, $discountInfo = array(), $openid = ''){

        try {
            if(empty($mobile) || empty($discountId)){
                throw new \Exception('参数错误!');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array('discountId' => intval($discountId),'mobile' => $mobile ,'userId' => intval($userId), 'fromUserId' => $fromUserId, 'discountInfo' => json_encode($discountInfo), 'openid' => $openid));
            $response = $GLOBALS['marketingRpc']->callByObject(array(
                    'service' => 'NCFGroup\Marketing\Services\WxShareLog',
                    'method' => 'collectDiscount',
                    'args' => $request
            ));

        } catch (\Exception $e) {
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'discountId:'.$discountId,'user_id:'.$userId,'mobile:'.$mobile,'exception:'.json_encode($e))));
            return false;
        }

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'discountId:'.$discountId,'user_id:'.$userId,'mobile:'.$mobile)));
        return $response;
    }
}
