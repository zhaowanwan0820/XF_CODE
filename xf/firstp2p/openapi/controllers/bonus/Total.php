<?php

/**
 * @abstract openapi 获取红包汇总信息
 * @date 2015-07-14
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 *
 */

namespace openapi\controllers\bonus;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestBonusGetList;

class Total extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "user_id" => array('filter' => "int"),
            "site_id" => array("filter" => "int"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->form->validate();
    }

    public function invoke() {

        $data = $this->form->data;
        if ($data['user_id']) {
            $user_id = $data['user_id'];
        } else {
            $user_id = 0;
            $userInfo = $this->getUserByAccessToken();
            if ($userInfo) {
                $user_id = $userInfo->userId;
            }
        }
        $user_id = intval($user_id);
        if (!$user_id) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $request = new RequestBonusGetList();
        try {
            $request->setUserId($user_id);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $bonusResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpBonus',
            'method' => 'total',
            'args' => $request
        ));
        if ($bonusResponse->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get bonus list failed";
            return false;
        }

        $this->json_data = $bonusResponse;
        return true;
    }

}
