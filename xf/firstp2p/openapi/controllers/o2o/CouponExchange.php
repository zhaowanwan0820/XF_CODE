<?php
/**
 * 获取优惠券组详情
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class CouponExchange extends BaseAction {
    private $msgConf = array(
        'needMsg' => 0,
        'storeName' => '',
        'tplId' => '',
        'storePhone' => ''
    );
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            // O2O卷ID
            'couponId' => array('filter' => 'required'),
            // 商户ID
            'storeId' => array('filter' => 'required'),
            // 规则
            'useRules' => array('filter' => 'required'),
            // 可能填写的表单（选填表单）姓名，电话，邮编，省市，地址
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
                $msg = $this->rpc->local('O2OService\getErrorMsg');
                $this->setErr('ERR_COUPON_ERROR', $msg);
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

        // 收货信息
        $receiverParam = array();
        // 收卷信息
        $extraParam = array();
        // 收货信息的字段列表
        $receiverFields = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
        // 020 service里面看起来貌似不能为null
        foreach($receiverFields as $one){
            $receiverParam[$one] = isset($data[$one]) ? $data[$one] : '';
        }
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = isset($data[$k]) ? $data[$k] : '';
            }
        }

        // 开始调用o2oserviceI
        $userId = $loginUser->userId;
        //exchangeCoupon($couponId, $userId, $storeId, $receiverParam = array(), $extraParam = array(), $msgConf = array())
        $couponInfo = $this->rpc->local('O2OService\exchangeCoupon', array($data['couponId'], $userId, $this->storeId, $receiverParam, $extraParam, $this->msgConf));
        $ret = array();
        // 领取成功
        if (is_array($couponInfo)) {
            $coupon['couponNumber']= $couponInfo['coupon']['couponNumber'];
            $coupon['couponDesc']= $couponInfo['couponGroup']['couponDesc'];
            $coupon['isShowCouponNumber']= $couponInfo['couponGroup']['isShowCouponNumber'];
            $coupon['productName'] = $couponInfo['product']['productName'];
            $coupon['updateTime'] = $couponInfo['coupon']['updateTime'];
            $coupon['useEndTime'] = $couponInfo['coupon']['useEndTime'];
            $coupon['storeName'] = $this->storeName;
            $coupon['titleName'] = $this->titleName;
            if (in_array($this->useRules, array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME))) {
                $ret['couponExtra']['receiverName'] = array('displayName' => '姓名', 'value' => $receiverParam['receiverName']);
                $ret['couponExtra']['receiverPhone'] = array('displayName' => '手机号', 'value' => $receiverParam['receiverPhone']);
                $ret['couponExtra']['receiverCode'] = array('displayName' => '邮政编码', 'value' => $receiverParam['receiverCode']);
                $ret['couponExtra']['receiverAddress'] = array('displayName' => '地址', 'value' => $receiverParam['receiverAddress']);
            } elseif(in_array($this->useRules, array(CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME))) {
                if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])){
                    foreach($this->formConfig['form'] as $k=>$v) {
                        $ret['couponExtra'][$k] = array('displayName' => $v['displayName'], 'value' => $extraParam[$k]);
                    }
                }
            }
            $ret['coupon'] = $coupon;
            $this->json_data = $ret;
        } else {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }
        return true;
    }
}
