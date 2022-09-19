<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Site;

class GetBonusGroupInfo extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'load_id' => array('filter' => 'required'),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $loadId = intval($data['load_id']);
        $siteId = Site::getId();
        $groupInfo = $this->rpc->local('BonusService\get_bonus_group', array($loadId));
        if (empty($groupInfo) || $groupInfo['user_id'] != $loginUser['id']) {
            $this->json_data = array();
            return false;
        }
        $groupSn = $groupInfo['id_encrypt'];
        $bonusTtl = $groupInfo['count'];
        $bonusBidFinished = app_conf('API_BONUS_SHARE_BID_FINISHED');
        $host = get_config_db('API_BONUS_SHARE_HOST', $siteId);
        $bonusUrl = $host.'/hongbao/GetHongbao?sn='.$groupSn; // web端提供


        $bonusTemplete = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getBonusTempleteBySiteId', array($siteId)), 10);
        if (!empty($bonusTemplete)) {
            $share_icon    = $bonusTemplete['share_icon'];
            $share_title   = $bonusTemplete['share_title'];
            $share_content = $bonusTemplete['share_content'];
        } else {
            // 分享红包链接扩展信息
            $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($loginUser['id'])), 180);
            $bonusFace = get_config_db('API_BONUS_SHARE_FACE', $siteId);
            $bonusTitle = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], get_config_db('API_BONUS_SHARE_TITLE', $siteId));
            $bonusContent = str_replace('{$BONUS_TTL}', $bonusTtl, get_config_db('API_BONUS_SHARE_CONTENT', $siteId));
            $bonusContent = str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $bonusContent);
            $share_icon    = $bonusFace;
            $share_title   = $bonusTitle;
            $share_content = $bonusContent;
        }
        $this->json_data = array(
            'load_id' => $loadId,
            'bonus_ttl' => $bonusTtl,
            'bonus_url' => $bonusUrl,
            'bonus_face' => $share_icon,
            'bonus_title' => $share_title,
            'bonus_content' => $share_content,
            'bonus_bid_finished' => $bonusBidFinished,
        );
    }
}
