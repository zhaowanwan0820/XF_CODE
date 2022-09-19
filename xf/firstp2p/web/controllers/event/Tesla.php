<?php
/**
 * 特斯拉活动页面
 *
 * @date 2014-09-14
 */

namespace web\controllers\event;

use web\controllers\BaseAction;

class Tesla extends BaseAction {

    public function init() {

    }

    public function invoke() {

        $coupon = '';
        $user_id = !empty($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : '';
        $login_url = PRE_HTTP.APP_HOST . '/user/login?backurl=event/tesla';

        if (!empty($user_id)) {
            $coupon = $this->rpc->local('CouponService\getOneUserCoupon', array($user_id));
            $coupon = $coupon['short_alias'];
        }

        $this->tpl->assign('coupon', $coupon);
        $this->tpl->assign('user_id', $user_id);
        $this->tpl->assign('login_url', $login_url);
        $this->tpl->assign('ios_url', app_conf('IOS_DOWNLOAD_URL'));
        $this->tpl->assign('android_url', app_conf('ANDROID_DOWNLOAD_URL'));
    }

}
