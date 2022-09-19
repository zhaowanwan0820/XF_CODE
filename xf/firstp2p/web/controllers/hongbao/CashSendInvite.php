<?php
/**
 * CashSendInvite.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\CashBase;
use core\service\UserService;
use libs\utils\Aes;
use core\service\UserTagService;
use core\dao\BonusConfModel;
use core\service\CouponService;
use core\service\BonusService;
use libs\utils\PaymentApi;

class CashSendInvite extends CashBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $this->tpl->assign("host", APP_HOST);
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($this->currentUserInfo['id'])), 10);
        $newCn = $senderUserCoupon['short_alias'];
        //TODO get rule
        $bonusService = new BonusService();
        if ($bonusService->isCashBonusSender($this->currentUserInfo['id'])) {
            $shareContent = get_config_db('COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS', $site_id);
            $shareContent = str_replace('{$COUPON}', $newCn, $shareContent);
            $linkUrl = 'http://' .APP_HOST. '/hongbao/CashGet?cn=' .$newCn. '&site_id=' . $this->site_id;
            $this->tpl->assign('linkUrl', $linkUrl);
            $this->tpl->assign('desc', $shareContent);
            $this->template = "web/views/hongbao/cash_coupon/invite_friends.html";
        } else {
            $tips = BonusConfModel::get('CASH_SEND_LIMIT_TIPS');
            $this->tpl->assign('tips', $tips);
            $this->template = "web/views/hongbao/cash_coupon/invite_friends_mismatch.html";
        }
    }
}
