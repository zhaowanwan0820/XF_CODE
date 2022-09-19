<?php

/**
 * @author <yutao@ucfgroup.com>
 * @abstract  新版h5注册时获取短信接口
 * @date 2015-03-30 
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;

class H5MCode extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
        //禁止 get 提交
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return false;
        }
        $this->form = new Form('POST');
        $this->form->rules = array(
            'mobile' => array('filter' => 'reg', "message" => "手机号码格式错误", "option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            'type' => array('filter' => 'int', 'message' => '参数错误'),
            'isrsms' => array('filter' => 'int', 'message' => '参数错误'),
        );
        if (!$this->form->validate()) {
            $this->_errorCode = -1;
            $this->_errorMsg = $this->form->getErrorMsg();
            echo json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
            return false;
        }
    }

    public function invoke() {
        $rspnMsg = false;
        //referer 封禁
        $forbidRefererRet = forbidReferer(true, array_pop(explode("\\", __CLASS__)));
        $debugMsg = __CLASS__ . "|" . $_SERVER['HTTP_USER_AGENT'] . "|" . json_encode($forbidRefererRet);
        \libs\utils\Logger::debug($debugMsg);

        $type = $this->form->data['type'];
        $isrsms = empty($this->form->data['isrsms']) ? false : true;

        do {

            //referer 封禁
            if ($forbidRefererRet['forbid']) {
                $this->_errorCode = -2;
                $this->_errorMsg = 'illegal request';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }
            // 验证token
            if (!$this->check_token()) {
                $this->_errorCode = -3;
                $this->_errorMsg = '系统繁忙，请稍后重试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }

            $sessionPhone = \es_session::get("H5VerifyPhone");
            if ($sessionPhone !== $this->form->data['mobile']) {
                $this->_errorCode = -4;
                $this->_errorMsg = 'phone not found in session';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }

            /**
             * @abstract增加调用短信接口的频率限制
             * @author yutao
             * @date 2015-02-27
             */
            $ip = get_client_ip();
            $mobile = $this->form->data['mobile'];
            $check_ip_minute_result = Block::check(SEND_SMS_IP_MINUTE, $ip, false);
            if ($check_ip_minute_result === false) {
                $this->_errorCode = -11;
                $this->_errorMsg = '发送频率超过分钟限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }
            $check_ip_day_result = Block::check(SEND_SMS_IP_TODAY, $ip, false);
            if ($check_ip_day_result === false) {
                $this->_errorCode = -12;
                $this->_errorMsg = '发送频率超过当天限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }
            $check_phone_hour_result = Block::check(SEND_SMS_PHONE_HOUR, $mobile, false);
            if ($check_phone_hour_result === false) {
                $this->_errorCode = -13;
                $this->_errorMsg = '当天手机号码发送频率超过限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $this->_errorCode, 'message' => $this->_errorMsg));
                break;
            }

            $this->_errorCode = $this->rpc->local('MobileCodeService\isSend', array($this->form->data['mobile'], $type));
            if ($this->_errorCode != 1) {
                $err = $this->rpc->local('MobileCodeService\getError', array($this->_errorCode));
                $this->_errorMsg = $err['message'];
                $rspnMsg = json_encode($err);
                break;
            }
        } while (false);

        setLog(array('errno' => $this->_errorCode, 'errmsg' => $this->_errorMsg));

        if ($rspnMsg !== false) {
            echo $rspnMsg;
            return;
        }

        $s_type = $type;
        if (!empty($_POST['sms_type']) && $_POST['sms_type'] == 1) {
            $s_type = 4;
        }
        return $this->rpc->local('MobileCodeService\sendVerifyCode', array($this->form->data['mobile'], 1, $isrsms, $s_type));
    }

    /**
     * 验证表单令牌 
     * 为了重新发送不调用app中的check_token方法
     * @author yutao
     * @param string $token_id
     * @return number 返回1为通过，0为失败
     */
    private function check_token($token_id = '') {
        $token_id = empty($token_id) ? $_REQUEST['token_id'] : $token_id;
        $token = $_REQUEST['token'];
        if (empty($token_id) || empty($token)) {
            return 0;
        }
        $k = 'ql_token_' . $token_id;
        if ($token == $_SESSION[$k]) {
//            $_SESSION[$k] = "";
//            unset($_SESSION[$k]);
            return 1;
        } else {
            return 0;
        }
    }

}
