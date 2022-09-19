<?php
/**
 * 校验邀请码
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;

class CheckInvitecode extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'code' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return false;
        }
    }

    public function invoke() {
        $ajaxData = array();
        $code = isset($this->form->data['code'])? strtoupper(trim($this->form->data['code'])):'';
        $ret = $this->rpc->local('CouponService\checkCoupon', array($code));

        if($ret !== false && $ret['short_alias'] !== $code){
            $ajaxData = array('errno' => 1, 'error' =>'邀请码不存在', 'data' => array( 'type'=>'alias','alias'=>'','userName'=>'null' ));
        }else if ( $ret !== false && empty($ret['coupon_disable']) ) {
            $ajaxData = array('errno' => 0, 'error' =>'', 'data' => array('type'=>'alias','alias'=>'F00441','userName'=>'null'));
        } else {
            $ajaxData = array('errno' => 1, 'error' =>$GLOBALS['lang']['COUPON_DISABLE'], 'data' => array( 'type'=>'alias','alias'=>'','userName'=>'null' ));
        }
        return ajax_return($ajaxData);
    }

}
