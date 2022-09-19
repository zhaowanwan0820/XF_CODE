<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\User;
use core\dao\BonusConfModel;

class BirthdayCheck extends ApisBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'token' => ['filter' => 'string', 'message' => '参数错误', 'option' => ['optional' => true]],
        ]);

        if (!$this->form->validate()) {
            $this->echoJson(10001, $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if (!BonusConfModel::get('SWITCH_APIS_BIRTHDAYCHECK')) {
            $this->echoJson(10005, '非法访问');
        }

        $userInfo = $this->getUserByToken();
        if (!$userInfo['id']) {
            $this->echoJson(1001, '获取用户信息失败');
        }
        // $this->echoJson(0, 'OK');

        $data = [];
        if (User::birthdayWishesCheck($userInfo)) {
            $this->echoJson(0, 'OK');
        } else {
            $this->echoJson(20001, 'check failed');
        }

    }

}
