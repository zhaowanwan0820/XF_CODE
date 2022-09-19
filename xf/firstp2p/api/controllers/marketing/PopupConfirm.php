<?php

namespace api\controllers\marketing;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\marketing\MarketStrategyService;
use libs\utils\Logger;

class PopupConfirm extends AppBaseAction 
{

    public function init() 
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "sid" => array("filter" => "required", "message" => "sid is required"),
            "nid" => array("filter" => "required", "message" => "nid is required"),
            "confirm" => array("filter" => "required", "message" => "confirm is required"),
            "did" => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() 
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $uid = $loginUser['id'];
        $strategyId = intval($data['sid']);
        $notifyId = intval($data['nid']);
        $confirmType = intval($data['confirm']);
        $discountId = intval($data['did']);

        $res = (new MarketStrategyService)->popupConfirm($uid, $notifyId, $strategyId, $confirmType, $discountId);
        if ($res['errCode'] > 0) {
            $this->setErr('ERR_SYSTEM', $res['msg']);
        } else {
            $this->json_data = $res['data'];
        }
    }
}

