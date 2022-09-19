<?php
/**
 *  * 验证用户是否存在
 *  * @author yangqing<yangqing@ucfgroup.com>
 *  *
 * */
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;

class UserExist extends BaseAction {

    private $_error;

    public function init() {
        $this->form = new Form('get');
        $this->form->rules = array(
            'username' => array('filter' => 'reg', 'message' => '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母', "option" => array("regexp" => "/^([A-Za-z])[\w-]{3,15}$/")),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            return $this->printResult();
        }
    }

    public function invoke() {
        $ret = $this->rpc->local('UserService\checkUserExistIsNormal', array(get_client_ip(), $_SERVER['HTTP_USER_AGENT']));
        if ($ret === true) {
            $username = trim($this->form->data['username']);
            $ret = $this->rpc->local('UserService\isUserExistsByUsername', array($username));
            if ($ret === TRUE) {
                $this->_error = '用户名已被使用';
            }
        } else {
            $this->_error = '系统繁忙，请稍后再试';
            return $this->printResult('-2');
        }
        return $this->printResult();
    }

    private function printResult($code = null) {
        if (empty($this->_error)) {
            $code = (empty($code)) ? 0 : $code;
            $json = array('code' => $code, 'msg' => '');
        } else {
            $code = (empty($code)) ? -1 : $code;
            $json = array('code' => $code, 'msg' => $this->_error);
        }
        echo json_encode($json);
        return false;
    }

}
