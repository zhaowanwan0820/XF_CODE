<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class sms_sender
{
    //短信通道, 用于缓存短信通道对象
    private static $channels = array();

    /**
     * 根据短信类型获取对应通道
     *
     * @return object 短信通道类对象
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    private function _createChannelObject($type)
    {
        if(array_key_exists($type, $channels)) {
            return $channels[$type];
        } else{
            $sms_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."sms where is_effect = 1 and `type` = $type");
            if($sms_info) {
                $sms_info['config'] = unserialize($sms_info['config']);

                require_once APP_ROOT_PATH."system/sms/".$sms_info['class_name']."_sms.php";

                $sms_class = $sms_info['class_name']."_sms";
                $channels[$type] = new $sms_class($sms_info);
                return $channels[$type];
            }
        }
        return false;
    }

    /**
     * 初始化短信通道，自动识别国际国内手机号并使用不同的通道
     *
     * @return object 短信通道类对象
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    private function _initChannel($mobile) {
        if($this->_isInternational($mobile)){
            return $this->_createChannelObject($GLOBALS['dict']['SMS_CHANNEL_TYPE']['INTERNATIONAL']);
        } else if($this->_verifyMobile($mobile)) {
            return $this->_createChannelObject($GLOBALS['dict']['SMS_CHANNEL_TYPE']['DOMESTIC']);
        }
        return false;
    }

    /**
     * 识别是否国际手机号
     * 00开头的数字即国际手机号
     *
     * @return bool 是否国际手机号
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    private function _isInternational($mobile)
    {
        return preg_match("/^00\d+/", $mobile) > 0;
    }

    /**
     * 验证手机号格式是否正确
     *
     * @return bool 是否有效手机号
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    private function _verifyMobile($mobile) {
        return preg_match("/^1\d{10}$/", $mobile) > 0;
    }


    /**
     * 发送短信
     *
     * @return array 示例: array('status'=>0, 'msg'=>'消息内容') status: 0 失败 1成功
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function sendSms($mobile,$content)
    {
        $result = array(
            'status' => 0,
            'msg'    => ""
        );
        $sms = $this->_initChannel($mobile);
        if($sms) {
            $result = $sms->sendSMS($mobile, $content);
        } else {
            $result['status'] = 0;
            $result['msg'] = "无法识别的手机号";
        }

        return $result;
    }
}
?>
