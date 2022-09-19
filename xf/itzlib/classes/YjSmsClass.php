<?php
class YjSmsClass{
    protected static $sendUrl = "https://api.youjiemall.com/hh/hh.sms.send";
    protected static $alertUrl = "https://www.zichanhuayuan.com/yzm/sms/send/alert/msg";
    protected static $_config = Null;

    public function __construct()
    {
        //载入配置
        $_configPath = dirname(dirname(__FILE__)) . '/config/sms.php';
        if (file_exists($_configPath)) {
            self::$_config = include($_configPath);
        }
    }

    /**
     * 有解短信通知
     * @param $remind
     * @return mixed
     */
    public static function sendToUser($remind){
        $return_result['code'] = 0;
        //非空校验
        if(empty($remind) || !is_array($remind)){
            Yii::log("sendToUser params error", "error", __CLASS__);
            $return_result['code'] = 1001;
            return $return_result;
        }
        
        //手机号格式校验
        if(!isset($remind['mobile']) || !FunctionUtil::IsMobile($remind['mobile'])){
            Yii::log("sendToUser mobile[{$remind['mobile']}] error", "error", __CLASS__);
            $return_result['code'] = 1002;
            return $return_result;
        }

        //模板code校验
        $yj_sms_code_list = self::$_config['yj_sms_code_list'];
        if(empty($remind['sms_code']) || empty($yj_sms_code_list) || !in_array($remind['sms_code'], $yj_sms_code_list)){
            Yii::log("sendToUser sms_code not in yj_sms_code_list ", "error", __CLASS__);
            $return_result['code'] = 1003;
            return $return_result;
        }

        //发送频次限制
        $mark = !empty($remind['order_no']) ? $remind['order_no'] : '';
        if(self::SpeedCheck($remind['phone'], $mark) == false){
            Yii::log("fast send of msg {$remind['phone']}", "error", __CLASS__);
            $return_result['code'] = 1004;
            return $return_result;
        }
        //发送
        $result = CurlService::getInstance()->yjRequest(self::$sendUrl, json_encode($remind), 'post');
        if($result == false || $result['data']['code'] != 0){
            //资产花园短信报警
            self::fundAlarm("sms_code：{$remind['sms_code']} error，mobile：{$remind['mobile']}；");
            Yii::log("send sms error, yjRequest return:".print_r($result,true), "error", __CLASS__);
            $return_result['code'] = 1005;
            return $return_result;
        }
        return $return_result;
    }

    /**
     * 资金报警
     * @param $phone
     * @param $error_info
     * @param $mark
     * @return mixed
     */
    public static function fundAlarm($error_info, $mark='zj', $phone='15810571697'){
        //返回
        $run_result['code'] = 0;
        //非空校验
        if(empty($error_info)){
            Yii::log("fundAlarm params error", "error", __CLASS__);
            $run_result['code'] = 1001;
            return $run_result;
        }
        //发送频次限制
        if(self::zjSpeedCheck($phone, $mark) == false){
            Yii::log("fast send of msg { $phone }", "error", __CLASS__);
            $run_result['code'] = 1007;
            return $run_result;
        }

        //报警发送
        $result = CurlService::getInstance()->AgRequest(self::$alertUrl,'GET',['mobile'=>$phone,'error'=>$error_info]);
        if(!isset($result['status']) || $result['status']!=200){
            Yii::log("send sms error:".print_r($result,true)." ".print_r($phone,true),"error");
            $run_result['code'] = 1008;
            return $run_result;
        }
        return $run_result;
    }


    private static function zjSpeedCheck($phone, $mark){
        $flag_cache_id = $phone."sms_send_flag".$mark;
        if(Yii::app()->rcache->get($flag_cache_id)){
            return false;
        }
        Yii::app()->rcache->set($flag_cache_id,"1",3600);
        return true;
    }
    
    //频次限制
    private static function SpeedCheck($phone, $mark){
        $flag_cache_id = $phone."sms_send_flag".$mark;
        if(Yii::app()->rcache->get($flag_cache_id)){
            return false;
        }
        Yii::app()->rcache->set($flag_cache_id, "1", 60);
        return true;
    }

}

