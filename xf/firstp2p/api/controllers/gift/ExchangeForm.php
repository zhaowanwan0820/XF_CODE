<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class ExchangeForm extends ApiBaseAction {
    private $ios_version = '';
    // 表单配置
    static $tplConfig = array(
        CouponGroupEnum::ONLINE_GOODS_REPORT => 'goodsform',
        CouponGroupEnum::ONLINE_GOODS_REALTIME => 'goodsform',
        CouponGroupEnum::ONLINE_COUPON_REPORT => 'couponform',
        CouponGroupEnum::ONLINE_COUPON_REALTIME => 'couponform',
    );

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->ios_version = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
            'storeId' => array('filter' => 'required'),
            'useRules' => array('filter' => 'required'),
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

        $user_id = $loginUser['id'];
        $rpcParams = array($user_id);
        PaymentApi::log('线上线下 - 进入线上券兑换页面 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
        $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($data['storeId'], $data['useRules']));
        $couponDetail = $this->rpc->local('O2OService\getCouponInfo', array($data['couponId'], $user_id));
        PaymentApi::log('线上线下 - 进入线上券兑换页面 - 请求结果'.json_encode($formConfig, JSON_UNESCAPED_UNICODE));

        $this->tpl->assign('formConfig', $formConfig['form']);
        $this->tpl->assign('storeName', $formConfig['storeName']);
        $this->tpl->assign('titleName', $formConfig['titleName']);
        $this->tpl->assign('usertoken', $data['token']);
        $this->tpl->assign('storeId', $data['storeId']);
        $this->tpl->assign('useRules', $data['useRules']);
        $this->tpl->assign('couponId', $data['couponId']);
        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('coupon', $couponDetail);
        $this->tpl->assign('appversion', $this->ios_version);//增加ios版本判断，供前端选择是否编码中文
        if($this->app_version >=331) {
            $this->template = $this->getTemplate('exchange_form');
        } else {
            $this->template = $this->getTemplate(self::$tplConfig[$data['useRules']]);
        }
    }
}
