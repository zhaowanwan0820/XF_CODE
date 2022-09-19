<?php

namespace web\controllers\deal;

use libs\utils\Site;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use libs\utils\Logger;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * 可用的投资券的个数
 */
class DiscountAvaliableCount extends BaseAction {
    public function init() {
        $this->check_login();

        $this->form = new Form();
        $this->form->rules = array(
            'dealId' => array('filter' => 'required', 'message' => 'dealId参数缺失'),
            'consumeType' => array('filter' => 'int', 'message' => 'consumeType参数类型为int'),
       );
        if (!$this->form->validate()) {
            $ret = array('error' => 2000, 'msg' => $this->form->getErrorMsg());
            return ajax_return($ret);
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $uid = $GLOBALS['user_info']['id'];
        $siteId = Site::getId();
        $consumeType = $data['consumeType'] ? intval($data['consumeType']) : CouponGroupEnum::CONSUME_TYPE_P2P;
        $type = 0;

        $dealId = $data['dealId'];
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
            $currentDealId = intval(get_config_db('DUOTOU_CURRENT_DEAL_ID', $siteId));
            $dealId = intval($dealId);
            // 灵活投智多鑫只能使用返现券
            if ($dealId == $currentDealId) {
                $type = CouponGroupEnum::DISCOUNT_TYPE_CASHBACK;
            }
        } else if ($consumeType == CouponGroupEnum::CONSUME_TYPE_P2P) {
            // 对于p2p的类型，dealId进行解密
            $dealId = Aes::decryptForDeal($dealId);
        }

        $rpcParams = array($uid, $dealId, false, $siteId, $consumeType, $type);
        $ret = $this->rpc->local('DiscountService\getAvailableDiscountCount', $rpcParams);
        ajax_return($ret);
    }
}
