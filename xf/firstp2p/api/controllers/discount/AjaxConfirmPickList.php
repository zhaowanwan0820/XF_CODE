<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class AjaxConfirmPickList extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'deal_id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            //前端测试
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true
                ),
            ),
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
        $money = $data['money'];
        $result = array('totalPage' => 0,'list' => array());
        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        // 投资券开关
        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        $o2oGoldDiscountSwitch = intval(get_config_db('O2O_GOLD_DISCOUNT_SWITCH', $siteId));
        if (!$o2oDiscountSwitch || ($type == CouponGroupEnum::DISCOUNT_TYPE_GOLD && !$o2oGoldDiscountSwitch)) {
            $this->json_data = $result;
            return false;
        }

        if (!empty($money)) {
            $rpcParams = array($userid, $data['deal_id'], $money, $page, 10, $type, $consumeType);
            $discountGroupList = $this->rpc->local('O2OService\getAvailableDiscountList', $rpcParams);
            if ($discountGroupList !== false) {
                $params = array('user_id'=> $userid, 'deal_id'=> $data['deal_id']);
                $signStr = $this->rpc->local('DiscountService\getSignature', array($params));
                foreach ($discountGroupList['list'] as &$item) {
                    $params['discount_id'] = $item['id'];
                    $params['discount_group_id'] = $item['discountGroupId'];
                    $item['sign'] = $this->rpc->local('DiscountService\getSignature', array($params));
                }
                $result['list'] = $discountGroupList['list'];
                $result['totalPage'] = $discountGroupList['totalPage'];
            }
        }
        $this->json_data = $result;
    }
}
