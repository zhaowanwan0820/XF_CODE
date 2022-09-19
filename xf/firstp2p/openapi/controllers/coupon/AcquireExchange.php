<?php

namespace openapi\controllers\coupon;

use openapi\controllers\PageBaseAction;
use libs\web\Form;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class AcquireExchange extends PageBaseAction {

    const IS_H5 = true;

    private $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
    private $needForm = array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME, CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME, CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT);
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'storeId' => array("filter" => "required", "message"=>"storeId is error"),
            'useRules' => array("filter" => "required", "message"=>"useRules is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'receiverName' => array('filter' => 'string'),
            'receiverPhone' => array('filter' => 'string'),
            'receiverCode' => array('filter' =>'string'),
            'receiverArea' => array('filter' => 'string'),
            'receiverAddress' => array('filter' => 'string'),
        );
        //extra信息从o2o获取，动态添加到rules中
        $this->storeId = isset($_REQUEST['storeId']) ? intval($_REQUEST['storeId']) : 0;
        $this->useRules = isset($_REQUEST['useRules']) ? intval($_REQUEST['useRules']) : 0;
        if($this->storeId && in_array($this->useRules, $this->needForm)) {
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
        #$loginUser['id'] = 40;
        $couponGroupId = $data['couponGroupId'];
        $loadId = intval($data['load_id']);
        $action = intval($data['action']);
        $user_id = $loginUser['userId'];

        $this->tpl->assign('oauth_token', $data['oauth_token']);
        $this->tpl->assign('load_id', $data['load_id']);
        $this->tpl->assign('site_id', $data['site_id']);
        // 只有是交易的action，才需要验证
        if (in_array($action, CouponGroupEnum::$TRIGGER_DEAL_MODES) && $loadId > 0) {
            // 根据load_id信息获取触发券组列表校验groupId，防止前端篡改groupId
            $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
            $triggerParams = array($user_id, $action, $loadId, $dealType);
            $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $triggerParams);
            if (empty($couponGroupList) || !isset($couponGroupList[$couponGroupId])) {
                // 非法操作
                $msg = '抢光了！下次要尽早哦！';
                // 控制器标志
                $this->tpl->assign('flag', 'acquireExchange');
                $this->tpl->assign('errMsg', $msg);
                $this->template = 'openapi/views/coupon/gift_fail.html';
                return false;
            }
        }

        //根据receiverInfoMap信息获取表单数据
        foreach($this->receiverInfoMap as $val) {
            $receiverParam[$val] = self::getFormData($data, $val);
        }
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
            }
        }
        $isNeedExchange = 1;//新版接口，需要完成兑换操作
        //新版接口的领取即兑换需三方标志的操作，前端页面没phone参数，需要专门处理
        if($this->useRules == CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT) {
            $extraParam['phone'] = $loginUser['mobile'];
        }

        $rpcParams = array($couponGroupId, $user_id, $action, $loadId, $loginUser['mobile'], $receiverParam,
            $extraParam, $isNeedExchange, $dealType);

        $gift = $this->rpc->local('O2OService\acquireExchange', $rpcParams);
        $from_source = \es_session::get('from_source');
        $redirect_uri = \es_session::get('redirect_uri');
        $this->tpl->assign('from_source', $from_source);
        $this->tpl->assign('redirect_uri', $redirect_uri);
        $this->tpl->assign('userInfo', $loginUser);

        if (empty($gift)) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->tpl->assign('errMsg', $msg);
            $this->tpl->assign('action', $action);
            $this->tpl->assign('flag', 'acquireExchange');//控制器标志
            $this->template = 'openapi/views/coupon/gift_fail.html';
        } else {
            $this->tpl->assign('receiverParam', $receiverParam);
            $this->tpl->assign('extraParam', $extraParam);
            $this->tpl->assign('coupon', $gift);
            $this->template = 'openapi/views/coupon/gift_suc.html';
        }
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }

}
