<?php

/**
 * SendVerifyCode.php
 *
 * @date 2014-05-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use core\service\sms\MobileCodeService;
use libs\utils\PaymentApi;

/**
 * 发送注册手机验证码
 *
 * Class SendVerifyCode
 * @package api\controllers\user
 */
class SendVerifyCode extends Signup {
    // 允许不传手机号的消息类型,直接根据token用户所对应手机号码发送
    private static $_ALLOW_NO_PHONE = array(
        MobileCodeService::RESET_BANK,              // 解绑银行卡
        MobileCodeService::MODIFY_PASSWORD_CODE,    // 修改密码发送验证码
    );

    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();
        $this->form = new Form('POST');
        $this->form->rules = array(
            'phone' => array("filter" => 'string'),
            'type' => array("filter" => 'string'),
            'token' => array("filter" => 'string'),
            'idno' => array("filter" => 'string'),
            'source' => array("filter" => 'string'),
            'country_code' => array("filter" => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }

        if (!in_array($this->form->data['type'], self::$_ALLOW_NO_PHONE)) {
            if (!$this->check_phone()) {
                return false;
            }
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $type = $params['type'] ? $params['type'] : 1;
        $source = $params['source'] ? $params['source'] : '';
        if (empty($params['phone']) || in_array($type, self::$_ALLOW_NO_PHONE)) {
            $userInfo = $this->user;
            $params['phone'] = $userInfo['mobile'];
        }

        $idno = !empty($params['idno']) ? $params['idno'] : null;
        $country_code = isset($params['country_code']) ? $params['country_code'] : 'cn';

        $MobileCodeServiceObj = new MobileCodeService();
        $is_send = $MobileCodeServiceObj->isSend($params['phone'], $type, 0, true, false);
        if ($is_send != 1) {
            $error_msg = $MobileCodeServiceObj->getError($is_send);
            $this->setErr('ERR_SIGNUP_SEND_CODE', $error_msg['message']);
        }

        //Sms::$app_name = $source;
        $ret = $MobileCodeServiceObj->sendVerifyCode(
            $params['phone'],
            0,
            false,
            $type,
            $country_code,
            $idno
        );

        if (empty($ret)) {
            $this->setErr('ERR_SIGNUP_SEND_CODE', '系统繁忙，请稍后重试');
        }

        if (!isset($ret['code']) || $ret['code'] != 1) {
            $errMsg = isset($ret['message']) ? $ret['message'] : '系统繁忙，请稍后重试';
            $this->setErr('ERR_SIGNUP_SEND_CODE', $errMsg);
        }

        $this->json_data = true;
    }
}
