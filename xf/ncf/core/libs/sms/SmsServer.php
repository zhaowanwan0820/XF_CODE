<?php

namespace libs\sms;

use NCFGroup\Common\Library\Sms\Sms;
use libs\utils\Logger;
use core\enum\UserEnum;
use core\service\MsgConfigService;
use core\service\user\UserService;
use core\service\user\UserLoginService;

class SmsServer
{

    private static $instance = null;

    private static $appName = '';

    // 惠普app name
    const CNNAME = 'p2pcn';

    /**
     * 经由网信普惠发出的短信，如果短信模版在此列表中，将会将短信头部的‘网信’修改
     * 为网信普惠
     *
     * @author sunxuefeng
     */
    private static $NCFPH_SMS_TPLS = array(
        'TPL_SMS_RESET_PASSWORD_CODE'
    );

    /**
     * 单例化
     */
    public static function instance($appName = '')
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        self::$appName = APP_NAME;
        if($appName !== '') {
            self::$appName = $appName;
        }

        return self::$instance;
    }

    private function __construct() { }

    public function send($mobile, $tpl, $data, $userid = null, $siteid = null)
    {
        $data = array_values($data);
        $appSecret = $GLOBALS['sys_config']['SMS_SEND_CONFIG']['APP_SECRET'];

        //处理分站签名
        if (empty($siteid)) {
            $siteid = \libs\utils\Site::getFenzhanId($userid);
        }
        $site = '';
        if ($siteid != 1) {
            $site = \libs\utils\Site::getTitleById($siteid);
        }
        if (!empty($site)) {
            $site = '[' . $site . ']';
        }
        if (empty($userid)){

            // 根据当前登录站点普惠单独通道
            $p2pcnIsSend = self::checkP2pCNSms($siteid,$tpl);
            if ($p2pcnIsSend){
                return false;
            }
            // 是否使用网信普惠签名
            $p2pcnSign = self::checkP2pCNSms($siteid,$tpl,1);
            if ($p2pcnSign){
                $site = '';
                self::$appName = self::CNNAME;
            }
        }
        array_unshift($data, $site);

        if (!empty($userid)) {
            if (!empty($GLOBALS['user_info'])) {
                $userInfo = $GLOBALS['user_info'];
            } else {
                $userInfo = UserService::getUserById($userid, 'id,user_type,mobile_code,site_id');
            }

            // 根据注册自普惠单独通道
            if (isset($userInfo['site_id'])){

                // 是否允许发送
                $p2pcnIsSend = self::checkP2pCNSms($userInfo['site_id'],$tpl);
                if ($p2pcnIsSend){
                    return false;
                }
                // 是否使用网信普惠签名
                $p2pcnSign = self::checkP2pCNSms($userInfo['site_id'],$tpl,1);
                if ($p2pcnSign){
                    // 防止显示重复
                    if (!empty($site)){
                        $data[0] = '';
                    }
                    self::$appName = self::CNNAME;

                }
            }
            // 企业用户支持
            if (isset($userInfo['user_type']) && $userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
            {
                // 获取企业联系人手机号列表
                $mobiles = UserLoginService::getEnterpriseMobileList($userid, true);
                foreach ($mobiles as $m) {
                    //台湾手机号去掉号码前面0
                    if ($m['code'] == '886' && substr($m['mobile'],0,1) == '0') {
                        $m['mobile'] = substr($m['mobile'], 1);
                    }

                    if (!empty($m['code']) && $m['code'] != '86') {
                        $m['mobile'] = '00' . $m['code'] . $m['mobile'];
                    }

                    $ret = Sms::send(self::$appName, $appSecret, $m['mobile'], $tpl, $data);
                    Logger::info("send sms to smsserver. enterprise, mobile:{$m['mobile']}, userid:{$userid}, ret:" . json_encode($ret));
                }
                return $ret;
            }

            // 检查用户sms订阅设置
            $tplConfig = $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG'];
            $not_send_sms = MsgConfigService::checkIsSendSms($userid, $tplConfig[$tpl]);
            if ($not_send_sms) {
                return false;
            }

            //台湾手机号去掉号码前面0
            if ($userInfo['mobile_code'] == '886' && substr($mobile,0,1) == '0') {
                $mobile = substr($mobile, 1);
            }

            //国际短信
            if (!empty($userInfo['mobile_code']) && $userInfo['mobile_code'] != '86') {
                $mobile = '00' . $userInfo['mobile_code'] . $mobile;
            }
        }

        // 在列表中的模版 将短信的开头名称由网信为网信普惠
        if (self::$appName != self::CNNAME && in_array($tpl, self::$NCFPH_SMS_TPLS)) {
            self::$appName = self::CNNAME;
        }

        $ret = Sms::send(self::$appName, $appSecret, $mobile, $tpl, $data);
        Logger::info("send sms to smsserver. mobile:{$mobile}, userid:{$userid}, ret:" . json_encode($ret));

        return $ret;

    }

    public static function sendAlertSms($mobiles, $content)
    {
        if (is_string($mobiles)) {
            $mobiles = [$mobiles];
        }

        foreach ($mobiles as $m) {
            $ret = self::send($m, 'monitor', [$content]);
        }

        return $ret;
    }

    /**
     * 检查网信普惠签名 和是否停发
     * @param $siteId
     * @param $tpl
     * @param int $option 0是否停发，1是签名
     * @return bool
     */
    public static function checkP2pCNSms($siteId,$tpl,$option = 0){
        if (isset($GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn'])
            && $siteId != $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn']) {
            return false;
        }

        return MsgConfigService::checkP2pcnIsSendSms($siteId,$tpl,$option);
    }
}
