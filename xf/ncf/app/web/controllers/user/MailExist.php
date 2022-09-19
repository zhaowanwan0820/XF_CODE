<?php
/**
 *  * 验证邮箱是否存在
 *  * @author wangfei5<wangfei5@ucfgroup.com>
 *  *
 **/

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;

class MailExist extends BaseAction {

    private $_error;

    public function init() {
        $this->form = new Form('get');
        $this->form->rules = array(
            'email' => array('filter' => 'email'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            return $this->printResult();
        }
    }

    public function invoke() {

        $ret = $this->rpc->local('UserService\checkEmailExist',array($this->form->data['email']));
        if($ret === true)
        {
            $this->_error = '邮箱已存在';
        }
        return $this->printResult();
    }

    private function printResult($code=null) {
        if(empty($this->_error))
        {
            $code = (empty($code))?0:$code;
            $json = array('code'=>$code,'msg'=>'');
        }
        else
        {
            $code = (empty($code))?-1:$code;
            $json = array('code'=>$code,'msg'=>$this->_error);
        }
        echo json_encode($json);
        return false;
    }
}
