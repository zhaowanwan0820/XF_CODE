<?php

namespace core\service\oto;

use core\service\BaseService;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\exception\O2OException;

/**
 * O2O的Rpc请求底层服务
 */
class O2ORpcService extends BaseService {
    public static $error = false;
    public static $errorMsg = '';
    public static $errorCode = 0;

    /**
     * 统一的异常处理，保持和以前的处理方式兼容
     */
    protected function _handleException($e, $functionName, $data = array()) {
        PaymentApi::log("O2OService.$functionName:".$e->getMessage().', data: '.json_encode($data, JSON_UNESCAPED_UNICODE), Logger::ERR);

        // 需要报的错误信息
        $this->setErrorMsg($e->getMessage());
        self::$errorCode = $e->getCode();
        return false;
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

    public function __construct() {
        if (!isset($GLOBALS['o2oRpc']) || !($GLOBALS['o2oRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)) {
            $o2oRpcConfig = $GLOBALS['components_config']['components']['rpc']['o2o'];
            $GLOBALS['o2oRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($o2oRpcConfig['rpcServerUri'],
                $o2oRpcConfig['rpcClientId'], $o2oRpcConfig['rpcSecretKey']);
        }
    }

    /**
     * 请求o2o方法
     *
     * @param $service string 服务名
     * @param $method string 方法名
     * @param $request mixed 请求request对象
     * @param $timeOut int 超时时间
     * @param $retry bool 是否重试
     * @access public
     * @return mixed
     */
    public function requestO2O($service, $method, $request, $timeOut = 3, $retry = true) {
        if (app_conf('O2O_SERVICE_ENABLE') == 0) {
            throw new \Exception('O2O Service is down');
        }

        $beginTime = microtime(true);
        // 考虑到统一处理的便捷，后期可以考虑集成到phalcon-common框架中
        if ($request instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase) {
            // 在底层请求里面统一传递，o2o对分站的支持
            $request->_site_id_ = \libs\utils\Site::getId();
            // 跨系统日志id的统一
            $request->_log_id_ = Logger::getLogId();
        }

        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        $logFunc = 'O2OService.'.$className.'.'.$method;
        PaymentApi::log("[req]{$logFunc}:".json_encode($request, JSON_UNESCAPED_UNICODE), Logger::RPC);

        // 增加重试
        $maxTryTimes = 3;
        $retryTimes = 0;
        do {
            try {
                if ($maxTryTimes != 3) {
                    ++$retryTimes;
                    PaymentApi::log("{$logFunc} retry {$retryTimes}", Logger::WARN);
                }
                $GLOBALS['o2oRpc']->setTimeout($timeOut);
                $response = $GLOBALS['o2oRpc']->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
                ));
                if (!empty($response) || !$retry) {
                    break;
                }
            } catch (\Exception $e) {
                \libs\utils\Alarm::push('o2o_exception', $logFunc,
                    'request: '.json_encode($request, JSON_UNESCAPED_UNICODE)
                    .', msg: '.$e->getMessage().', code: '.$e->getCode());

                // 超时，重试
                if ($e->getCode() == \NCFGroup\Protos\O2O\RPCErrorCode::RPC_RETRY_AGAIN_LATER) {
                    if ($maxTryTimes == 1 || !$retry) {
                        PaymentApi::log("{$logFunc}:".$e->getMessage(), Logger::WARN);
                        // 优化显示结果
                        throw new \core\exception\O2OTimeoutException('系统繁忙,请稍后再试', O2OException::CODE_RPC_TIMEOUT, $e);
                    }
                } else {
                    PaymentApi::log("{$logFunc}:".$e->getMessage(), Logger::ERR);
                    throw $e;
                }
            }
        } while(--$maxTryTimes > 0);

        // 如果为对象，转换成数组
        if (gettype($response) == 'object') {
            $response = $response->toArray();
        }

        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        $res = ($res === false) ? 'invalid response: '.var_export($response, true) : mb_substr($res, 0, 1000);
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        PaymentApi::log("[resp][cost:{$elapsedTime}]{$logFunc}:".$res, Logger::RPC);
        return $response;
    }
}
