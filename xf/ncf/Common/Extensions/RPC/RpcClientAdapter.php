<?php
/* RpcClientAdapter.php ---
 *
 * Filename: RpcClientAdapter.php
 * Description: Yar RPC Client Adapter
 * Author: zhounew
 * Created: 14-9-25 下午5:37
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup
 */

namespace NCFGroup\Common\Extensions\RPC;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use NCFGroup\Common\Library\TraceSdk;
use NCFGroup\Common\Library\Logger;

/**
 * Class RpcClientAdapter
 *
 * 基于YAR远程调用的Client Adapter
 *
 * @package NCFGroup\Common\Extensions\RPC
 */
class RpcClientAdapter extends AbstractClientAdapter
{

    protected $__remoteServerUrl;

    protected $__clientId;

    protected $__client;

    protected $__secretKey;

    /**
     * 构造函数
     *
     * @param string $remoteServerUrl 远程服务Url或者ip
     * @param string $clientId 客户端传入的client id
     * @param string $secretKey 客户端传入的secretKey
     */
    public function __construct($remoteServerUrl, $clientId = "__client__", $secretKey = "__secretKey__")
    {
        $this->__remoteServerUrl = $remoteServerUrl;
        $this->__clientId = $clientId;
        $this->__secretKey = $secretKey;

        $this->__client = new \Yar_Client($this->__remoteServerUrl);

    }

    public function setConnectTimeout($timeout = 3)
    {
        $this->__client->setOpt(\YAR_OPT_CONNECT_TIMEOUT, $timeout); // in seconds
        return $this;
    }

    public function setTimeout($timeout = 5)
    {
        $this->__client->setOpt(\YAR_OPT_TIMEOUT, $timeout); // in seconds
        return $this;
    }

    public function __call($method, array $args)
    {
        $method = strval($method);
        if(method_exists($this->__client, $method)) {
            return call_user_func_array(array($this->__client, $method), $args);
        } else {
            throw new \Exception("RPC Client: method not exists");
        }
    }

    /**
     * 根据当前传入的参数，计算出来验签signature。
     *
     * @param string $service 客户端传入的服务名
     * @param string $method 客户端传入的方法名
     * @param ProtoBufferBase $request 客户端传入的请求数据
     *
     * @return string 传给服务端的签名
     */
    protected function getSign($service, $method, ProtoBufferBase $request)
    {
        $argsStr = json_encode($request);
        $hashStr = implode('|', array(
            $service,
            $method,
            $argsStr,
            $this->__clientId,
            $this->__secretKey
        ));
        $sign = sha1($hashStr);
        return $sign;
    }

    /**
     * 基于父类接口继承并实现的RPC方法 - callByParams
     *
     * @param string $service 客户端传入的服务名
     * @param string $method 客户端传入的方法名
     * @param ProtoBufferBase $request 客户端传入的请求数据
     *
     * @return mixed|NULL
     */
    public function callByParams($service, $method, ProtoBufferBase $request)
    {
        $digPoint = TraceSdk::digLogStart(__FILE__, __LINE__, 'rpc');
        if (TraceSdk::isEnable()) {
            $request->_trace_id_ = TraceSdk::getTraceID();
            $request->_rpc_id_ = TraceSdk::getChildNextRPCID();
            $request->_log_id_ = Logger::getLogId();
        }

        try {
            $sign = $this->getSign($service, $method, $request);
            $result = $this->__client->callByParams($service, $method, $request, $sign, $this->__clientId);
    
            TraceSdk::digCurlEnd($digPoint, $this->__remoteServerUrl, 'post',
                ['service'=>$service, 'method'=>$method, 'param'=>$request], '', [], 0, '', '');

            return $result;
        } catch (\Exception $ex) {
            TraceSdk::record(TraceSdk::LOG_TYPE_EXCEPTION, __FILE__, __LINE__, 'rpc', [
                'code'=>$ex->getCode(),
                'msg'=>$ex->getMessage(),
                'serverUrl' => $this->__remoteServerUrl,
                'service' => $service,
                'method' => $method,
                'request' => $request
            ]);

            throw $ex;
        }
    }

    /**
     * 基于父类接口继承并实现的RPC方法 - callByObject
     * 支持json object的输入参数，更加清晰，鼓励用这种方法来写！
     *
     * @param Object $serviceObj 封装RPC请求参数的array对象
     *
     * @return mixed|NULL
     */
    public function callByObject($serviceObj)
    {
        $digPoint = TraceSdk::digLogStart(__FILE__, __LINE__, 'rpc');
        $request = $serviceObj["args"];
        if (TraceSdk::isEnable()) {
            if (is_array($request)) {
                $request['_trace_id_'] = TraceSdk::getTraceID();
                $request['_rpc_id_'] = TraceSdk::getChildNextRPCID();
                $request['_log_id_'] = Logger::getLogId();
            } else if (is_object($request)) {
                $request->_trace_id_ = TraceSdk::getTraceID();
                $request->_rpc_id_ = TraceSdk::getChildNextRPCID();
                $request->_log_id_ = Logger::getLogId();
            }
        }

        try {
            $sign = $this->getSign($serviceObj["service"], $serviceObj["method"], $request);
            $serviceObj["sign"] = $sign;
            $serviceObj["client"] = $this->__clientId;
            $serviceObj['args'] = $request;
            $result = $this->__client->callByObject($serviceObj);
            TraceSdk::digCurlEnd($digPoint, $this->__remoteServerUrl, 'post', $serviceObj, '', [], 0, '', '');
            return $result;
        } catch (\Exception $ex) {
            TraceSdk::record(TraceSdk::LOG_TYPE_EXCEPTION, __FILE__, __LINE__, 'rpc', [
                    'code' => $ex->getCode(),
                    'msg' => $ex->getMessage(),
                    'serverUrl' => $this->__remoteServerUrl,
                    'service' => $serviceObj["service"],
                    'method' => $serviceObj["method"],
                    'request' => $request
                ]);

            throw $ex;
        }
    }
}
