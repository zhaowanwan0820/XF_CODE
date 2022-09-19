<?php

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\CouponLogService;
use core\service\CouponBindService;

require_once dirname(__FILE__).'/../../../app/Lib/page.php';

class Serviceaward extends BaseAction
{
    public function init()
    {
        if (!$this->check_login()) {
            return false;
        }
    }

    public function invoke()
    {

        $user_id = intval($GLOBALS['user_info']['id']);

        $coupon = $this->rpc->local('CouponService\getUserCoupon', array($user_id));
        $this->tpl->assign('coupon', $coupon);

        $couponLogService  = new CouponLogService(CouponLogService::MODULE_TYPE_P2P,CouponLogService::DATA_TYPE_SERVICE);
        $rotalRefererRebateAmount = $couponLogService->getTotalRefererRebateAmount($user_id);
        $this->tpl->assign('rotalRefererRebateAmount',$rotalRefererRebateAmount);

        $is_used_code = $this->rpc->local('CouponService\isCouponUsed', array($user_id));
        if ((!$GLOBALS['user_info']['real_name'] || $GLOBALS['user_info']['idcardpassed'] != 1) && !$is_used_code) {
            $this->tpl->assign('is_not_code', true);
        } else {
            $this->tpl->assign('is_not_code', false);
        }

        $couponModelTypes = CouponLogService::getModelTypes();
        $couponModelTypes['p2p'] = '服务奖励';
        unset($couponModelTypes['reg']);
        $this->tpl->assign('couponModelTypes', $couponModelTypes);

        $share_url = get_domain() . '/?cn=' . $coupon['short_alias'];
        $this->tpl->assign('share_url', $share_url);

        $this->tpl->assign('inc_file', 'web/views/account/service_award.html');
        $this->template = 'web/views/account/frame.html';
    }
}
