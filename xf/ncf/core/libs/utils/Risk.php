<?php
/**
 * 风控对接第三方:工具类
 * @author 吕宝松 <lvbaosong@ucfgroup.com>
 */
namespace libs\utils;
use libs\utils\Logger;
use core\enum\DeviceEnum;

class Risk{

    //调用第三方风控异常情况计数key
    const KEY_SYNC_ABNORMAL_COUNT = 'RISK_SYNC_ABNORMAL_COUNT';
    const KEY_SYNC_ASYNC_ABNORMAL_COUNT = 'RISK_SYNC_ASYNC_ABNORMAL_COUNT';

    const KEY_MONITOR_DEGRADE = 'RISK_DEGRADE';
    const KEY_MONITOR_HTTP_ERROR = 'RISK_HTTP_ERROR';
    //调用第三方风控异常计数redis key 缓存过期时间
    const EXPIRE_TIME_SYNC = 120;
    const EXPIRE_TIME_ASYNC = 1800;
    //调用第三方风控异常计数阀值
    const THRESHOLD = 20;
    //第三方风控接口调用业务码,由第三方定义
    const BC_REGISTER='PAY.REG';//注册
    const BC_REAL_NAME_AUTH='PAY.SIGNED';//实名认证
    const BC_BIND = 'PAY.BIND';//绑卡
    const BC_LOGIN='PAY.LOGIN';//登录
    const BC_BID='PAY.BID';//投标
    const BC_CHANGE_PWD='PAY.CPWD';//修改密码
    const BC_WITHDRAW_CASH='PAY.CASH';//提现
    const BC_BALANCE_PAY='PAY.BPAY';//余额支付
    const BC_CHARGE='PAY.RECHARGE';//充值
    //埋点的平台
    const PF_WEB='WEB';
    const PF_API='API';
    const PF_OPEN_API='OPEN_API';
    //请求的操作类型
    const SYNC = 'SYNC';
    const ASYNC = 'ASYNC';
    const SYNC_TO_ASYNC='SYNC_TO_ASYNC';
    //请求风控审计类型,由第三方定义
    const AT_MID='mid';
    const AT_AFTER='after';


    /**
     * Curl调用第三方请求
     * @param $url string 请求url
     * @param $data array 请求数据
     * @param $type 接口调用时候的请求类型
     */
    public static function request($url,$data,$type=self::SYNC){
        $logId = $data['log_id'];
        $platform = $data['platform'];
        unset($data['log_id']);
        unset($data['platform']);
        $startTime = microtime(true);
        $data_string = json_encode($data);
        $data_string = '[' . $data_string . ']';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::getCurlTimeOutTime($type));
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8','Content-Length: ' . strlen($data_string)));

        $result = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $errorMsg = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cost = round(microtime(true)-$startTime,4)*1000;
        $returnValue = array('errorNo'=>$errorNo,'errorMsg'=>$errorMsg,'httpCode'=>$httpCode,'cost'=>$cost,'data'=>$result);
        self::timeOutHandle($type,$cost);
        
        if($httpCode!=200||$errorNo!=0){
            Logger::error('Risk-'.$type .'|'.$platform.'|'.$logId. '|post:'.$data_string.'|result:'.json_encode($returnValue));
            Monitor::add(self::KEY_MONITOR_HTTP_ERROR);
            return false;
        }
        if($GLOBALS['sys_config']['THIRD_PARTY_RISK']['DEBUG']){
            Logger::debug('Risk-'.$type .'|'.$platform.'|'.$logId. '|post:'.$data_string.'|result:'.json_encode($returnValue, JSON_UNESCAPED_UNICODE));
        }else{
            Logger::debug(implode('|',array("Risk-{$type}",$platform,$data['frms_biz_code'],$logId,'result:'.json_encode($returnValue, JSON_UNESCAPED_UNICODE))));
        }

