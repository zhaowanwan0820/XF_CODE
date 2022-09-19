<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class ExchangeCoupon extends ApiBaseAction {
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

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
            'storeId' => array('filter' => 'required'),
            'useRules' => array('filter' => 'required'),
            'address_id' => array('filter' => 'int'),
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
                $this->template = $this->getTemplate('exchange_fail');
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
        $this->tpl->assign('userInfo', $loginUser);
        $receiverParam = array();
        $extraParam = array();
        // 根据地址ID获取收货人地址信息
        if(!empty($data['address_id'])) {
            $address = $this->rpc->local('AddressService\getOne', array($loginUser['id'],$data['address_id']));
            $receiverParam['receiverName'] = $address['consignee'];
            $receiverParam['receiverPhone'] = $address['mobile'];
            $receiverParam['receiverArea'] = $address['area'];
            $receiverParam['receiverAddress'] = $address['address'];
        } else { //根据receiverInfoMap信息获取表单数据
            foreach ($this->receiverInfoMap as $val) {
                $receiverParam[$val] = self::getFormData($data, $val);
            }
        }

        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
                #$extraParam[$k] = self::getFormData($this->formConfig['form'][$k], $v['name']);
            }
        }
        $user_id = $loginUser['id'];
        $rpcParams = array($user_id,$receiverParam,$extraParam);
        PaymentApi::log('线上线下 - 兑换优惠券 - 请求参数'.var_export($rpcParams, true));
        $couponInfo = $this->rpc->local('O2OService\exchangeCoupon', array($data['couponId'], $user_id, $this->storeId, $receiverParam, $extraParam, $this->msgConf));
        PaymentApi::log('线上线下 - 兑换优惠券 - 请求结果'.var_export($couponInfo, true));
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
                $coupon['receiverArea'] = $receiverParam['receiverArea'];
                $coupon['receiverAddress'] = $receiverParam['receiverAddress'];
                if($this->app_version >= 331) {
                    $this->tpl->assign('receiverParam', $receiverParam);
                    $this->tpl->assign('extraParam', $extraParam);
                    $this->template = $this->getTemplate('gift_suc');
                } else {
                    $this->template = $this->getTemplate('goods_suc');
                }
            } elseif(in_array($this->useRules, array(CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME))) {
                $coupon['storeName'] = $this->storeName;
                $coupon['titleName'] = $this->titleName;
                if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])){
                    foreach($this->formConfig['form'] as $k=>$v) {
                        $couponExtra[$k] = array('displayName' => $v['displayName'], 'value' => $extraParam[$k]);
                    }
                }
                $this->tpl->assign('formConfig', $couponExtra);
                if($this->app_version >= 331) {
                    $this->tpl->assign('receiverParam', $receiverParam);
                    $this->tpl->assign('extraParam', $extraParam);
                    $this->template = $this->getTemplate('gift_suc');
                } else {
                    $this->template = $this->getTemplate('coupon_suc');
                }
            } else {
                $this->template = $this->getTemplate('apply_suc');
            }

            $signData = $this->rpc->local('O2OService\addSign', array($coupon, $loginUser));
            foreach ($signData as $key => $val) {
                $this->tpl->assign($key, $val);
            }
            $this->tpl->assign('coupon', $coupon);
        } else {
            $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
            if($this->app_version >= 331) {
                $this->template = $this->getTemplate('gift_fail');
            } else {
                $this->template = $this->getTemplate('exchange_fail');
            }
        }
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }

    public function _before_invoke() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datas = $_POST;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $datas = $_GET;
            $datas = array_diff_key($datas, array('act' => '', 'city' => '', 'ctl' => '', '1' => '', '2' => ''));
        } else {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名不正确
            return false;
        }

        $this->app_version = isset($_SERVER['HTTP_VERSION']) ? intval($_SERVER['HTTP_VERSION']) : 100;
        $this->setAutoViewDir();

        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;

        //特殊用户处理
        if (\libs\utils\Block::isSpecialUser($userId)) {
            define('SPECIAL_USER_ACCESS', true);
            if (\libs\utils\Block::checkAccessLimit($userId) === false) {
                throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
            }
        }

        return true;
    }

}
