<?php

namespace api\controllers\apis;

use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use core\service\O2OService;
use core\dao\BonusConfModel;

class AcquireDiscount extends ApisBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'token' => ['filter' => 'string', 'message' => '参数错误', 'option' => ['optional' => false]],
            'discoutGroupId' => ['filter' => 'int', 'message' => '参数错误', 'option' => ['optional' => false]],
            'userId' => ['filter' => 'int', 'message' => '参数错误', 'option' => ['optional' => false]],
            'orderId' => ['filter' => 'string', 'message' => '参数错误', 'option' => ['optional' => false]],
            'userkey' => ['filter' => 'string', 'option' => ['optional' => true]],
            'code' => ['filter' => 'string', 'option' => ['optional' => true]],
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

        $data = $this->form->data;

        if ($data['userId'] != $userInfo['id']) {
            $this->echoJson(1001, '参数错误');
        }

        $result = (new O2OService())->acquireDiscount($userInfo['id'], $data['discoutGroupId'], $data['orderId']);

        if ($result) {
            $this->echoJson(0, 'OK', $result);
        } else {
            $this->echoJson(O2OService::$errorCode, O2OService::$errorMsg);
        }

    }

}
