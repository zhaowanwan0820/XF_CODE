<?php
/**
 * 兑换优惠券
 *
 *
 */
namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\O2OService;

class ExchangeCoupon extends BaseAction {
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
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
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
                $this->template = 'web/views/gift/gift_fail.html';
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
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];
        
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
        $user_id = $loginUser['id'];
        $rpcParams = array($user_id,$receiverParam,$extraParam);
        PaymentApi::log('webo2o- 兑换优惠券 - 请求参数'.json_encode($rpcParams,JSON_UNESCAPED_UNICODE));
        $couponInfo = $this->rpc->local('O2OService\exchangeCoupon', array($data['couponId'], $user_id, $this->storeId, $receiverParam, $extraParam, $this->msgConf));
        PaymentApi::log('webo2o - 兑换优惠券 - 请求结果'.json_encode($couponInfo, JSON_UNESCAPED_UNICODE));
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
                $this->tpl->assign('receiverParam', $receiverParam);
                $this->tpl->assign('extraParam', $extraParam);
            } elseif(in_array($this->useRules, array(CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME))) {
                $coupon['storeName'] = $this->storeName;
                $coupon['titleName'] = $this->titleName;
                if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])){
                    foreach($this->formConfig['form'] as $k=>$v) {
                        $couponExtra[$k] = array('displayName' => $v['displayName'], 'value' => $extraParam[$k]);
                    }
                }
                $this->tpl->assign('formConfig', $couponExtra);
                $this->tpl->assign('receiverParam', $receiverParam);
                $this->tpl->assign('extraParam', $extraParam);
            }
            $coupon['id'] = $data['couponId'];
            $this->tpl->assign('coupon', $coupon);
            $this->template = 'web/views/gift/gift_suc.html';
        } else {
            $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
        }
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }
}
