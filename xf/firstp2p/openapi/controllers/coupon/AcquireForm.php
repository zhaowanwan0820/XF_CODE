<?php

namespace openapi\controllers\coupon;

use openapi\controllers\PageBaseAction;
use libs\web\Form;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * 新版优化的表单页面，包含兑换需要展示的表单信息
 */
class AcquireForm extends PageBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "int"),
            'couponId' => array('filter' => "int"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'action' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $loginUser = $loginUser->toArray();
        $couponGroupId = intval($data['couponGroupId']);
        $couponId = intval($data['couponId']);
        if ($couponId) {
            //如果存在couponId，说明是已经领取还未兑换
            PaymentApi::log('openapi - 进入领取兑换详情页面 - 请求参数couponId'.json_encode($couponId, JSON_UNESCAPED_UNICODE));
            $gift_detail = $this->rpc->local('O2OService\getCouponInfo', array($data['couponId'], $loginUser['userId']));
        } else {
            //未领取进入
            PaymentApi::log('openapi - 进入领取兑换详情页面 - 请求参数couponGroupId'.json_encode($couponGroupId, JSON_UNESCAPED_UNICODE));
            $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $loginUser['userId'], $data['action'], $data['load_id']));
        }
        PaymentApi::log('openapi - 进入领取兑换详情页面 - 请求结果'.json_encode($gift_detail, JSON_UNESCAPED_UNICODE));
        if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_ORDER_USE_RULES)) {
            $useFormId = $gift_detail['useFormId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($useFormId, $gift_detail['useRules']));
            $this->tpl->assign('formConfig', $formConfig['form']);
            $this->tpl->assign('storeName', $formConfig['storeName']);
            $this->tpl->assign('titleName', $formConfig['titleName']);
        }

        $this->tpl->assign('coupon', $gift_detail);
        $this->tpl->assign('action', $this->form->data['action']);
        $this->tpl->assign('load_id', $this->form->data['load_id']);
        $this->tpl->assign('oauth_token', $this->form->data['oauth_token']);
        $this->template = 'openapi/views/coupon/exchange_form.html';
    }

}
