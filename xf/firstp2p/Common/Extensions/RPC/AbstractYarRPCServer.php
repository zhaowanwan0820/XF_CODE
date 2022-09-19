<?php
/* AbstractYarRPCServer.php ---
 *
 * Filename: AbstractYarRPCServer.php
 * Description: Yar RPC Server的抽象类
 * Author: zhounew
 * Created: 14-9-25 下午5:37
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup
 */

namespace NCFGroup\Common\Extensions\RPC;

use NCFGroup\Common\Library\PhalconLib;
use NCFGroup\Common\Library\TextLib;
use NCFGroup\Common\Library\HttpLib;
use NCFGroup\Common\Extensions\Varz\VarzAdapter;
use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Common\Library\TraceSdk;

use Phalcon\Logger\Adapter as PhalconLogAdapter;
use Phalcon\Config as PhalconConfig;
use Phalcon\DI as PhalconDI;
use Phalcon\Db\Adapter\Pdo as PhalconPdo;

/**
 * Class YarRPCServer
 *
 * 利用Yar扩展来做RPC调用，协议基于HTTP，传输层协议可以为msgpack或者php serialization.
 *
 * @package NCFGroup\Common\Extensions\RPC
 */
abstract class AbstractYarRPCServer implements \Phalcon\Events\EventsAwareInterface
{

    protected $config = null;

    protected $di = null;

    protected $varzService = null;

    protected $logger = null;

    protected $isInit = false;

    protected $phpOnly = false;

    protected $_eventsManager;

    public function setEventsManager($eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }

    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * 实例化子类可以通过覆盖书写该方法，建立自己的backend server。
     *
     * @return mixed
     */
    abstract protected function initialize();

    /**
     * 初始化函数
     *
     * @param PhalconConfig $config Phalcon配置信息对象
     * @param PhalconDI $di Phalcon DI对象（用于注入）
     * @param PhalconLogAdapter $logger Phalcon的Log对象
     * @param VarzAdapter $varz Varz对象，用于程序数据探针
     *
     * @throws \Exception
     */
    protected function init(PhalconConfig $config, PhalconDI $di, PhalconLogAdapter $logger = null, VarzAdapter $varz = null)
    {
        if (empty($config) || empty($di)) {
            $errorMsg = '"config" or "db" or "di" can not be empty!';
            throw new \Exception($errorMsg);
        }
        $this->config = $config;
        $this->di = $di;
        $this->logger = $logger;
        $this->varzService = $varz;
        $this->isInit = true;
    }

    /**
     * 内部封装的输出error信息
     *
     * @param mixed $msg
     */
    private function error($msg)
    {
        if ($this->logger) {
            PhalconLib::error($this->logger, $msg, 1);
        }
    }

    /**
     * 内部封装的输出debug信息
     *
     * @param mixed $msg
     */
    private function debug($msg)
    {
        if ($this->logger) {
            PhalconLib::debug($this->logger, $msg, 1);
        }
    }

    /**
     * 内部封装的添加varz函数
     *
     * @param string $variable varz变量名
     * @param int $inc varz的增量
     */
    private function increaseVarz($variable, $inc = 1)
    {
        if ($this->varzService) {
            $this->varzService->increaseVarz($variable, $inc);
        }
    }

    /**
     * 对当前传入的request proto进行annotation检查
     * 当前检查为2点：
     * 1、Request对象里面的每个参数和method里面的annotation的class参数要一一对应。
     * 2、Request对象里面如果有required annotation，则必须有值，否则不予处理。
     * @TODO(zhounew): 优化这个函数，提高其鲁棒性。当request为基本类型时，也要能work，目前是不work的。
     *
     * @param string $service 客户端传入的服务名
     * @param string $method 客户端传入的方法名
     * @param ProtoBufferBase $request 客户端传入的请求数据（可以为RequestBase，也可以为普通值类型）
     *
     * @return bool 检查通过，则为true; 反之，则为false。
     * @throws \Exception
     */
    protected function requestCheck($service, $method, ProtoBufferBase $request)
    {
        $serviceReflection = new \ReflectionClass($service);
        $methodReflection = $serviceReflection->getMethod($method);
        $methodReflection->getDocComment();

        $methodAnotation = $methodReflection->getDocComment();
        $matchResult = TextLib::getStringBetween($methodAnotation, "@param", "$");
        $requestClassName = $matchResult[0];
        try {
            if ($requestClassName && $requestClassName != "SimpleRequestBase") {
                $requestClassReflection = new \ReflectionClass($requestClassName);
                foreach ($requestClassReflection->getProperties() as $requestProperty) {
                    $property = $requestProperty->getName();
                    if (!property_exists($request, $property)) {
                        throw new \Exception("The '$property' is not in your request($requestClassName)! The request is: " . json_encode($request));
                    }
                    $propertyComment = $requestProperty->getDocComment();
                    $propertyLimitation = '';
                    if (stripos($propertyComment, 'optional') !== false) {
                        $propertyLimitation = 'optional';
                    } elseif (stripos($propertyComment, 'required') !== false) {
                        $propertyLimitation = 'required';
                        return $this->checkRequired($request, $property);
                    }

                    if (empty($propertyLimitation)) {
                        throw new \Exception("$requestClassName should have limitation annotation for its property '$property'!");
                    }
                }
            }
        } catch(\Exception $e) {
            // nothing ...
        }

        return true;
    }


