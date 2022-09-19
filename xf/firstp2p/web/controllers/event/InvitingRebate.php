<?php
/**
 * Index.php
 *
 * @date 2014-08-21
 */

namespace web\controllers\event;

use web\controllers\BaseAction;
use core\service\CouponService;
class InvitingRebate extends BaseAction {

    public function init() {
        //$this->check_login();
    }

    public function invoke() {

        $user_id = !empty($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : '';
        $coupon = '';
        $share_url = PRE_HTTP.APP_HOST . '/user/login?backurl=event/inviting_rebate';
        $coupon_url = PRE_HTTP.APP_HOST . '/user/login?backurl=account/coupon';
        if (!empty($user_id)) {
            $coupon = $this->rpc->local('CouponService\getOneUserCoupon', array($user_id));
            $coupon = $coupon['short_alias'];
            $coupon_url = PRE_HTTP.APP_HOST.'/account/coupon';
            $share_url = '';
        }
        $this->tpl->assign('coupon', $coupon);
        $this->tpl->assign('user_id', $user_id);
        $this->tpl->assign('coupon_url', $coupon_url);
        $this->tpl->assign('share_url', $share_url);
        $this->tpl->display("web/views/event/inviting_rebate.html");
    }

}
