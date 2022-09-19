<?php

namespace api\controllers\stats;

use libs\web\Form;
use libs\utils\Logger;
use api\controllers\AppBaseAction;

class Detail extends AppBaseAction {

    const USER_REGIST_TODAY = 'userRegistToday';
    const USER_TRADE_TODAY  = 'userTradeTody';

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
           "types" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        if (empty($params['types'])) {
            $this->json_data = [];
            return true;
        }

        $return = array();
        $types  = explode(',', $params['types']);

        if (in_array(self::USER_REGIST_TODAY, $types)) {
            $registerCount = $this->rpc->local('UserService\getCountByDay', array(date("Y-m-d")));  //今日注册人数
            $return[self::USER_REGIST_TODAY] = $registerCount;
        }

        if (in_array(self::USER_TRADE_TODAY, $params['types'])) {
            $loadUserCount = $this->rpc->local('DealLoadService\getLoadUsersNumByTime', array()); //今日投资人数
            $return[self::USER_TRADE_TODAY] = $loadUserCount;
        }

        $this->json_data = $return;
        return true;
    }

}
