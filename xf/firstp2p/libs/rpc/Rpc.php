<?php
/**
 * Rpc class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace libs\rpc;

use libs\utils\Logger;

/**
 * 用于执行Rpc远程调用
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Rpc
{
    /**
     * rpc服务类命名空间前缀
     *
     * @var string
     **/
    public $namespace_prefix = "core\service\\";
    /**
     * 执行本地service调用
     *
     * @param string $service_name 服务接口名称
     * @param array $args 对应service接口的方法参数
     * @param string $module service服务对应的core\service\下的目录
     * @return void
     **/
    public function local($service_name, $args = array(), $module = '')
    {
        list($class_name, $func) = explode('\\', $service_name);
        if (empty($module)) {
            $class_name = $this->namespace_prefix.$class_name;
        } else {
            $class_name = $this->namespace_prefix.$module."\\".$class_name;
        }
        try {
            $reflectionMethod = new \ReflectionMethod($class_name, $func);
            return $reflectionMethod->invokeArgs(new $class_name(), $args);
        } catch (\Exception $e) {
            return call_user_func_array(array(new $class_name(), $func), $args);
        }
    }

    /**
     * 执行远程service调用
     *
     * @param string $service_name 服务接口名称
     * @param array $args 对应service接口的方法参数
     * @return void
     **/
    public function remote($service_name, $args = array())
    {
        throw new \Exception("远程service调用尚未实现");
    }


    private function _log($service_name, $args = array()) {
        if (!isset($GLOBALS['requestId'])) {
            $GLOBALS['requestId'] = intval((ip2long($_SERVER['REMOTE_ADDR'])+time())/rand(1,100));
        }
        $trace = debug_backtrace();
        $log = "requestId:{$GLOBALS['requestId']} service:{$service_name}  ";
        if (is_array($args) && count($args) > 0) {
            $log .= "args:".json_encode($args);
        }
        
        if (isset($GLOBALS['user_info']['id'])) {
            $log .= ' uid:'.$GLOBALS['user_info']['id'];
        }
        if (isset($trace[1]['file'])) {
            $file = str_replace(APP_ROOT_PATH,'',$trace[1]['file']);
            $log .= " file:{$file} line:".$trace[1]['line'];
        }
        Logger::wLog($log,Logger::RPC);
    }
} // END class Rpc
