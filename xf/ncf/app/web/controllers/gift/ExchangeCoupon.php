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
use core\service\o2o\CouponService;
use core\enum\CouponGroupEnum;


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
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];
        $extraInfo = $data;
        $response = CouponService::giftExchangeCoupon($loginUser['id'], $data['couponId'], $data['storeId'], $data['useRules'], $extraInfo);
        if(empty($response) || empty($response['coupon'])) {
            $this->tpl->assign('errMsg', CouponService::getErrorMsg());
            $this->template = 'web/views/gift/gift_fail.html';
            return false;
        }

        $receiverParam = $response['receiverParam'];
        $extraParam = $response['extraParam'];
        $coupon = $response['coupon'];

        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('receiverParam', $receiverParam);
        $this->tpl->assign('extraParam', $extraParam);
        $this->tpl->assign('formConfig', $couponExtra);
        $this->tpl->assign('coupon', $coupon);
        $this->template = 'web/views/gift/gift_suc.html';
    }
}

