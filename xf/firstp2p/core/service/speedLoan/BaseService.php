<?php
/**
 * 网信速贷基类
 * @data 2017.09.13
 * @author weiwei12 weiwei12@ucfgroup.com
 */


namespace core\service\speedLoan;

use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\RequestCommon;
use libs\utils\Rpc;
use libs\utils\Monitor;

class BaseService
{
    /**
     * 请求速贷后端服务
     * @return array
     */
    public function requestCreditloan($service, $method, $request , $maxRetryTimes = 3, $timeout = 10, $connectTimeout = 10)
    {
        $beginTime = microtime(true);
        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        Logger::info("[req]CreditloanBaseService.{$className}.{$method}:" . json_encode($request, JSON_UNESCAPED_UNICODE));

        try {
            $rpc = new Rpc('creditloanRpc');
            $response = $rpc->go($service,$method,$request,$maxRetryTimes,$timeout,$connectTimeout);
        } catch (\Exception $e) {
            $exceptionName = get_class($e);
            \libs\utils\Alarm::push('creditloan_rpc_exception', $className.'_'.$method,
                    'request: '.json_encode($request, JSON_UNESCAPED_UNICODE).',ename:' .$exceptionName. ',msg: '.$e->getMessage());
            Monitor::add('CREDITLOAN_' . strtoupper($className) . '_' . strtoupper($method) . '_FAIL');
            Logger::error("CreditloanBaseService.$service.$method.$exceptionName:" . $e->getMessage());
            throw $e;
        }
        // TODO log response
        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($res == false) {
            \libs\utils\Alarm::push('creditloan_rpc_exception', $className.'_'.$method,'request: '.'time-out');
        }
        $res = ($res == false) ? 'invalid response: ' . var_export($response, true) : mb_substr($res, 0, 1000);
        $elapsedTime = round(microtime(true) - $beginTime, 3);
        Logger::info("[resp][cost:{$elapsedTime}]CreditloanBaseService.{$className}.{$method}:" . $res);
        return $response;
    }

}
