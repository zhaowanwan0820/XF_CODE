<?php
/**
 * GetHongbao.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\YxHongbao;
use libs\weixin\Weixin;
use core\service\BonusService;
use libs\utils\PaymentApi;
use core\service\BonusBindService;

class YxHongbaoGet extends YxHongbao {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $bonusService = new BonusService();
        $bonusBindService = new BonusBindService();
        // 当前福利绑定的手机号存在，直接展示已领取页面
        if ($result = $bonusService->getBonusByOpenid($this->sn, $this->openid)) {
            $this->tpl->assign('mobile', $this->mobile);
            //$this->template = 'web/views/hongbao/yx/yilinghongbao.html';
            $this->tpl->assign('only_new_user', '此微信账号已领过福利。您可发贺卡福利，领用人投资后，您再获10元奖励福利。');
            //$this->template = "web/views/hongbao/yilinghongbao.html";
            $this->template = "web/views/hongbao/yx/qianghongbao.html";
            return false;
        }

        $bindInfo = $bonusBindService->getBindInfoByOpenid($this->openid);
        if (!$bindInfo->mobile) {
            $bonusBindService->bindUser($this->openid, $this->mobile);
        }

        // 福利已领完
        //if (count($bonusUserList) >= $bonusInfo['count']) {
        if ($bonusService->getCurrentBonusCount($this->sn) >= $this->bonusGroupInfo['count']) {
            $this->tpl->assign('mobile', $this->mobile);
            $this->template = 'web/views/hongbao/yx/hongbaoyiqiangwan.html';
            return false;
        }

        // 领取福利
        if ($result = $bonusService->collection($this->sn, $this->mobile, $this->openid, $this->referMobile)) {
            $this->bonusDetail = $result;
        } else {
            $data = array($this->sn, $this->openid, $this->mobile, $result);
            PaymentApi::log("HongbaoCollectionError." . json_encode($data, JSON_UNESCAPED_UNICODE));
            $this->show_error('福利疯抢中 稍后再试吧', '', 0 , 1);
            return false;
        }

        $this->tpl->assign('bonusDetail', $this->bonusDetail);
        $this->tpl->assign('mobile', $this->mobile);
        $this->tpl->assign('date', date("m-d H:i", time()));
        $this->tpl->assign("host", APP_HOST);
        $totalMoney = $bonusService->getUserSumMoney(array('mobile' => $this->mobile, 'status' => 1));
        $this->tpl->assign('totalMoney', $totalMoney);
        $this->tpl->assign('userInfo', $this->wxInfo['user_info']);
        if ($this->bonusDetail['status'] == 3 || $this->bonusDetail['status'] == 4) {
            $this->tpl->assign('only_new_user', '老用户不可领迎新贺卡福利。您可发贺卡福利，领用人投资后，您再获10元奖励福利。');
            $this->template = "web/views/hongbao/yx/qianghongbao.html";
        } elseif ($this->bonusDetail['status'] == 2) {
            $this->tpl->assign('only_new_user', '您已领过福利。您可发贺卡福利，领用人投资后，您再获10元奖励福利。');
            //$this->template = "web/views/hongbao/yilinghongbao.html";
            $this->template = "web/views/hongbao/yx/qianghongbao.html";
        } else {
            $this->tpl->assign("shareUrl", 'http://' .APP_HOST. '/hongbao/YxHongbaoSend?sn=' .urlencode($this->sn). '&mobile='.$this->mobile. '&referUsn=' .urlencode($this->referUsn));
            $this->template = "web/views/hongbao/yx/qiangdaohongbao.html";
        }
    }
}
