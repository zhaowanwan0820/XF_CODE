<?php

namespace libs\sms;

use NCFGroup\Common\Library\Sms\Sms;
use libs\utils\Logger;

class SmsServer
{

    private static $instance = null;

    private static $appName = '';

    // 惠普app name
    const CNNAME = 'p2pcn';

    /**
     * 单例化
     */
    public static function instance($appName = '')
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        self::$appName = APP_NAME;
        if ($appName !== '') {
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
            $userInfo = $GLOBALS['db']->get_slave()->getRow("SELECT user_type,mobile_code,site_id FROM firstp2p_user WHERE id = '{$userid}'");

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
            if (isset($userInfo['user_type']) && $userInfo['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
            {
                $userService = new \core\service\UserService($userid);
                $mobiles = $userService->getEnterpriseContactInfo(true);
                foreach ($mobiles as $m) {
                    //台湾手机号去掉号码前面0
                    if ($m['code'] == '886' && substr($m['mobile'],0,1) == '0') {
                        $m['mobile'] = substr($m['mobile'], 1);
                    }

                    if (!empty($m['code']) && $m['code'] != '86') {
                        $m['mobile'] = '00' . $m['code'] . $m['mobile'];
                    }

                    self::qygjSmsModify($siteid, $tpl, $data); // 签约管家逻辑
                    $ret = Sms::send(self::$appName, $appSecret, $m['mobile'], $tpl, $data);
                    Logger::info("send sms to smsserver. enterprise, mobile:{$m['mobile']}, userid:{$userid}, tpl:{$tpl}, ret:" . json_encode($ret));
                }

                if ($ret['code'] != 0 ) {
                    $ret['message'] = '系统异常，稍后再试';
                }
                return $ret;
            }

            // 检查用户sms订阅设置
            $msg_config_service = new \core\service\MsgConfigService();
            $tplConfig = $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG'];
            $not_send_sms = $msg_config_service->checkIsSendSms($userid, $tplConfig[$tpl]);
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

        self::qygjSmsModify($siteid, $tpl, $data); // 签约管家逻辑
        $ret = Sms::send(self::$appName, $appSecret, $mobile, $tpl, $data);
        Logger::info("send sms to smsserver. mobile:{$mobile}, userid:{$userid}, tpl:{$tpl}, ret:" . json_encode($ret));

        if ($ret['code'] != 0 ) {
            $ret['message'] = '系统异常，稍后再试';
        }
        return $ret;

    }

    public static function qygjSmsModify($siteId, &$tpl, &$data) {
        if (app_conf('QYGJ_SITE_ID') != $siteId) { //非签约管家
            return true;
        }

        $data[0] = '';
        switch($tpl) {
            case 'TPL_SMS_VERIFY_CODE':
            case 'TPL_SMS_RESET_PASSWORD':
            case 'TPL_SMS_MODIFY_FORGETPASSWORD_CODE':
                $tpl = sprintf('%s_QYGJ', $tpl);
                self::$appName = 'wx_txz';
                break;
            case 'TPL_SMS_CTCT_RPT':
            case 'TPL_SMS_BORROW_CTCT_RPT':
                self::$appName = 'wx_qygj';
                break;
        }
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

        if ($siteId != $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn']){
            return false;
        }
       
        $msg_config_service = new \core\service\MsgConfigService();

        return $msg_config_service->checkP2pcnIsSendSms($siteId,$tpl,$option);
    }
}
?>
