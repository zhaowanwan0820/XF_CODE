<?php
/* LocalClientAdapter.php ---
 *
 * Filename: LocalClientAdapter.php
 * Description: Yar RPC 本地Mock Client Adapter
 * Author: zhounew
 * Created: 14-9-25 下午5:37
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup
 */

namespace NCFGroup\Common\Extensions\RPC;

use Phalcon\DI as PhalconDI;
use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
/**
 * Class LocalClientAdapter
 *
 * 利用合并backend和frontend，以直接在一个PHP进程中调用backend service，模拟RPC。
 * 好处在于可以检查出来backend写得不规范的地方，或者报错、warning等信息。
 *
 * @package NCFGroup\Common\Extensions\RPC
 */
class LocalClientAdapter extends AbstractClientAdapter
{

    protected $config;
    protected $di;

    /**
     * 构造函数
     *
     * @param PhalconDI $di
     */
    public function __construct(PhalconDI $di) {
        $this->di = $di;
    }

    /**
     * 基于父类接口继承并实现的RPC方法 - callByParams
     *
     * @param string $service 客户端传入的服务名
     * @param string $method 客户端传入的方法名
     * @param ProtoBufferBase $request 客户端传入的请求数据（可以为RequestBase，也可以为普通值类型）
     *
     * @return mixed|NULL
     */
    public function callByParams($service, $method, ProtoBufferBase $request)
    {
        $service = ucfirst($service) . 'Service';
        $class = new $service();
        if (is_callable(array($class, $method))) {
            $class->di = $this->di;
            // foreach ($this->di->getServices() as $key => $value) {
            //     if ($this->di->has($key)) {
            //         try {
            //             $class->$key = $this->di->get($key);
            //         } catch (\Exception $e) {
            //             continue;
            //         }
            //     }
            // }
            $result = call_user_func_array(array(
                $class,
                $method
            ), array($request));
            return $result;
        } else {
            $errorMsg = "No Service($service->$method) is found!";
            throw new \Exception($errorMsg);
        }

    }

    /**
     * 基于父类接口继承并实现的RPC方法 - callByObject
     * 利用反射调用本地方法，支持json object的输入参数，更加清晰
     * 鼓励用这种方法来写！
     *
     * @param Object $serviceObj 封装RPC请求参数的array对象
     *
     * @return mixed|NULL
     */
    public function callByObject($serviceObj)
    {
        if ($serviceObj['service'] && $serviceObj['method']) {
            if (!$serviceObj['args']) {
                $request = array();
            } else {
                $request = $serviceObj['args'];
            }

            $service = $serviceObj['service'];
            $method = $serviceObj['method'];

            return $this->callByParams($service, $method, $request);
        } else {
            return null;
        }
    }
}
