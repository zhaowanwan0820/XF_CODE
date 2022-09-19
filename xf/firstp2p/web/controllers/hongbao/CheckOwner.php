<?php
/**
 * CheckOwner.php
 *
 * @date 2015-06-15
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\HongbaoBase;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;

class CheckOwner extends HongbaoBase {

    public function init() {
        $this->action = $this->getCurrentUrl();
        $this->form = new Form("get");
        $this->form->rules = array(
            "sn" => array("filter" => "required", "message" => "参数错误"),
            "is_n" => array("filter" => "required", "message" => "参数错误"),
            "source" => array("filter" => "string", "option" => array("optional" => true)),
            "site_id" => array("filter" => "int", "option" => array("optional" => true)),
        );

        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }
    }

    public function invoke() {

        $sn = $this->form->data['sn'];
        $isNew = $this->form->data['is_n'];
        $site_id = intval($this->form->data['site_id']) ? intval($this->form->data['site_id']) : 1;
        $source = $this->form->data['source'];
        // 获取jsapi签名
        $this->getJsApiSignature();

        if ($isNew) {
            $id = $this->rpc->local('WXBonusService\snDecrypt', [$sn]);
            $bonusInfo = $this->rpc->local('WXBonusService\getBonusGroup', [$id]);
            $bonusInfo['user_id'] = $bonusInfo['userId'];
        } else {
            $bonusInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\get_group_info_by_sn', array($sn)), 10);
        }
        $this->bonusTemplete = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getBonusTempleteBySiteId', array($site_id)), 10);
        if ($this->bonusTemplete) {
            $this->tpl->assign("bg_image", $this->bonusTemplete['bg_image']);
        }
        if (!$bonusInfo) {
            PaymentApi::log("HongbaoGetGroupInfoError" .$sn. json_encode($bonusInfo, JSON_UNESCAPED_UNICODE));
            $this->show_error('福利不存在');
            return false;
        }

        $siteName = \libs\utils\Site::getTitleById($site_id);
        $siteName = $siteName ? $siteName : '网信理财';
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($bonusInfo['user_id'])), 10);
        $this->tpl->assign('coupon', $senderUserCoupon);
        // 根据各分站配置读取对应的h5下载链接
        if ($site_id != 1 && get_config_db('APP_DOWNLOAD_H5_URL', $site_id)) {
            $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $site_id);
            $downloadDesc = '下载客户端';
        } else {
            $downloadUrl = 'http://app.firstp2p.com/?referrer_token=weixin&cn=' . $senderUserCoupon['short_alias'];
            //$downloadUrl = 'http://m.firstp2p.com/?from_platform=hongbao_touzi&cn=' . $senderUserCoupon['short_alias'];
            $downloadDesc = '下载客户端';
        }
        $this->tpl->assign('site_id', $site_id);
        $this->tpl->assign('siteName', $siteName);
        $this->tpl->assign('downloadUrl', $downloadUrl);
        $this->tpl->assign('downloadDesc', $downloadDesc);

        // 验证是否是当前用户
        $wxCache = $this->getCookie(self::USER_WEIXIN_INFO);
        if (!$wxCache) {
            $this->template = "web/views/hongbao/notallowed.html";
            return false;
        }

        $openid = $wxCache['openid'];
        // 获取绑定的手机号
        $bindInfo = $this->rpc->local('BonusBindService\getBindInfoByOpenid', array($openid));
        if ($bindInfo) {
            $mobile = $bindInfo->mobile;
        }
        $mobile = $mobile ? $mobile : $this->getCookie(self::USER_MOBILE_KEY);
        if (!$mobile) {
            $this->template = "web/views/hongbao/notallowed.html";
            return false;
        }

        $ownerInfo = $this->rpc->local('UserService\getUser', array($bonusInfo['user_id']));

        if ($ownerInfo['mobile'] !== $mobile) {
            //TODO
            $this->template = "web/views/hongbao/notallowed.html";
            return false;
        }
        if ($isNew) {
            header('Location:http://' . APP_HOST . '/hongbao/grab?sn=' . $sn. '&site_id=' . $site_id . '&source=' .$source);
        } else {
            header('Location:http://' . APP_HOST . '/hongbao/GetHongbao?sn=' . $sn. '&site_id=' . $site_id . '&source=' .$source);
        }
        return true;
    }
}
