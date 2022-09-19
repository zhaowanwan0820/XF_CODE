<?php

/**
 * 
 * 获取手机验证码
 * @author 晓安<zhaoxiaoan@ucfgroup.com>
 * 
 */

namespace openapi\controllers\user;

use openapi\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;
use core\dao\MobileVcodeModel;
use libs\utils\Logger;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;

class MCode extends BaseAction {

    public function init() {
    }

    public function invoke() {

        $form = new Form('');

        $form->rules = array(
            'mobile' => array('filter' => 'reg', "message" => "手机号码格式错误", "option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            'type' => array('filter' => 'int', 'message' => '参数错误'),
            'isrsms' => array('filter' => 'int', 'message' => '参数错误'),
            'captcha' => array('filter' => 'string', 'message' => '参数错误'),
            'active' => array("filter" => "string"),
            'isPc' => array("filter" => "string"),
            'smLoginToken' => array('filter' => 'string'),
            'idno' => array('filter' => 'string'),
        );

        $errno = 0;
        $errmsg = '';

        if (!$form->validate()) {
            $errno = -1;
            $errmsg = '手机号格式错误';
            echo json_encode(array('code' => $errno, 'message' => $errmsg));
            setLog(array('errno' => $errno, 'errmsg' => $errmsg));
            return;
        }

        $type = $form->data['type'];
        // 状态码请参考service
        $rspnMsg = false;
        //referer 封禁
        $forbidRefererRet = forbidReferer(true, array_pop(explode("\\", __CLASS__)));

        $debugMsg = __CLASS__ . "|" . $_SERVER['HTTP_USER_AGENT'] . "|" . json_encode($forbidRefererRet) . "|" . json_encode($form->data);

        do {
            // 验证token
            if (!check_token() && $form->data['active'] == 0) {
                $errno = -3;
                $errmsg = '系统繁忙，请稍后重试';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }

            if ($forbidRefererRet['forbid']) {
                $errno = -2;
                $errmsg = 'illegal request';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }

            if ($type != 1 && $type != 2 && $type != 12 && $type != 16) {
                $errno = -1;
                $errmsg = '参数错误';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }

            if ($type == 1) {
                $verify = \es_session::get('verify');
                $captcha = $form->data['captcha'];
                if (empty($captcha)) {
                    $errno = -9;
                    $errmsg = '图形验证码不能为空';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
                if (md5($captcha) !== $verify) {
                    $errno = -10;
                    $errmsg = '图形验证码不正确';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
            }

            if($type == 16){
                //校验smLoginToken
                $smLoginToken = \es_session::get('smLoginToken');
                if( empty($smLoginToken) || $form->data['smLoginToken'] != $smLoginToken ){
                    //非法请求
                    $errno = -13;
                    $errmsg = '请求超时，请刷新页面';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    echo $rspnMsg;
                    return;
                }
                $verify = \es_session::get('verify');
                if(!empty($verify)){
                    $captcha = $form->data['captcha'];
                    if (empty($captcha)) {
                        $errno = -9;
                        $errmsg = '图形验证码不能为空';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                    if (md5($captcha) !== $verify) {
                        $errno = -10;
                        $errmsg = '图形验证码不正确';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                }

                Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WAP','SM_LOGIN_INTERFACE_START',json_encode($form->data))));
                //同步，进行安全监测
                $form->data['account'] = $form->data['mobile'];
                RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_OPEN_API,$this->device)->check($form->data,Risk::SYNC);
                if(!empty($_SESSION['risk_login_frequent'])){
                    Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WAP','SM_LOGIN_INTERFACE_RISK_FREQUENT',json_encode($form->data))));
                    $errno = -13;
                    $errmsg = '手机号码发送频率超过限制，请稍后再试';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    echo $rspnMsg;
                    return;
                }
                if(!empty($_SESSION['risk_login_illegal'])){
                    Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WAP','SM_LOGIN_INTERFACE_RISK_ILLEGAL',json_encode($form->data))));
                    $errno = -13;
                    $errmsg = '设备指纹非法';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    echo $rspnMsg;
                    return;
                }
                //短信登录验证
                $isCheckIPFrequency = true;
                $mobile = $form->data['mobile'];

                if ($isCheckIPFrequency) {
                    //手机号码，每秒请求1次
                    if (Block::check('CLIENT_REGISTER_USER_CODE_SECOND', $mobile,true) ===false){
                        $errno = -11;
                        $errmsg = '发送频率超过分钟限制，请稍后再试';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                    $ip = get_client_ip();
                    $check_ip_minute_result = Block::check('SEND_SMS_IP_MINUTE', $ip,false);
                    if ($check_ip_minute_result === false) {
                        $errno = -11;
                        $errmsg = '发送频率超过分钟限制，请稍后再试';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                    $check_ip_day_result = Block::check('SEND_SMS_IP_TODAY', $ip, false);
                    if ($check_ip_day_result === false) {
                        $errno = -12;
                        $errmsg = '发送频率超过当天限制，请稍后再试';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                    $check_phone_hour_result = Block::check('SEND_SMS_PHONE_HOUR', $mobile, false);
                    if ($check_phone_hour_result === false) {
                        $errno = -13;
                        $errmsg = '手机号码发送频率超过限制，请稍后再试';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                }
            }
            if($type != 16){
                //默认的检测
                $errno = $this->rpc->local('MobileCodeService\isSend', array($form->data['mobile'], $type));
                if ($errno != 1) {
                    $err = $this->rpc->local('MobileCodeService\getError', array($errno));
                    $errmsg = $err['message'];
                    $rspnMsg = json_encode($err);
                    break;
                }
            }
        } while (false);
        \libs\utils\Logger::debug($debugMsg . "|" . json_encode($rspnMsg));

        setLog(array('errno' => $errno, 'errmsg' => $errmsg));

        if ($rspnMsg !== false) {
            echo $rspnMsg;
            return;
        }

        $isrsms = empty($form->data['isrsms']) ? false : true;
        $isPc = !isset($form->data['isPc']) ? 1 : $form->data['isPc'];
        $s_type = $type;
        if (!empty($_POST['sms_type']) && $_POST['sms_type'] == 1) {
            $s_type = 4;
        }
        $idno = ($s_type== 2 && !empty($form->data['idno'])) ? $form->data['idno'] : null;
        if ($isPc == 0) {
            $res = $this->rpc->local('MobileCodeService\sendVerifyCode', array($form->data['mobile'], $isPc, $isrsms, $s_type,'cn',$idno));
            echo $res;
            return;
        } else {
            return $this->rpc->local('MobileCodeService\sendVerifyCode', array($form->data['mobile'], $isPc, $isrsms, $s_type));
        }
    }

    public function authCheck() {
        return true;
    }

    public function _after_invoke() {
        return;
    }

}
