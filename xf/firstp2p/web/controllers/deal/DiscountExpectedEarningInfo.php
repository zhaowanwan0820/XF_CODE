<?php

namespace web\controllers\deal;

use libs\utils\Site;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

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

        $siteId = Site::getId();
        $uid = $GLOBALS['user_info']['id'];
        $consumeType = $data['consumeType'] ? intval($data['consumeType']) : CouponGroupEnum::CONSUME_TYPE_P2P;
        $dealId = $data['dealId'];
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_P2P) {
            // 对于p2p的类型，dealId进行解密
            $dealId = Aes::decryptForDeal($dealId);
        } else if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
            $dealId = intval($dealId);
        }

        $rpcParams = array($uid, $dealId, $money, $data['discountId'], $siteId, $consumeType);
        $ret = $this->rpc->local('DiscountService\expectedEarningInfo', $rpcParams);
        ajax_return($ret);
    }
}
