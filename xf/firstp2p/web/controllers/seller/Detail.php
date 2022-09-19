<?php
/**
 *  * 商家兑换券详情页面
 *   */

namespace web\controllers\seller;

use libs\web\Form;
use api\conf\Error;
use web\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponEnum;

class Detail extends BaseAction {
    public function init() {
        parent::init();
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'couponCode' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            $error = Error::get($this->form->getErrorMsg());
            $this->show_error($error['errmsg']);
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS ['user_info'];

        $couponInfo = $this->rpc->local('O2OService\getCouponInfoByCouponCode', array($data['couponCode'], $loginUser['id']));
        if ($this->rpc->local('O2OService\hasError')) {
            $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
            $this->template = 'web/views/v2/seller/apply_fail.html';
            return;
        }

        if (!(intval(@$couponInfo['couponInfo']['id']) > 0)) {
            $this->tpl->assign('errMsg', '输入的券码不存在');
            $this->template = 'web/views/v2/seller/apply_fail.html';
            return;
        }

        if ($couponInfo['couponInfo']['status'] == CouponEnum::STATUS_EXPIRED) {
            $this->tpl->assign('errMsg', '输入的券码已经过期或者失效');
            $this->template = 'web/views/v2/seller/apply_fail.html';
            return;
        }

        if ($couponInfo['couponInfo']['status'] == CouponEnum::STATUS_USED) {
            $this->tpl->assign('errMsg', '输入的券码已使用');
            $this->template = 'web/views/v2/seller/apply_fail.html';
            return;
        }

        $this->tpl->assign('coupon', $couponInfo['couponInfo']);
        $this->tpl->assign('userInfo', $couponInfo['userInfo']);
        $this->template = 'web/views/v2/seller/detail.html';
        // 显示兑换券详情
    }
}