        return $result;
    }

    /**
     * 调用第三方接口异常情况计数
     */
    public static function addAbnormalCount($key){
        if(!$key){
            return;
        }
        try {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if (!$redis) {
                Logger::error("risk to redis fail.redis is null");
                return;
            }
            $redis->INCR($key);
            switch($key){
                case self::KEY_SYNC_ASYNC_ABNORMAL_COUNT:
                    $redis->EXPIRE($key,self::EXPIRE_TIME_ASYNC);
                    break;
                case self::KEY_SYNC_ABNORMAL_COUNT:
                    $redis->EXPIRE($key,self::EXPIRE_TIME_SYNC);
                    break;
            }
        } catch (\Exception $e) {
            Logger::error("risk to redis fail." . $e->getMessage());
        }
    }

    /**
     * 获取调用第三方接口异常计数
     */
    public static function getAbnormalCount($key){
        if(!$key){
            return 0;
        }
        try {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if (!$redis) {
                Logger::error("risk to redis fail.redis is null");
                return 0;
            }
            return  $redis->get($key);
        } catch (\Exception $e) {
            Logger::error("risk to redis fail." . $e->getMessage());
        }
    }


    /**
     * 调用第三方接口超时计数
     * @param string $type 请求类型
     * @param int $cost 耗时
     */
    public static function timeOutHandle($type,$cost){
        if($type==Risk::SYNC && $cost>=Risk::getCurlTimeOutTime(Risk::SYNC)){
            Risk::addAbnormalCount(Risk::KEY_SYNC_ABNORMAL_COUNT);
        }else if($type==Risk::SYNC_TO_ASYNC && $cost>=Risk::getCurlTimeOutTime(Risk::SYNC)){
            Risk::addAbnormalCount(Risk::KEY_SYNC_ASYNC_ABNORMAL_COUNT);
        }
    }

    /**
     * 获取配置的curl请求超时时间
     */
    public static function getCurlTimeOutTime($type){
        $config = array('SYNC'=>100,'SYNC_TO_ASYNC'=>100,'ASYNC'=>100);
        return  array_key_exists($type,$config)? $config[$type]:100;
    }

    /**
     * 获取配置的服务类
     */
    public static function getServiceClass($serviceType){
        $config=array(
            'PAY.REG'=>'core\service\risk\RiskRegisterService',
            'PAY.SIGNED'=>'core\service\risk\RiskRealNameAuthService',
            'PAY.LOGIN'=>'core\service\risk\RiskLoginService',
            'PAY.BID'=>'core\service\risk\RiskBidService',
            'PAY.CPWD'=>'core\service\risk\RiskChangePwdService',
            'PAY.CASH'=>'core\service\risk\RiskWithdrawCashService',
            'PAY.RECHARGE'=>'core\service\risk\RiskChargeService',
            'PAY.BIND'=>'core\service\risk\RiskBindService',
        );
        return  array_key_exists($serviceType,$config)? $config[$serviceType]:false;
    }

    /**
     * 生成请求序列号
     */
    public static function genSn(){
        return time().mt_rand(10000,99999);
    }
    /**
     * 同步转异步判断
     * @return boolean
     */
    public static function isForceSyncToAsync(){
        $syncAbnormalCount = Risk::getAbnormalCount(Risk::KEY_SYNC_ABNORMAL_COUNT);
        $syncToAsyncAbnormalCount = Risk::getAbnormalCount(Risk::KEY_SYNC_ASYNC_ABNORMAL_COUNT);
        if(($syncAbnormalCount&&$syncAbnormalCount>=Risk::THRESHOLD)||$syncToAsyncAbnormalCount){//同步状态达到阀值或同步转异步的非正常情况存在值均强制异步执行
            Monitor::add(Risk::KEY_MONITOR_DEGRADE);
            return true;
        }
        return false;
    }

    /**
     * 获取第三方所需指纹参数
     */
    public static function getFinger(){
        $fingerPrint = '';
        if(isset($_COOKIE["FRMS_FINGERPRINT"])){
            $fingerPrint =  $_COOKIE["FRMS_FINGERPRINT"];
        }else if(isset($_SERVER['HTTP_FINGERPRINT'])){
            $fingerPrint =  $_SERVER['HTTP_FINGERPRINT'];
        }

        //风控升级后web设备指纹更改为取cookie的BSFIT_DEVICEID字段,即设备指纹外码
        if(isset($_COOKIE["BSFIT_DEVICEID"])) {
            $fingerPrint = $_COOKIE["BSFIT_DEVICEID"];
        }

        return $fingerPrint;
    }

    /**
     * 获取设备指纹的过期时间,web端读cookie,其他端需要传的话需要设置header
     * @return string
     */
    public static function getFingerExpiration() {
        $expiration = '';
        if (isset($_COOKIE['BSFIT_EXPIRATION'])) {
            $expiration = $_COOKIE['BSFIT_EXPIRATION'];
        } elseif (isset($_SERVER['HTTP_BSFIT_EXPIRATION'])) {
            $expiration = $_SERVER['HTTP_BSFIT_EXPIRATION'];
        }

        return $expiration;
    }

    /**
     * 获取设备指纹cookiename,web端读cookie,其他端需要传的话需要设置header
     * @return string
     */
    public static function getFingerCookieName() {
        $cookieName = '';
        if (isset($_COOKIE['BSFIT_OkLJUJ'])) {
            $cookieName = $_COOKIE['BSFIT_OkLJUJ'];
        } elseif (isset($_SERVER['HTTP_BSFIT_OKLJUJ'])) {
            $cookieName = $_SERVER['HTTP_BSFIT_OKLJUJ'];
        }

        return $cookieName;
    }

    public static function getDevice($data){
        $device = DeviceEnum::DEVICE_UNKNOWN;
        if (empty($data)) {
            // 如果user_agent都不存在
            if (empty($_SERVER['HTTP_USER_AGENT'])) {
                return $device;
            }

            $data = $_SERVER['HTTP_USER_AGENT'];
        }

        if (stripos($data, 'Android') !== false) {
            $device = DeviceEnum::DEVICE_ANDROID;
        } elseif (stripos($data, 'iOS') !== false) {
            $device = DeviceEnum::DEVICE_IOS;
        } elseif (stripos($data, 'WAP') !== false) {
            $device = DeviceEnum::DEVICE_WAP;
        }

        return $device;
    }
    /**
     * 获取请求第三方的url
     */
    public static function getRequestUrl($data=''){
        if($data==Risk::AT_AFTER){
            $url = $GLOBALS['sys_config']['THIRD_PARTY_RISK']['HOST_NOTIFY'];
        }else{
            $url = $GLOBALS['sys_config']['THIRD_PARTY_RISK']['HOST'];
        }
        return $url;
    }
}
