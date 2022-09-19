<?php
/**
 * web防套利，判断是否需要身份验证
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */
namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\UserLoginService;
use core\service\user\UserService;

class LoginRestrict extends BaseAction
{
    private $_error = null;
    private $_flag = false;
    public function init()
    {
        $this->form = new Form("post");
        $this->form->rules = array(
            'username'=> array('filter'=>'string'),
            'country_code' => array('filter' => 'string'),
            'password' => array('filter' => 'string'),
        );
        if (! $this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            echo json_encode(array(
                'errorCode' => 2,
                'errorMsg' => $this->_error,
            ));
            return ;
        }
    }

    public function invoke()
    {
        $username = $this->form->data['username'];
        // 获取用户信息
        $user_info = UserService::getUserByNameMobile($username);
        $pwd = UserLoginService::compilePassword($this->form->data['password']);
        \FP::import("libs.common.dict");
        $whitelist = \dict::get("MOBILE_WHITELIST_PRO_RESTRICT");//获取防套利手机白名单
        $ret = '1';
        while ($whitelist && $string = array_shift($whitelist)) {
            $ret = strpos($user_info['mobile'],$string);
            if ($ret === 0) {
                break;
            }
        }
        if (empty($user_info['mobile']) || ($user_info['country_code'] != $this->form->data['country_code']) || $ret === 0 || $user_info['user_pwd'] != $pwd) {
            echo json_encode(array(
                'errorCode' => 1,
                'errorMsg' => '不需要短信验证',
            ));
            return;
        }
        if (set_restrict_cookie($username)) {
            setLog(array('restrict_flag'=>1));
            echo json_encode(array(
                'errorCode' => 0,
                'errorMsg' => '需要短信验证',
                'mobile' => $user_info['mobile'],
                'mobile_code' => $user_info['mobile_code'],
            ));
            return;
        } else {
            echo json_encode(array(
                'errorCode' => 1,
                'errorMsg' => '不需要短信验证',
            ));
            return;
        }
    }
}
