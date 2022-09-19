<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class ExchangeCoupon extends PageBaseAction {

    const IS_H5 = true;

    private $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
    private $formConfig = array();
    private $msgConf = array(
        'needMsg' => 0,
        'storeName' => '',
        'tplId' => '',
        'storePhone' => ''
    );
    private $useRules = null;
    private $storeName = null;
    private $titleName = null;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
            'storeId' => array('filter' => 'required'),
            'useRules' => array('filter' => 'required'),
            'receiverName' => array('filter' => 'string'),
            'receiverPhone' => array('filter' => 'string'),
            'receiverCode' => array('filter' =>'string'),
            'receiverArea' => array('filter' => 'string'),
            'receiverAddress' => array('filter' => 'string'),
        );
        //extra信息从o2o获取，动态添加到rules中
        $this->storeId = isset($_REQUEST['storeId']) ? intval($_REQUEST['storeId']) : 0;
        $this->useRules = isset($_REQUEST['useRules']) ? intval($_REQUEST['useRules']) : 0;
        if($this->storeId && $this->useRules) {
            //增加错误处理，防止获取表单配置时接口失败导致页面白页
            $this->formConfig = $this->rpc->local('O2OService\getExchangeForm',array($this->storeId,$this->useRules));
            if(false === $this->formConfig) {
                $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
                $this->template = 'openapi/views/coupon/gift_fail.html';
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
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $loginUser = $loginUser->toArray();
        $this->tpl->assign('userInfo', $loginUser);
        $receiverParam = array();
        $extraParam = array();
        //根据receiverInfoMap信息获取表单数据
        foreach($this->receiverInfoMap as $val) {
            $receiverParam[$val] = self::getFormData($data, $val);
        }
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
            }
        }
        $user_id = $loginUser['userId'];
        $rpcParams = array($user_id,$receiverParam,$extraParam);
        PaymentApi::log('openapi - 兑换优惠券 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
        $couponInfo = $this->rpc->local('O2OService\exchangeCoupon', array($data['couponId'], $user_id, $this->storeId, $receiverParam, $extraParam, $this->msgConf));
        PaymentApi::log('openapi - 兑换优惠券 - 请求结果'.json_encode($couponInfo, JSON_UNESCAPED_UNICODE));
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('oauth_token', $data['oauth_token']);
        $from_source = \es_session::get('from_source');
        $redirect_uri = \es_session::get('redirect_uri');
        $this->tpl->assign('from_source', $from_source);
        $this->tpl->assign('redirect_uri', $redirect_uri);
        $coupon['useRules'] = $couponInfo['coupon']['useRules'];
        if (is_array($couponInfo)) {
            $couponExtra = array();
            $coupon['productName'] = $couponInfo['product']['productName'];
            $coupon['updateTime'] = $couponInfo['coupon']['updateTime'];
            $coupon['useEndTime'] = $couponInfo['coupon']['useEndTime'];
            $coupon['couponDesc'] = $couponInfo['couponGroup']['couponDesc'];
            if (in_array($this->useRules, array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME))) {
                $coupon['receiverName'] = $receiverParam['receiverName'];
                $coupon['receiverPhone'] = $receiverParam['receiverPhone'];
                $coupon['receiverCode'] = $receiverParam['receiverCode'];
                $coupon['receiverAddress'] = $receiverParam['receiverAddress'];
            } elseif(in_array($this->useRules, array(CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME))) {
                $coupon['storeName'] = $this->storeName;
                $coupon['titleName'] = $this->titleName;
                if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])){
                    foreach($this->formConfig['form'] as $k=>$v) {
                        $couponExtra[$k] = array('displayName' => $v['displayName'], 'value' => $extraParam[$k]);
                    }
                }
                $this->tpl->assign('formConfig', $couponExtra);
            }

            $this->tpl->assign('receiverParam', $receiverParam);
            $this->tpl->assign('extraParam', $extraParam);
            $this->tpl->assign('coupon', $coupon);
            $this->template = 'openapi/views/coupon/gift_suc.html';
        } else {
            $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
            $this->template = 'openapi/views/coupon/gift_fail.html';
        }
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }
}
