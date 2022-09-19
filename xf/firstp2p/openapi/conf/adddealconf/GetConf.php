<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/16
 * Time: 9:46
 */
namespace openapi\conf\adddealconf;
use libs\utils\Logger;
class GetConf {
    private $namespace_prefix = 'openapi\conf\adddealconf\\';

    /**
     * @param $service_name
     * @param array $args
     * @param string $path
     * @return mixed
     */
    public function getConf($service_name, $args = array(),$path = '')
    {
        if (!empty($path)) {
            $this->namespace_prefix = $this->namespace_prefix.$path.'\\';
        }
        list($class_name, $func) = explode('\\', $service_name);
        $class_name = $this->namespace_prefix.$class_name;
        try {
            $reflectionMethod = new \ReflectionMethod($class_name, $func);
            return $reflectionMethod->invokeArgs(new $class_name(), $args);
        } catch (\Exception $e) {
            logger::error(implode('|',array(__CLASS__,__FUNCTION__,'errorMSg:',$e->getMessage())));
            return call_user_func_array(array(new $class_name(), $func), $args);
        }
    }
}