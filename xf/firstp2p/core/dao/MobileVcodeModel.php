<?php
/**
 * 验证码模型
 *
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 **/

namespace core\dao;


class MobileVcodeModel extends BaseModel {
    
    
   /**
    * 获取当天同一号发送的次数
    * @param string $mobile_phone
    * @return boolean
    */
   public function getMobilePhoneVcodeNum($mobile_phone = ''){
       if (!is_numeric($mobile_phone) || empty($mobile_phone)){
           return false;
       }
       $nowtime = get_gmtime();
       $current_date = strtotime(date('Y-m-d',$nowtime));
       $start_date = strtotime(date('Y-m-d 00:00:00',$current_date));
       $end_date = strtotime(date('Y-m-d 23:59:59',$current_date));
       $where = "mobile_phone = '%s' AND create_time > '%s' AND create_time < '%s'";
       $where = sprintf($where,$this->escape($mobile_phone),$this->escape($start_date),$this->escape($end_date));
       $count = $this->count($where);
   }
   /**
    * 获取验证码
    * @param string $mobile_phone
    * @param int $seconds 客户端和pc端不一样
    * @return boolean
    */
   public function getMobilePhoneTimeVcode($mobile_phone = '',$seconds=180)
   {
       if (!is_numeric($mobile_phone) || empty($mobile_phone)){
           return false;
       }
       $current_date = get_gmtime();		// 当前时间
       $end_date = date('Y-m-d H:i:s',$current_date-$seconds);	//180前时间
       $end_date = strtotime($end_date);
       $where = "mobile_phone = '%s' AND create_time > '%s' AND create_time < '%s'";
       $where = sprintf($where,$this->escape($mobile_phone),$this->escape($end_date),$this->escape($current_date));
       $row = $this->findBy($where);
       if (is_object($row)){
            return $row->getRow();
       }else{
           return false;
       }
   }
  
}
// END class MsgCategory extends BaseModel
