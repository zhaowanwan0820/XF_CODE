<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class AjaxAcquireExchange extends AppBaseAction {
    private $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'storeId' => array("filter" => "required", "message"=>"storeId is error"),
            'useRules' => array("filter" => "required", "message"=>"useRules is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'receiverName' => array('filter' => 'string'),
            'receiverPhone' => array('filter' => 'string'),
            'receiverCode' => array('filter' =>'string'),
            'receiverArea' => array('filter' => 'string'),
            'receiverAddress' => array('filter' => 'string'),
        );
        //extra信息从o2o获取，动态添加到rules中
        $this->storeId = isset($_POST['storeId']) ? intval($_POST['storeId']) : 0;
        $this->useRules = isset($_POST['useRules']) ? intval($_POST['useRules']) : 0;
        if($this->storeId && $this->useRules) {
            //增加错误处理，防止获取表单配置时接口失败导致页面白页
            $this->formConfig = $this->rpc->local('O2OService\getExchangeForm',array($this->storeId,$this->useRules));
            if(false === $this->formConfig) {
                $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
                $this->template = 'api/views/_v33/gift/gift_fail.html';
                return false;
            }
        }
        if(isset($this->formConfig['storeName'])) {
            $this->storeName = $this->formConfig['storeName'];
        }
        if(isset($this->formConfig['titleName'])) {
            $this->titleName = $this->formConfig['titleName'];
        }
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $this->form->rules[$k] = array('filter' => $v['type']);
            }
        }
        if(isset($this->formConfig['msgConf'])) {
            $this->msgConf = $this->formConfig['msgConf'];
            $this->msgConf['storeName'] = $this->storeName;
        }
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

        $couponGroupId = $data['couponGroupId'];
        $loadId = intval($data['load_id']);
        $action = intval($data['action']);
        //根据receiverInfoMap信息获取表单数据
        foreach($this->receiverInfoMap as $val) {
            $receiverParam[$val] = self::getFormData($data, $val);
        }
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
            }
        }
        $user_id = $loginUser['id'];
        $isNeedExchange = 1;//新版接口，需要完成兑换操作
        $rpcParams = array($couponGroupId, $loginUser['id'], $action, $loadId, $loginUser['mobile'], $receiverParam, $extraParam, $isNeedExchange);
        PaymentApi::log('线上线下 - ajax领取兑换优惠券 - 请求参数'.var_export($rpcParams, true));
        $gift = $this->rpc->local('O2OService\acquireExchange', $rpcParams);
        PaymentApi::log('线上线下 - ajax领取兑换优惠券 - 请求参数'.var_export($rpcParams, true));
        $this->json_data = $gift;
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }
}
