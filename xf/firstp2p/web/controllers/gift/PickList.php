<?php

namespace web\controllers\gift;

use libs\web\Form;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use web\controllers\BaseAction;

class PickList extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'action' => array('filter' => 'required', 'message' => 'action is required'),
            'load_id' => array("filter" => "required", "message"=>"deal load id is error"),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];

        $dealLoadId = $data['load_id'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $event = $data['action'];
        $userid = $loginUser['id'];
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $page = $page < 1 ? 1 : $page;
        $rpcParams = array($userid, $event, $dealLoadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        if ($couponGroupList === false) {
            $couponGroupList = array();
        }

        $this->tpl->assign('couponGroupList', $couponGroupList);
        $this->tpl->assign('countList', count($couponGroupList));
        $this->tpl->assign('action', $data['action']);
        $this->tpl->assign('load_id', $data['load_id']);
        $this->tpl->assign('deal_type', $dealType);
        $this->template = 'web/views/gift/list.html';
    }
}
