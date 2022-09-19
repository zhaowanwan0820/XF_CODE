<?php
/**
 * 验证用户密码或邮箱 是否正确，邮箱是否已存在(ajax)
 * @author wenyanlei@ucfgroup.com
 */
namespace web\controllers\user;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\UserLoginService;

class Validator extends BaseAction
{
    public function init ()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
                'type' => array('filter' => 'string'),
                'value' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke ()
    {
        $data = $this->form->data;
        $user_id = intval($GLOBALS['user_info']['id']);
        $user_info = $GLOBALS['user_info'];

        if(empty($user_info)) {
            return $this->show_error('用户不存在', '', 1);
        }

        if($data['type'] == 'email' && $user_info['email'] !== $data['value']){
            return $this->show_error('邮箱输入错误', '', 1);
        }elseif($data['type'] == 'password'){
            $pwd_compile = UserLoginService::compilePassword($data['value']);
            if($pwd_compile !== $user_info['user_pwd']){
                return $this->show_error('密码输入错误', '', 1);
            }
        }
        return $this->show_success('', '', 1);
    }
}
