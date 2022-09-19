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
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

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
        if ($couponId) {
            $rpcParams = array($couponId, $user_id);
            PaymentApi::log('webo2o - 进入领取兑换详情页面 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
            $gift_detail = $this->rpc->local('O2OService\getCouponInfo', $rpcParams);
        } else {
            PaymentApi::log('webo2o - 进入领取兑换详情页面 - 请求参数'.json_encode($couponGroupId, JSON_UNESCAPED_UNICODE));
            $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $loginUser['id'],
                $data['action'], $data['load_id'], $dealType));
        }

        if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_ORDER_USE_RULES)) {
            $useFormId = $gift_detail['storeId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($useFormId, $gift_detail['useRules']));
            $this->tpl->assign('formConfig', $formConfig['form']);
            $this->tpl->assign('storeName', $formConfig['storeName']);
            $this->tpl->assign('titleName', $formConfig['titleName']);
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
