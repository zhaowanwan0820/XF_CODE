<?php

/**
 * 存储手机验证码
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 */

namespace core\data;

use libs\utils\Logger;
use libs\utils\Monitor;

class MobileCodeData extends BaseData {
   private $prefix_key = array(
                1 => 'pc_mobile_code_key_', // pc端的key
                0 => 'mobile_phone_mobile_code_key_' // 手机端的key
           );
   
   /**
    * 获取验证码
    * @param int $mobile 手机号
    * @param int $isPc 默认1是pc端，0是手机端
    * @return bool | 正确返回验证码
    */
   public function getMobileCode($mobile,$isPc=1){
       if (!is_numeric($mobile) || $mobile < 0 || !isset($this->prefix_key[$isPc])){
           return false;
       }

       $redis = \SiteApp::init()->dataCache->getRedisInstance();
       $result = $redis->get($this->prefix_key[$isPc] . $mobile);

       $remains = $redis->ttl($this->prefix_key[$isPc] . $mobile);
       $cost = $remains > 0 ? (180 - $remains) : 180;
       Logger::info("GET CODE. moblie:{$mobile}, code:{$result}, isPc:{$isPc}, cost:{$cost}");
       Monitor::add("GET_MOBILE_CODE");

       return $result;
   }
   
   /**
    * 设置验证码
    * @param int $mobile 手机号
    * @param int $code 验证码
    * @param int $expirationTime 过期时间  ，默认180秒
    * @param int $isPc 默认1是pc端，0是手机端
    * @return bool true|false
    */
   public function setMobileCode($mobile,$code,$expirationTime=180,$isPc=1){
       if (empty($code) || !isset($this->prefix_key[$isPc]) || !is_numeric($expirationTime)){
           return false;
       }
       
       $redis = \SiteApp::init()->dataCache->getRedisInstance();

       Logger::info("SET CODE. moblie:{$mobile}, code:{$code}, isPc:{$isPc}");
       Monitor::add("SET_MOBILE_CODE");

       return $redis->setEx($this->prefix_key[$isPc] . $mobile, $expirationTime, $code);
   }
   
   /**
    * 清除验证码
    * @param int $mobile 手机号
    * @param int $isPc 默认1是pc端，0是手机端
    * @return bool
    */
   public function delMobileCode($mobile,$isPc=1){
       if (empty($mobile) || !isset($this->prefix_key[$isPc])) return false;

       $redis = \SiteApp::init()->dataCache->getRedisInstance();
       //在这个阶段，验证码都使用成功了
       $remains = $redis->ttl($this->prefix_key[$isPc] . $mobile);
       $cost = $remains > 0 ? (180 - $remains) : 180;
       Logger::info("DEL CODE. moblie:{$mobile}, isPc:{$isPc}, cost:{$cost}");
       Monitor::add("DEL_MOBILE_CODE");
       return $redis->del($this->prefix_key[$isPc] . $mobile);
   }
   
}
