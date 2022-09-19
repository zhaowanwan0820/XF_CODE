<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;

// 用户的邀请人列表
class AjaxFriendList extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'discount_id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $user_id = $loginUser['id'];
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        $rpcParams = array($user_id, $page, 10);
        $friendList = $this->rpc->local('O2OService\getUserFriendList', $rpcParams);

        if ($friendList === false) {
            $friendList = array('total' => 0, 'totalPage' => 0, 'list' => array());
        } else {
            $params = array();
            $params['from_user_id'] = $user_id;
            $params['discount_id'] = $data['discount_id'];
            foreach ($friendList['list'] as &$item) {
                $params['to_user_id'] = $item['user_id'];
                $item['sign'] = $this->rpc->local('DiscountService\getSignature', array($params));
            }
        }

        $this->json_data = $friendList;
    }
}
