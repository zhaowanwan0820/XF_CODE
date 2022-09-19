<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\bonus\BonusUser;

class AjaxGet extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
            'pageSize' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $token = $data['token'];
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        // 1已使用，2可用，3过期
        $type = $data['type'];
        $page = isset($data['page']) ? $data['page'] : 1;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;
        
        if ($this->app_version >= 320) {
            $all = $this->rpc->local('BonusService\get_list', array($loginUser['id'], 0, true, $page, $pageSize, true, true));
            $getResult = (new BonusUser())->getUserByUid($loginUser['id']);
            $new_all = array();
            foreach ($all['list'] as $bonus_value) {
                if (intval($bonus_value['status']) <> 2) {
                    $bonus_value['status'] = ($bonus_value['expired_at'] < time()) ? '3' : '1';
                }
                $new_all[] = $bonus_value;
            }
            $all['list'] = $new_all;
            $list = $all;
        } else {
            $list = $this->rpc->local('BonusService\get_list', array($loginUser['id'], $type, true, $page, $pageSize, true, true));
        }

        $this->json_data = $list;
    }

}
