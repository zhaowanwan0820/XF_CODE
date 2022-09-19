<?php

/**
 * @author yutao
 * @abstract  H5 注册第一步
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;

class DoH5RegStepOne extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
        //禁止 get 提交
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return false;
        }

        $this->form = new Form('post');
        $this->form->rules = array(
            'mobile' => array('filter' => 'reg', "message" => "手机号码格式错误", "option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            'captcha' => array('filter' => 'string', 'message' => '请输入验证码'),
            'isAjax' => array('filter' => 'int', 'message' => '参数错误'),
        );

        if (!$this->form->validate()) {
            $this->_errorCode = -1;
            $this->_errorMsg = $this->form->getErrorMsg();
            echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
            return false;
        }
    }

    public function invoke() {
        do {
            /**
             * 验证图形验证码
             */
            $verify = \es_session::get('verify');
            $captcha = $this->form->data['captcha'];
            if (empty($captcha)) {
                $this->_errorCode = -9;
                $this->_errorMsg = '图形验证码不能为空';
                break;
            }
            if (md5($captcha) !== $verify) {
                $this->_errorCode = -10;
                $this->_errorMsg = '图形验证码不正确';
                break;
            }

            /**
             * 将手机号码加入session
             */
            \es_session::set('H5VerifyPhone', $this->form->data['mobile']);
        } while (false);

        setLog(array('errno' => $this->_errorCode, 'errmsg' => $this->_errorMsg, 'mobile' => $this->form->data['mobile']));
        echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
        return;
    }

}
