<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class AjaxPickList extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'deal_id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true))
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

        $userid = $loginUser['id'];
        $options = array();
        $page = intval($data['page']);
        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;

        $rpcParams = array($userid, $data['deal_id'], false, $page, 10, $type, $consumeType);
        $discountGroupList = $this->rpc->local('O2OService\getAvailableDiscountList', $rpcParams);
        if ($discountGroupList === false) {
            $discountGroupList = array('total' => 0, 'totalPage' => 0, 'list' => array());
        }

        $params = array('user_id'=> $userid, 'deal_id'=> $data['deal_id']);
        $signStr = $this->rpc->local('DiscountService\getSignature', array($params));
        foreach ($discountGroupList['list'] as &$item) {
            $params['discount_id'] = $item['id'];
            $params['discount_group_id'] = $item['discountGroupId'];
            $item['sign'] = $this->rpc->local('DiscountService\getSignature', array($params));
        }
        $this->json_data = $discountGroupList;
    }
}
