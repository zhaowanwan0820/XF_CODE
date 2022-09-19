<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use core\service\BonusService;
use core\dao\BonusConfModel;

class BonusUsable extends ApisBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'userId' => ['filter' => 'int', 'message' => '参数错误'],
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        if (!BonusConfModel::get('BONUS_APIS_SWITCH')) {
            return $this->echoJson(10005, '非法访问');
        }

        $data = $this->form->data;
        $userId = intval($data['userId']);
        if ($userId <= 0) return $this->echoJson(10001, '参数错误');
        $res = (new BonusService)->getUsableBonus($userId);
        if ($res) return $this->echoJson(0, 'OK', $res);
        else return $this->echoJson(20001, '获取失败');
    }

}
