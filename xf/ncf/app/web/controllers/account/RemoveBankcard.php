<?php

/**
 * RemoveBankcard.php
 *
 * @date 2014年4月8日14:52:33
 * @author  wangqunqiang <wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\dao\BankModel;
use core\service\ConfService;
use libs\web\Url;
use libs\payment\supervision\Supervision;
use core\enum\UserEnum;
use core\enum\UserAccountEnum;
use core\service\face\FaceService;
use core\service\coupon\CouponService;

class RemoveBankcard extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        session_start();
        $_SESSION['resetbank'] = 1;
        $user_info = $GLOBALS['user_info'];

        if (FaceService::isFaceSwitchOn(FaceService::TYPE_BIND)
            && $user_info['user_purpose'] == UserAccountEnum::ACCOUNT_INVESTMENT
            && $user_info['user_type'] != UserEnum::USER_TYPE_ENTERPRISE
        ) {
            $couponInfo = CouponService::getByUserId($user_info['id']);
            if (empty($couponInfo['refer_user_id']) && empty($couponInfo['invite_user_id'])) {
                $this->tpl->assign("page_title", "请下载网信普惠APP");
                $this->tpl->assign("content", app_conf("ID5_MAINTEN_MSG"));
                $this->template = "web/views/v2/account/maintain.html";
                $this->tpl->display("web/views/v2/account/maintain.html");
                return;
            }
        }

        // 如果企业用户访问添加银行界面，显示
        if ($user_info['user_type'] == '1')
        {
           return app_redirect(Url::gene('deal','promptCompany'));
        }

        $user_id = intval ( $GLOBALS['user_info']['id'] );
        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$user_id]) && Supervision::isServiceDown()) {
            return $this->show_tips(Supervision::maintainMessage(), '温馨提示', 0, '', '/');
        }

        $this->tpl->display("web/views/account/resetbank.html");
    }

}
