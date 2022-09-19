<?php
/**
 * 领取优惠券
 *
 *
 */
namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use core\service\o2o\CouponService;
use core\enum\CouponGroupEnum;


class AcquireForm extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'couponId' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];
        $couponGroupId = intval($data['couponGroupId']);
        $couponId = isset($data['couponId']) ? intval($data['couponId']) : 0;
        $user_id = $loginUser['id'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $extraInfo = array('action' => $data['action'], 'loadId' => $data['load_id'], 'dealType' => $dealType);
        $response = CouponService::giftAcquireForm($user_id, $couponId, $couponGroupId, $extraInfo);
        $gift_detail = isset($response['gift']) ? $response['gift'] : array();

        if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_ORDER_USE_RULES)) {
            $this->tpl->assign('formConfig', $gift_detail['formConfig']);
            $this->tpl->assign('storeName', $gift_detail['storeName']);
            $this->tpl->assign('titleName', $gift_detail['titleName']);
        }
        $this->tpl->assign('coupon', $gift_detail);
        $this->tpl->assign('couponId', $couponId);
        $this->tpl->assign('couponGroupId', $data['couponGroupId']);
        $this->tpl->assign('action', $data['action']);
        $this->tpl->assign('load_id', $data['load_id']);
        $this->tpl->assign('deal_type', $dealType);
        $this->template = 'web/views/gift/exchange_form.html';
    }
}
