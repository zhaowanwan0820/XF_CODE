<?php
/**
 * 修改邮箱
 * @author wenyanlei@ucfgroup.com
 */
namespace web\controllers\user;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;

class Saveemail extends BaseAction
{

    private $_error = null;

    public function init ()
    {
        if(!$this->check_login()) return false;
        //框架问题，只能在框架获取参数之前过滤空格，不然报错误
        $_POST['email'] = trim($_POST['email']);
        $_POST['new_email'] = trim($_POST['new_email']);
        $this->form = new Form('post');
        $this->form->rules = array(
            'password' => array(
                'filter' => 'length',
                'message' => '密码长度为5-25位',
                "option" => array(
                    "min" => 5,
                    "max" => 25
                )
            ),
            'new_email' => array(
                'filter' => 'email','message'=>'新邮箱格式错误'
            ),
            'captcha' => array('filter' => 'string'),
        );
        if (! $this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
        }
    }

    public function invoke ()
    {
        // 验证表单令牌
        if(!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0,0,url('user/editemail'));
        }
        if (! empty($this->_error)) {
            return $this->show_error($this->_error, '', 1);
        }

        $user_id = intval($GLOBALS['user_info']['id']);
        $user_info = $this->rpc->local('UserService\getUser', array($user_id));

        if(empty($user_info)){
            return $this->show_error('用户不存在');
        }

        $data = $this->form->data;

        $bo = BOFactory::instance('web');
        $pwd_compile = $bo->compilePassword($data['password']);
        if($pwd_compile !== $user_info['user_pwd']){
            return $this->show_error('密码输入错误');
        }

        if(!is_email($data['new_email'])){
            return $this->show_error('新邮箱格式错误！');
        }

        $is_exist = $this->rpc->local('UserService\checkEmailExist', array($data['new_email']));
        if($is_exist){
            return $this->show_error('该邮箱已被使用');
        }

        $save_data = array('id' => $user_id, 'email' => $data['new_email']);
        $save = $this->rpc->local('UserService\updateInfo', array($save_data));
        if($save){
            return $this->show_success('邮箱修改成功！', '', 0, 0, url("account"));
        }
        return $this->show_error('操作失败');
    }
}
