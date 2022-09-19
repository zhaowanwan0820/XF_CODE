<?php
/**
 * BindMobile.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\BonusService;
use libs\weixin\Weixin;

class Share extends HongbaoBase{

    public function init() {
        $this->form = new Form("get");
        $this->form->rules = array(
            "sn" => array("filter" => "required", "message" => "参数错误"),
            "site_id" => array("filter" => "int", "option" => array("optional" => true)),
        );
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }
    }

    public function invoke() {
        $sn = $this->form->data['sn'];
        $site_id = $this->form->data['site_id'];
        $site_id = $site_id ? $site_id : 1;
        $bonusService = new BonusService();
        $this->bonusGroupInfo = $bonusInfo = $bonusService->get_group_info_by_sn($sn);
        $this->bonusTemplete = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getBonusTempleteBySiteId', array($site_id)), 10);

        if (!$bonusInfo) {
            $this->show_error('福利不存在', '', 0 , 1);
            return false;
        }

        // 福利已过期
        if ($bonusInfo['expired_at'] <= time()) {
            $this->show_error('福利已过期', '', 0 , 1);
            return false;
        }

        // 获取jsapi签名
        $this->getJsApiSignature();

        /// 福利已领完
        list($collection_count, $bonusUserList) = array_values($bonusService->get_list_by_sn($sn, BonusService::SCOPE_BIND));
        //if (!empty($bonusUserList) && count($bonusUserList) >= $bonusInfo['count']) {
        if (!empty($bonusUserList) && $collection_count >= $bonusInfo['count']) {
            $this->show_error('福利已被领完', '', 0 , 1);
            return false;
        }
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($bonusInfo['user_id'])), 10);

        $linkUrl = 'http://' .APP_HOST. '/hongbao/GetHongbao?sn=' .urlencode($this->sn). '&site_id=' . $site_id;
        //活动
        if ($this->bonusGroupInfo['active_config']) {
            $title = $this->bonusGroupInfo['active_config']['name'];
            $shareContent = $this->bonusGroupInfo['active_config']['desc'];
            $img = $this->bonusGroupInfo['active_config']['icon'];
        } else {
            if ($this->bonusTemplete) {
                $img          = $this->bonusTemplete['share_icon'];
                $title        = $this->bonusTemplete['share_title'];
                $shareContent = $this->bonusTemplete['share_content'];

            } else {
                $shareContent = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);
                $title = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
                $img = get_config_db('API_BONUS_SHARE_FACE', $site_id);
            }
        }
        $patterns = array('/\{\$BONUS_TTL\}/', '/\{\$COUPON\}/');
        $replaces = array($bonusInfo['count'], $senderUserCoupon['short_alias']);
        $shareContent = preg_replace($patterns, $replaces, $shareContent);
        $title = preg_replace($patterns, $replaces, $title);
        $this->tpl->assign('bonusSiteLogo', $bonusSiteLogo);
        $this->tpl->assign('img', $img);
        $this->tpl->assign('title', $title);
        $this->tpl->assign('linkUrl', $linkUrl);
        $this->tpl->assign('desc', $shareContent);

        $this->tpl->assign("host", get_config_db('API_BONUS_SHARE_HOST', $site_id));
        $this->tpl->assign("sn", $sn);
        $this->tpl->assign("site_id", $site_id);
        $this->template = "web/views/hongbao/share.html";

    }
}
