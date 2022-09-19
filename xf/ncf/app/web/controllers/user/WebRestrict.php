<?php
/**
 * web防套利，判断是否需要身份验证
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\user\UserService;

class WebRestrict extends BaseAction
{
    private $_error = null;
    private $_flag = false;
    public function init()
    {
        $this->form = new Form("post");
        $this->form->rules = array(
            'username'=> array('filter'=>'string'),
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
        $user_info = UserService::getUserByNameMobile($username);
        if ($user_info['mobile'] && set_restrict_cookie($username)) {
            echo json_encode(array(
                'errorCode' => 0,
                'errorMsg' => '需要短信验证',
                'mobile' => $user_info['mobile'],
                //'uid' => $user_info['id'],
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