    private function checkRequired($request, $property)
    {
        if (!isset($request->$property)) {
            $method = 'get' . ucfirst($property);
            if (method_exists($request, $method)) {
                $value = $request->{$method}();
                if (!is_null($value)) {
                    return true;
                }
            }
            throw new \Exception("The '$property' in your request(" . get_class($request) . ") should be required! The request is: " . json_encode($request));
        }
        return true;
    }

    /**
     * 对客户端传输的request进行ip检查，如果在我们的白名单里面，则通过。否则，则不通过。
     *
     * @return bool 检查通过结果
     */
    protected function ipCheck()
    {
        if ($this->config->application->ipRestriction) {
            $clientIp = strval(HttpLib::getClientIp());
            // 这个是坑，因为PhalconConfig会自动把array转成对象，所以我们要用到array
            // 的时候，要转换回来。
            $whiteList = (array)($this->config->application->ipWhitelist);

            //支持已*为通配符的ip地址段
            $checkRegexp = implode('|', str_replace( array('*','.'), array('\d{1,3}','.') ,$whiteList));
            if (empty($whiteList) || (!preg_match("/^(".$checkRegexp.")$/", $clientIp))){
                return false;
            }

        }
        return true;
    }

    /**
     * 对传入的Request做签名校验，如果签名正确，则通过；否则，不予通过。
     *
     * @param string $service 客户端传入的服务名
     * @param string $method 客户端传入的方法名
     * @param ProtoBufferBase $request 客户端的请求数据（可以为RequestBase，也可以为普通值类型）
     * @param string $sign 客户端传入的签名数据
     * @param string $client 客户端传入的client id
     *
     * @return bool
     */
    protected function signCheck($service, $method, ProtoBufferBase $request, $sign, $client)
    {
        if ($this->config->application->clientRestriction) {
            $secretKey = $this->config->application->secretKey->$client;
            $argsStr = json_encode($request);
            $hashStr = implode('|', array(
                $service,
                $method,
                $argsStr,
                $client,
                $secretKey
            ));
            //$this->error($hashStr);
            $expectedSign = sha1($hashStr);
            if ($expectedSign == $sign) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 基于父类接口继承并实现的RPC方法 - callByParams
     *
     * @param string $service 客户端传入的服务名
     * @param string $method 客户端传入的方法名
     * @param (array | ProtoBufferBase) $request 客户端的请求数据（可以为RequestBase，也可以为普通值类型）
     * @param string $sign 客户端传入的签名数据
     * @param string $client 客户端传入的client id
     *
     * @throws \Exception
     */
    public function callByParams($service, $method, $request, $sign = '__sign__', $client = '__client__')
    {
        $yarDigPoint = TraceSdk::digLogStart(__FILE__, __LINE__, 'rpc');

        if (!$this->isInit) {
            $this->initialize();
        }
        // Service类全名
        $service = ucfirst($service) . 'Service';

        $service = trim($service);
        $method = trim($method);

        if (empty($service) || empty($method) || empty($sign) || empty($client)) {
            $errorMsg = "Service($service) or Method($method) or Signature($sign) or Client($client) is missing!";
            $this->error($errorMsg);
            $this->increaseVarz('param_missing_exception', 1);
            throw new \Exception($errorMsg);
        }

        if($request instanceof ProtoBufferBase) {
            // all rights, do nothing
            $this->phpOnly = true;
        } elseif(is_array($request)) {
            $tmp = $request;
            // 获取第一个参数类型
            $param = new \ReflectionParameter(array($service, $method), 0);
            if($param->getClass()) {
                $class = $param->getClass()->getName();
                $request = new $class();
                $request->softClone($tmp);
            } else {
                throw new \Exception("Service class:method definition is invalid. Info: {$service}:{$method}");
            }
        } else {
            throw new \Exception("Your input is not allowed. Request: " . json_encode($request));
        }

        // 这里进行注入
        if ($request->_trace_id_ && $request->_rpc_id_) {
            TraceSdk::injectHeaders($request->_trace_id_, $request->_rpc_id_);
        }

        if ($request->_log_id_) {
            Logger::setLogId($request->_log_id_);
        }

        if (!empty($request)) $this->requestCheck($service, $method, $request);

        if (!$this->ipCheck()) {
            $clientIp = HttpLib::getClientIp();
            $errorMsg = "Your IP ($clientIp) is not in the backend IP whitelist!";
            $this->increaseVarz('ip_restrict_exception', 1);
            $this->error($errorMsg);
            throw new \Exception($errorMsg);
        }

        // if (!$this->signCheck($service, $method, $request, $sign, $client)) {
        //     $errorMsg = "Your client($client) and signature ($sign) is not correct!";
        //     $this->error($errorMsg);
        //     $this->increaseVarz('signature_restrict_exception', 1);
        //     throw new \Exception($errorMsg);
        // }

        $class = new $service();

        if (is_callable(array($class, $method))) {
            $class->di = $this->di;
            // $this->debug('finish');
            try {
                $actionData = array('class'=>$service, 'method'=>$method, 'request'=>$request);
                if ($this->_eventsManager) {
                    $this->_eventsManager->fire('rpc:beforeExecuteAction', $this, $actionData);
                }
                $result = call_user_func_array(array($class, $method), array($request));
                if ($this->_eventsManager) {
                    $this->_eventsManager->fire('rpc:afterExecuteAction', $this, $actionData);
                }
            } catch(\Exception $exception) {
                $error = $exception->getFile() . ':' . $exception->getLine() . PHP_EOL . $exception->getTraceAsString();
                if(method_exists($exception,"getLevel")) {
                    Logger::log($error,$exception->getLevel());
                } else {
                    Logger::error($error);
                }
                throw $exception;
            }
            $debugMsg = "RPC call is invoked for $service->$method(). \n[Request]:\n" . json_encode($request) . "\n[Response]:\n" . json_encode($result);
            $this->debug($debugMsg);
            //$this->increaseVarz('total_requests', 1);
            //$this->increaseVarz("callservice_{$service}.{$method}", 1);
            TraceSdk::digLogEnd($yarDigPoint, $actionData);
            if($this->phpOnly == true) {
                return $result;
            } else {
                return $result->toArray();
            }
        } else {
            $errorMsg = "No Service($service->$method) is found!";
            $this->error($errorMsg);
            $this->increaseVarz('service_notfound_exception', 1);
            throw new \Exception($errorMsg);
        }
    }

    /**
     * 基于父类接口继承并实现的RPC方法 - callByObject
     * 用一个array去封装RPC请求参数。
     *
     * @param array $serviceObj 封装RPC请求参数的array对象
     * @throws \Exception
     */
    public function callByObject($serviceObj)
    {
        if (!$this->isInit) {
            $this->initialize();
        }
        if ($serviceObj['service'] && $serviceObj['method']) {
            if (!$serviceObj['args']) {
                $request = array();
            } else {
                $request = $serviceObj['args'];
            }

            $service = $serviceObj['service'];
            $method = $serviceObj['method'];

            $sign = !empty($serviceObj['sign']) ? $serviceObj['sign'] : '__sign__';
            $client = !empty($serviceObj['client']) ? $serviceObj['client'] : '__client__';

            return $this->callByParams($service, $method, $request, $sign, $client);
        } else {
            $service = $serviceObj['service'];
            $method = $serviceObj['method'];
            $sign = $serviceObj['sign'];
            $client = $serviceObj['client'];
            $errorMsg = "Service($service) or Method($method) or Args or Signature($sign) or Client($client) is missing!";
            $this->error($errorMsg);
            $this->increaseVarz('param_missing_exception', 1);
            throw new \Exception($errorMsg);
        }
    }
}

?>
