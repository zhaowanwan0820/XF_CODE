<?php

namespace api\controllers\marketing;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\marketing\MarketStrategyService;
use libs\utils\Logger;

class GetPopupInfo extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if (app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            $this->json_data = '';
            return;
        }

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $uid = $loginUser['id'];
        $res = (new MarketStrategyService)->popup($uid);
        $this->json_data = !empty($res['data']) ? $res['data'] : '';
    }
}

