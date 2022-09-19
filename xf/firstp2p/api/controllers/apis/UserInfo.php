<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use core\dao\BonusConfModel;

class UserInfo extends ApisBaseAction
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
        if (!BonusConfModel::get('BONUS_APIS_USERINFO')) {
            $this->echoJson(10005, '非法访问');
        }

        $userInfo = $this->getUserByToken();
        if (!$userInfo['id']) {
            $this->echoJson(1001, '获取用户信息失败');
        }

        $vipInfo = (new \core\service\vip\VipService())->getVipInfo(intval($userInfo['id']));

        $data = [
            "userId" => $userInfo['id'],
            "name" => $userInfo['real_name'],
            "level" => intval($vipInfo['service_grade']),
            "sex" => $userInfo['sex'],
            "mobile" => substr_replace($userInfo['mobile'],'****', 3, 4),
        ];

        $this->echoJson(0, 'OK', $data);
    }

}
