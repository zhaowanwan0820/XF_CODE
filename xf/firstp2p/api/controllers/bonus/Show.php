<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class Show extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            //'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array("filter" => "int", "message"=>"id is error"),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        //$loginUser = $this->getUserByToken();
        //if (empty($loginUser)) {
        //    $this->setErr('ERR_GET_USER_FAIL');
        //    return false;
        //}
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $site_id = $data['site_id'] ? $data['site_id'] : 1 ;
        $id = $data['id'];
        $bonus = $this->rpc->local('BonusService\getBonusInfoById', array($id));
        if (intval($bonus['status']) !== 2 && $bonus['expired_at'] < time()) {
            $bonus['status'] = '3';
        }
        $this->tpl->assign('bonus', $bonus);
        $this->tpl->assign('site_id', $site_id);
        
        if ($this->app_version < 320) {
            if ($bonus['status'] == 2) {
                $this->template = $this->getTemplate('show_used');
            } else {
                $this->template = $this->getTemplate('show_useful');
            }
        }
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}
