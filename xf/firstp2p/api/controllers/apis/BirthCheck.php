<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;

class Info extends ApisBaseAction
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
        if (!BonusConfModel::get('BONUS_APIS_BIRTHCHECK')) {
            $this->echoJson(10005, '非法访问');
        }

        $userInfo = $this->getUserByToken();
        if ($userinfo['id']) {
            $this->echoJson(1001, '获取用户信息失败');
        }

        $data = ['wishes' => 0];
        $length = strlen($userInfo['idno']);
        if ($length > 0) {
            $birth = $length == 15 ? ('19' . substr($userInfo['idno'], 6, 6)) : substr($userInfo['idno'], 6, 8);
        }

        $this->echoJson(0, 'OK', $data);
    }

}
