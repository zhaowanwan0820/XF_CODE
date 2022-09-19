<?php
/**
 * CashRegisterSuccess.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use web\controllers\hongbao\CashBase;
use core\service\BonusService;
use core\service\UserTagService;

class CashRegisterSuccess extends CashBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $bonusService = new BonusService();
        $result = $bonusService->bind($this->currentUserInfo['id'], $this->mobile);
        $userTagService = new UserTagService();
        $userTagService->addUserTagsByConstName($this->currentUserInfo['id'], 'XJHB_2');
        $this->tpl->assign("host", APP_HOST);
        $this->template = "web/views/hongbao/cash_coupon/register_success.html";

    }
}
