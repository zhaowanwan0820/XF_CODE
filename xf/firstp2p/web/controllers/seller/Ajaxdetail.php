<?php
/**
 *  * 优惠券查询
 *   */

namespace web\controllers\seller;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use api\conf\Error;

class Ajaxdetail extends BaseAction {
    public function init() {
        parent::init();
        if(!$this->check_login()) return false;
            $this->form = new Form();
            $this->form->rules = array(
            'couponCode' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            $error = Error::get($this->form->getErrorMsg());
            $data = array('errno' => $error['errno'], 'error' => $error['errmsg']);
            return ajax_return($data);
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS ['user_info'];
        $page = intval($data['page']);
        $page = $page ? $page : 1;
        $couponInfo = $this->rpc->local('O2OService\getCouponInfoByCouponCode', array($data['couponCode'], $loginUser['id']));
        if ($this->rpc->local('O2OService\hasError')) {
            $data['errno'] = 1;
            $data['error'] = $this->rpc->local('O2OService\getErrorMsg');
        }
        if(isset($couponInfo['couponInfo']['id']) && $couponInfo['couponInfo']['id']>0) {
            $data = array('errno' => 0, 'error' => '');
        } else {
            $data = array('errno' => 1, 'error' => '输入的券码不存在');
        }
        return ajax_return($data);
    }
}
