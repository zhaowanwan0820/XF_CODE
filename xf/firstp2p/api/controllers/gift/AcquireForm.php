<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * 新版优化的表单页面，包含兑换需要展示的表单信息
 */

class AcquireForm extends ApiBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required"),
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
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $couponGroupId = intval($data['couponGroupId']);
        PaymentApi::log('线上线下 - 进入领取兑换详情页面 - 请求参数'.var_export($couponGroupId, true));
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $loginUser['id'], $data['action'], $data['load_id']));
        PaymentApi::log('线上线下 - 进入领取兑换详情页面 - 请求结果'.var_export($gift_detail, true));
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
        $this->tpl->assign('usertoken', $this->form->data['token']);
        $this->template = $this->getTemplate('exchange_form');
    }

}
