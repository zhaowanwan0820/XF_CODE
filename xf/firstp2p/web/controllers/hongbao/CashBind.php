<?php
/**
 * CashBind.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\CashBase;
use core\service\BonusService;
use libs\utils\PaymentApi;

class CashBind extends CashBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $bonusService = new BonusService();
        $rebateRule = $bonusService->getBonusNewUserRebate('CASH_BONUS_RULE');
        $this->tpl->assign('money', intval($rebateRule['forNew']['money']));
        $this->tpl->assign("host", APP_HOST);
        $this->template = "web/views/hongbao/cash_coupon/index.html";

    }
}
