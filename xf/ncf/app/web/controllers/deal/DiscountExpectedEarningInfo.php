<?php

namespace web\controllers\deal;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\o2o\DiscountService;

/**
 * 选择相关投资券后的提示文案
 */
class DiscountExpectedEarningInfo extends BaseAction {
    public function init() {
        $this->check_login();

        $this->form = new Form();
        $this->form->rules = array(
            'dealId' => array('filter' => 'required', 'message' => 'dealId参数缺失'),
            'money' => array('filter' => 'required', 'message' => "金额格式错误"),
            'discountId' => array('filter' => 'required', 'message' => 'discountId参数缺失'),
            'consumeType' => array('filter' => 'int', 'message' => 'consumeType参数类型为int'),
        );
        if (!$this->form->validate()) {
            $ret = array('error' => 2000, 'msg' => $this->form->getErrorMsg());
            return ajax_return($ret);
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $money = $data['money'];
        if (!empty($money) && !preg_match('/^\d+(\.\d{1,2})?$/', $money)) {
            $money = 0;
        }

        $uid = $GLOBALS['user_info']['id'];
        $consumeType = $data['consumeType'] ? intval($data['consumeType']) : 1;
        $dealId = $data['dealId'];
        if ($consumeType == 1) {
            // 对于p2p的类型，dealId进行解密
            $dealId = Aes::decryptForDeal($dealId);
        } else if ($consumeType == 2) {
            $dealId = intval($dealId);
        }

        $ret = DiscountService::getExpectedEarningInfo($uid, $dealId, $money, $data['discountId'], $consumeType);
        ajax_return($ret);
    }
}
