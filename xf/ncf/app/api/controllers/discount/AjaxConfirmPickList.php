<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\o2o\DiscountService;

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
        $loginUser = $this->user;

        $userid = $loginUser['id'];
        $page = intval($data['page']);
        $money = $data['money'];
        $result = array('totalPage' => 0,'list' => array());
        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 1;
        // 投资券开关
        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        if (!$o2oDiscountSwitch) {
            $this->json_data = $result;
            return false;
        }

        if (!empty($money)) {
            $discountGroupList = DiscountService::getAvailableDiscountList($userid, $data['deal_id'], $money, $page, 10, $type, $consumeType);
            if ($discountGroupList !== false) {
                $params = array('user_id'=> $userid, 'deal_id'=> $data['deal_id']);
                foreach ($discountGroupList['list'] as &$item) {
                    $params['discount_id'] = $item['id'];
                    $params['discount_group_id'] = $item['discountGroupId'];
                    $item['sign'] = DiscountService::getSignature($params);
                }
                $result['list'] = $discountGroupList['list'];
                $result['totalPage'] = $discountGroupList['totalPage'];
            }
        }
        $this->json_data = $result;
    }
}
