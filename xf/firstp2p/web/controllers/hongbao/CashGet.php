<?php
/**
 * GetHongbao.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\CashBase;
use libs\weixin\Weixin;
use core\service\BonusService;
use libs\utils\PaymentApi;
use core\service\BonusBindService;
use core\service\CouponService;

class CashGet extends CashBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $bonusService = new BonusService();
        $bonusBindService = new BonusBindService();

        // 领取福利
        $result = $bonusService->sendCashBonus($this->mobile, $this->referUid, 'CASH_BONUS_RULE', $this->replace);
        if ($result) {
            $this->bonusDetail = $result;
        } else {
            $data = array($this->cn, $this->mobile, $result);
            $this->show_error('福利疯抢中 稍后再试吧', '', 0 , 1);
            return false;
        }

        $this->tpl->assign('bonusDetail', $this->bonusDetail);
        $this->tpl->assign('date', date("m-d H:i", time()));
        $this->tpl->assign("host", APP_HOST);
        if ($this->bonusDetail['status'] == 3) {
            return $this->show_error('没有返利规则');
        } else if ($this->bonusDetail['status'] == 4) {
            $bonus = $this->bonusDetail['bonus'];
            $hongbaoText = app_conf('NEW_BONUS_TITLE');
            return $this->show_error("您领过“{$bonus['name']}”{$hongbaoText}，不能领当前{$hongbaoText}了");
        } else if ($this->bonusDetail['status'] == 2) {
            $this->tpl->assign('hasGet', 1);
            $this->tpl->assign('money', intval($this->bonusDetail['money']));
            \es_session::set(self::MOBILE_SESSION_KEY, $this->mobile);
            $this->template = "web/views/hongbao/cash_coupon/deposit_account.html";
        } else {
            $this->tpl->assign('hasGet', 0);
            $this->tpl->assign('money', intval($this->bonusDetail['money']));
            $this->template = "web/views/hongbao/cash_coupon/deposit_account.html";
        }
    }
}
