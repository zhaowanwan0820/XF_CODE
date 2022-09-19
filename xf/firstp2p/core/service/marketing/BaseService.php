<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class BaseService 
{

    /**
     * 请求marketing服务
     */
    public static function requestMarketing($service, $method, $request, $timeOut = 3, $retry = true) {
        if (app_conf('MARKETING_SERVICE_SWITCH') == 0) {
            Logger::info('MarketingService is down');
            return false;
        }
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
                        throw new \Exception($e->getMessage());
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

}
