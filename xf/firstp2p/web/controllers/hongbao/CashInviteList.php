<?php
/**
 * CashInviteList.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\CashBase;
use core\service\BonusService;
use core\dao\BonusModel;

class CashInviteList extends CashBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $bonusService = new BonusService();
        if (!$this->referUid) {
            return false;
        }
        // 获取最新的20条记录
        $bonusUserList = $bonusService->get_list_by_type(BonusModel::BONUS_CASH_FOR_INVITE, $this->currentUserInfo['id'], '', BonusService::SCOPE_ALL, '*', 1, 20, 'DESC');
        $this->listView = $this->makeListToView($bonusUserList);
        $sumMoney = $bonusService->getUserSumMoney(array('userId' => $this->currentUserInfo['id'], 'type' => BonusModel::BONUS_CASH_FOR_INVITE), false);

        $this->tpl->assign('bonusList', $this->listView);
        $this->tpl->assign('sum', $sumMoney);
        $this->template = "web/views/hongbao/cash_coupon/list_invite.html";

    }
}
