<?php
/**
 *
 * @author wangfei5<wangfei5@ucfgroup.com>
 */
namespace core\service\curlHook;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use core\service\BaseService;
use core\event\ThirdPartyHookEvent;
use libs\utils\Logger;

class ThirdPartyHookService extends BaseService {


    protected $method = 'post';

    // 自己调用
    public function setMethodGet(){
        $this->method = 'get';
    }

    // 检查准入的条件
    protected function canRun($input){
        return true;
    }

    // 异步执行,直接检查
    public function asyncCall($url, $params, $channel){
        if($this->canRun($params)){
            $taskService = new GTaskService();
            $event = new ThirdPartyHookEvent($url, $params, $channel);
            $res = $taskService->doBackground($event, 10);
        }
        return $res;
    }

    // 同步执行，自行检查
    public function syncCall($url, $params, $channel){
        try{
            if(!$this->canRun($params)){
                $errMsg = sprintf('准入不通过 [url:%s][params:%s]',$url,json_encode($params));
                // 这是一个bug，准入不通过不应该进行重试，避免资源浪费。此时的错误号应该为0
                throw new \Exception($errMsg,0);
            }
            $params = $this->checkParams($url,$params);
            $params = $this->signParams($params);
            $ret = $this->callCurl($url,$params,$this->method);
            // 稍微过滤一下
            $retLog = preg_replace("#[^A-Za-z0-9_\"{}:,\\\]#",'',substr($ret,0,200));
            $checkRet = $this->checkRet($ret,$params);
            if($checkRet === false){
                $errMsg = sprintf("通知返回检查异常,  notify_url:%s | params:%s | ret:%s",$url,json_encode($params),$retLog);
                throw new \Exception($errMsg,-997);
            }
            $this->writeLog(sprintf("通知success,  notify_url:%s | params:%s | ret:%s",$url,json_encode($params),$retLog),$channel);
            return 0;
        }catch(\Exception $e){
            $this->writeLog($e->getMessage(), $channel);
            return $e->getCode();
        }
    }
    // 默认不检查返回值
    protected function checkRet($ret,$params){
        return true;
    }

    // 默认不需要签名
    protected function signParams($params){
       return $params;
    }

    /**
    * 检查参数并加密参数
    */
    protected function checkParams($url, $params){
        // 检查参数是否为空,不包含布尔类型的
        foreach( $params as $key=>$one ){
            if(empty($one)){
                unset($params[$key]);
            }
        }
        return $params;
    }


    protected function setCurlHeader($ch){
        return true;
    }

    protected function callCurl($url, $params, $method){
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        $this->setCurlHeader($ch);
        if($method == 'post'){
            curl_setopt ( $ch, CURLOPT_POST, 1 );
        }
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS,$params );
        // 毫秒超时一定要设置这个
        curl_setopt ( $ch, CURLOPT_NOSIGNAL, 1);
        // 设置为10秒超时
        curl_setopt ( $ch, CURLOPT_TIMEOUT_MS, 10000);
        if (substr($url, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $ret = curl_exec ( $ch );
        if(curl_errno($ch) != 0 ){
            $errMsg = sprintf('curl [error:%s][url:%s][params:%s]',curl_error($ch),$url,json_encode($params));
            throw new \Exception($errMsg,-3);
        }
        curl_close ( $ch );
        return $ret;
    }

    /*
     * 签署合同绝大场景是异步的，所以需要进行日志打印
     */
    public function writeLog($str, $channel) {
        Logger::wLog("ThirdPartyHookEvent: " . $str . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH. "/logger/" ."thirdPartNotify_{$channel}_" . date('Y_m_d') .'.log');
    }
}
