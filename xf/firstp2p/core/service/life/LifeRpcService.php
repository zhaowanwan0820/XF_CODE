<?php

namespace core\service\life;

use core\service\BaseService;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\exception\LifeException;

/**
 * Rpc请求底层服务
 */
class LifeRpcService extends BaseService {
    public static $projectName = 'life';
    public static $error = false;
    public static $errorMsg = '';
    public static $errorCode = 0;

    public function __construct() {
        if (!isset($GLOBALS[self::$projectName . 'Rpc']) || !($GLOBALS[self::$projectName . 'Rpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)) {
            $rpcConfig = $GLOBALS['components_config']['components']['rpc'][self::$projectName];
            $GLOBALS[self::$projectName . 'Rpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter(
                $rpcConfig['rpcServerUri'], $rpcConfig['rpcClientId'], $rpcConfig['rpcSecretKey']
            );
        }
    }

    /**
     * 请求Rpc方法
     *
     * @param $service string 服务名
     * @param $method string 方法名
     * @param $request mixed 请求request对象
     * @param $timeOut int 超时时间
     * @param $retry bool 是否重试
     * @access public
     * @return mixed
     */
    public function requestRpc($service, $method, $request, $timeOut = 3, $retry = true) {
        if (app_conf(strtoupper(self::$projectName) . '_SERVICE_ENABLE') == 0) {
            throw new \Exception(ucfirst(self::$projectName) . ' Service is down');
        }

        $beginTime = microtime(true);
        // 考虑到统一处理的便捷，后期可以考虑集成到phalcon-common框架中
        if ($request instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase) {
            // 在底层请求里面统一传递，对分站的支持
            $request->_site_id_ = \libs\utils\Site::getId();
            // 跨系统日志id的统一
            $request->_log_id_ = Logger::getLogId();
        }

        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        $logFunc = ucfirst(self::$projectName) . 'Service.' . $className . '.' . $method;
        PaymentApi::log("[req]{$logFunc}:" . json_encode($request, JSON_UNESCAPED_UNICODE), Logger::RPC);

        // 增加重试
        $maxTryTimes = 3;
        $retryTimes = 0;
        do {
            try {
                if ($maxTryTimes != 3) {
                    ++$retryTimes;
                    PaymentApi::log("{$logFunc} retry:{$retryTimes}", Logger::WARN);
                }
                $GLOBALS[self::$projectName . 'Rpc']->setTimeout($timeOut);
                $response = $GLOBALS[self::$projectName . 'Rpc']->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
                ));
                if (!empty($response) || !$retry) {
                    break;
                }
            } catch (\Exception $e) {
                \libs\utils\Alarm::push(self::$projectName . '_exception', $logFunc,
                    'request: ' . json_encode($request, JSON_UNESCAPED_UNICODE)
                    .', msg: ' . $e->getMessage() . ', code: ' . $e->getCode());

                // 超时，重试
                if ($e->getCode() == LifeException::RPC_RETRY_AGAIN_LATER) {
                    if ($maxTryTimes == 1 || !$retry) {
                        PaymentApi::log("{$logFunc}:" . $e->getMessage(), Logger::WARN);
                        // 优化显示结果
                        throw new LifeException('系统繁忙,请稍后再试', LifeException::CODE_RPC_TIMEOUT, $e);
                    }
                } else {
                    PaymentApi::log("{$logFunc}:" . $e->getMessage(), Logger::ERR);
                    throw $e;
                }
            }
        } while(--$maxTryTimes > 0);

        // 如果为对象，转换成数组
        if (gettype($response) == 'object') {
            $response = $response->toArray();
        }

        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        $res = ($res === false) ? 'invalid response: ' . var_export($response, true) : mb_substr($res, 0, 1000);
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        PaymentApi::log("[resp][cost:{$elapsedTime}]{$logFunc}:" . $res, Logger::RPC);
        return $response;
    }

    protected function _handleResponse($response) {
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            throw new \Exception($response['errorMsg'], $response['errorCode']);
        }

        return [
            'errorCode' => 0,
            'errorMsg' => isset($response['errorMsg']) ? $response['errorMsg'] : 'success',
            'data' => $response['data'],
        ];
    }

    /**
     * 统一的异常处理
     */
    protected function _handleException($e, $functionName, $data = array()) {
        PaymentApi::log(ucfirst(self::$projectName) . "Service.{$functionName}, exceptionMsg:" . $e->getMessage() . ', data: ' . json_encode($data, JSON_UNESCAPED_UNICODE), Logger::ERR);

        // 需要报的错误信息
        $this->setErrorMsg($e->getMessage());
        self::$errorCode = $e->getCode();
        return [
            'errorCode' => empty(self::$errorCode) ? LifeException::CODE_PARAM_ERROR : self::$errorCode,
            'errorMsg' => $e->getMessage(),
            'data' => $data,
        ];
    }

    public function hasError() {
        return self::$error;
    }

    public function setErrorMsg($msg) {
        self::$error = true;
        self::$errorMsg = $msg;
        return true;
    }

    public function getErrorCode() {
        return self::$errorCode;
    }

    public function getErrorMsg() {
        return self::$errorMsg ? self::$errorMsg : '';
    }
}