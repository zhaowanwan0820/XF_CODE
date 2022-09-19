<?php

/**
 * 异步校验图形验证码
 * @author wangfei5<wangfei5@ucfgroup.com>
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;

class CaptchaCheck extends BaseAction {

    public function init() {
        $this->form = new Form('get');
        $this->form->rules = array(
            'captcha' => array('filter' => 'string', 'message' => '参数错误'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            return $this->printResult();
        }
    }

    public function invoke() {
        $vname = isset($_GET['vname']) ? addslashes($_GET['vname']) : 'verify';

        $verify = \es_session::get($vname);
        $captcha = $this->form->data['captcha'];
        if (empty($captcha)) {
            $this->_error = '验证码不能为空';
        }elseif (md5($captcha) !== $verify) {
            $this->_error = '验证码不正确';
        }
        return $this->printResult();
    }

    private function printResult($code=null) {
        if(empty($this->_error))
        {
            $code = (empty($code))?0:$code;
            $json = array('code'=>$code,'msg'=>'');
        }else{
            $code = (empty($code))?-1:$code;
            $json = array('code'=>$code,'msg'=>$this->_error);
        }
        echo json_encode($json);
        return false;
    }

}
