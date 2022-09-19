<?php
/**
 * 商家兑换券详情页面
 */

namespace api\controllers\seller;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponEnum;

class Detail extends BaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponCode' => array('filter' => 'required'),
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
        $this->tpl->assign('token', $this->form->data['token']);

        $couponInfo = $this->rpc->local('O2OService\getCouponInfoByCouponCode', array($data['couponCode'], $loginUser['id']));
        if ($this->rpc->local('O2OService\hasError')) {
           $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
           $this->template = $this->getTemplate('apply_fail');
           return;
        }

        //var_dump($couponInfo);exit();
        if (!(intval(@$couponInfo['couponInfo']['id']) > 0)) {
           $this->tpl->assign('errMsg', '输入的券码不存在');
           $this->template = $this->getTemplate('apply_fail');
           return;
        }

        if ($couponInfo['couponInfo']['status'] == CouponEnum::STATUS_EXPIRED) {
           $this->tpl->assign('errMsg', '输入的券码已经过期或者失效');
           $this->template = $this->getTemplate('apply_fail');
           return;
        }

        if ($couponInfo['couponInfo']['status'] == CouponEnum::STATUS_USED) {
           $this->tpl->assign('errMsg', '输入的券码已使用');
           $this->template = $this->getTemplate('apply_fail');
           return;
        }

        $this->tpl->assign('coupon', $couponInfo['couponInfo']);
        $this->tpl->assign('userInfo', $couponInfo['userInfo']);
        $this->template = $this->getTemplate('detail');
        // 显示兑换券详情
    }
}
