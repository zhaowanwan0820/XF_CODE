<?php

/**
 * @author <刘振鹏@ucfgroup.com>
 * @abstract  密保问题发送手机验证码
 * @date 2015-09-10 
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;

class ProtectionMobileCode extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
        //禁止get提交
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return false;
        }

        $this->form = new Form('post');
        $this->form->rules = array(
            'type' => array('filter' => 'int'),
        );

        if (!$this->form->validate()) {
            $protectionResult['errorCode'] = -1;
            $protectionResult['errorMsg'] = $this->form->getErrorMsg();
            echo json_encode($protectionResult);
            return false;
        }
    }

    public function invoke() {
        $user_id = intval($GLOBALS['user_info']['id']);
        if(!$user_id){
            $protectionResult['errorCode'] = -1;
            $protectionResult['errorMsg']  = "发生系统错误";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        $mobile = $GLOBALS['user_info']['mobile'];
        $type   = isset($this->form->data['type']) ? $this->form->data['type'] : 0;

        $rspnMsg = false;
        $isrsms = false;
        do {
            /**
             * @abstract增加调用短信接口的频率限制(原著)
             * @author yutao
             * @date 2015-02-27
             */
            $ip = get_client_ip();
            $check_ip_minute_result = Block::check(SEND_SMS_IP_MINUTE, $ip, false);
            if ($check_ip_minute_result === false) {
                $this->_errorCode = -2;
                $this->_errorMsg = '发送频率超过分钟限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }
            $check_ip_day_result = Block::check(SEND_SMS_IP_TODAY, $ip, false);
            if ($check_ip_day_result === false) {
                $this->_errorCode = -3;
                $this->_errorMsg = '发送频率超过当天限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }
            $check_phone_hour_result = Block::check(SEND_SMS_PHONE_HOUR, $mobile, false);
            if ($check_phone_hour_result === false) {
                $this->_errorCode = -4;
                $this->_errorMsg = '当天手机号码发送频率超过限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }
        } while (false);

        setLog(array('errno' => $this->_errorCode, 'errmsg' => $this->_errorMsg));
        if ($rspnMsg !== false) {
            echo $rspnMsg;
            return;
        }
        
        $sms_type = ($type == 0) ? 7 : 8;
        $res = $this->rpc->local('MobileCodeService\sendVerifyCode', array($mobile, 0, false, $sms_type, $GLOBALS['user_info']['country_code']));
        $res = json_decode($res,true); 
        $rspnMsg = json_encode(array('code' => $res['code'], 'message' => $res['message']));

        die($rspnMsg);
    }


}
