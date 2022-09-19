<?php
/**
 * oauth调用的修改邮箱接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

class ChangeEmail extends BaseAction {
    const RES_SUCC = 0;
    const RES_EMPTY_SIGN = -1;
    const RES_ERR_SIGN = -2;
    const RES_ERR_PARAMS = -3;
    const RES_ERR_USER = -4;
    const RES_FAIL_UPDATE = -5;

    private $_des_key = 'nUw0CwIfj6Q=';

    public function init() {
        \FP::import("libs.utils.logger");
        $this->form = new Form();
        $this->form->rules = array(
            "sign" => array("filter"=>"required"),
        );
        if (!$this->form->validate()) {
            return $this->_display(self::RES_EMPTY_SIGN);
        }
    }

    public function invoke() {
        $sign = trim($this->form->data['sign']);
        if (!$sign) {
            $this->_wlog($sign, self::RES_EMPTY_SIGN);
            return $this->_display(self::RES_EMPTY_SIGN);
        }

        $sign = urldecode($sign);
        \FP::import('libs.id5.des');
        $des = new DES(base64_decode($this->_des_key));
        $params = $des->newDecrypt($sign);

        if (!$params) {
            $this->_wlog($sign, self::RES_ERR_SIGN);
            return $this->_display(self::RES_ERR_SIGN);
        }

        list($passport_id, $user_name, $email) = explode('&&', $params);
        if (!$passport_id || !$user_name || !$email) {
            $this->_wlog($sign, self::RES_ERR_PARAMS, $params, $passport_id, $user_name, $email);
            return $this->_display(self::RES_ERR_PARAMS);
        }

        $result = $this->rpc->local("UserService\changeEmail", array($passport_id, $user_name, $email));
        $user = $result['user'];

        if ($result['res'] === false) {
            if ($user) {
                $this->_wlog($sign, self::RES_FAIL_UPDATE, $params, $passport_id, $user_name, $email, $user['id']);
                return $this->_display(self::RES_FAIL_UPDATE);
            } else {
                $this->_wlog($sign, self::RES_ERR_USER, $params, $passport_id, $user_name, $email);
                return $this->_display(self::RES_ERR_USER);
            }
        }

        $this->_wlog($sign, self::RES_SUCC, $params, $passport_id, $user_name, $email, $user['id']);
        return $this->_display(self::RES_SUCC);
    }

    private function _display($errcode) {
        echo $errcode;
        return false;
    }
}
