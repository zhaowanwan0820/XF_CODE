<?php
/**
 * 获取优惠券组详情
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;

class ExchangeForm extends BaseAction {
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            'couponId' => array('filter' => 'required'),
            'storeId' => array('filter' => 'required'),
            'useRules' => array('filter' => 'required'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
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

        $userId = $loginUser->userId;
        $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($data['storeId'], $data['useRules']));
        $ret = array();
        $ret['formConfig'] = $formConfig['form'];
        $ret['storeName'] = $formConfig['storeName'];
        $ret['titleName'] = $formConfig['titleName']?$formConfig['titleName']:'请填写以下表单用于接收优惠券';
        $ret['storeId'] = $data['storeId'];
        $ret['useRules'] = $data['useRules'];
        $ret['couponId'] = $data['couponId'];
        $this->json_data = $ret;
        return true;
    }
}
