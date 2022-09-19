<?php

/**
 * SendVerifyCode.php
 *
 * @date 2014-05-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use core\service\MobileCodeService;
use libs\utils\PaymentApi;

/**
 * 发送注册手机验证码
 *
 * Class SendVerifyCode
 * @package api\controllers\user
 */
class SendVerifyCode extends Signup {

    protected $must_verify_sign = false;
    //允许不传手机号的消息类型,直接根据token用户所对应手机号码发送
    private static $_ALLOW_NO_PHONE = array(MobileCodeService::RESET_BANK, MobileCodeService::MODIFY_PASSWORD_CODE,MobileCodeService::GOLD_DELIVER_CODE,MobileCodeService::CANDY_WITHDRAW);

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
            return false;
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
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            $params['phone'] = $userInfo->mobile;
        }
        $idno = !empty($params['idno']) ? $params['idno'] : null;

        $country_code = isset($params['country_code'])?$params['country_code']:'cn';
        $result = $this->rpc->local('UserService\NewSendVerifyCode', array($params['phone'], $type, $idno,false,$country_code));

        //Sms::$app_name = $source;
        if (!empty($result) && !isset($result['code'])) {
            $this->json_data = $result['result'];
        } else {
            $this->setErr('ERR_SIGNUP_SEND_CODE', $result['reason']);
        }
    }

}
