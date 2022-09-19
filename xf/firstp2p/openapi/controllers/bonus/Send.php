<?php

/**
 * @abstract openapi 发出的红包列表
 * @date 2015-07-01
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 *
 */

namespace openapi\controllers\bonus;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestBonusSendList;

class Send extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "user_id" => array('filter' => "int"),
            "offset" => array("filter" => "int"),
            "count" => array("filter" => "int"),
            "site_id" => array("filter" => "int"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->form->validate();
    }

    public function invoke() {

        $data = $this->form->data;
        $site_id = $data['site_id'] ? $data['site_id'] : 1;
        $page = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1;
        $count = $data['count'] > 0 ? intval($data['count']) : 10;
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

        $request = new RequestBonusSendList();
        try {
            $request->setUserId($user_id);
            $request->setPage($page);
            $request->setCount($count);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $bonusResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpBonus',
            'method' => 'sendList',
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
