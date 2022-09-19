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

class RemoveBankcard extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        session_start();
        $_SESSION['resetbank'] = 1;
        //身份认证维护页
        if (intval(app_conf("ID5_VALID")) === 3) {
            $this->tpl->assign("page_title", "系统维护中");
            $this->tpl->assign("content", app_conf("ID5_MAINTEN_MSG"));
            $this->template = "web/views/v2/account/maintain.html";
            $this->tpl->display("web/views/v2/account/maintain.html");
            return;
        }
        $user_info = $GLOBALS ['user_info'];
        // 如果企业用户访问添加银行界面，显示
        if ($user_info['user_type'] == '1')
        {
           return app_redirect(Url::gene('deal','promptCompany'));
        }

        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            return $this->show_tips(\libs\utils\PaymentApi::maintainMessage(), '温馨提示', 0, '', '/');
        }

        $user_id = intval ( $GLOBALS['user_info']['id'] );
        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$user_id]) && Supervision::isServiceDown()) {
            return $this->show_tips(Supervision::maintainMessage(), '温馨提示', 0, '', '/');
        }

        $this->tpl->display("web/views/v3/account/resetbank.html");
    }

}
