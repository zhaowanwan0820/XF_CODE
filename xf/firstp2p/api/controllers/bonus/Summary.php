<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class Summary extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            //'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'int'),
            'is_n' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    protected function getAppVersion($initVersion = 100) {
        return 410;
    }

    public function invoke() {
        $data = $this->form->data;
        //$loginUser = $this->getUserByToken();
        //if (empty($loginUser)) {
        //    $this->setErr('ERR_GET_USER_FAIL');
        //    return false;
        //}
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $site_id = $data['site_id'] ? $data['site_id'] : 1 ;
        $id = $data['id'];
        if ($data['is_n']) {
            $group = $this->rpc->local('WXBonusService\getBonusGroup', [$id]);
            $list = $this->rpc->local('WXBonusService\getBonusGroupGrabList', [$id]);
            $group['sendNum'] = count($list);
            $group['leftNum'] = $group['count'] - $group['sendNum'];
            $group['expired_at'] = $group['expireTime'];
            $group['expiredAt'] = date('Y-m-d H:i:s', $group['expireTime']);
            $group['createdAt'] = date('Y-m-d H:i:s', $group['createTime']);
            $group['bonusInfo'] = $group['info'];
        } else {
            $group = $this->rpc->local('BonusService\getGroupInfoById', array($id));
        }
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($group['user_id'])), 10);
        // 分享链接扩展信息
        $bonusTemplete = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getBonusTempleteBySiteId', array($site_id)), 10);
        if (!empty($bonusTemplete)) {
            $share_icon    = $bonusTemplete['share_icon'];
            $share_title   = $bonusTemplete['share_title'];
            $share_content = $bonusTemplete['share_content'];
        } else {
            $share_icon    = get_config_db('API_BONUS_SHARE_FACE',$site_id);
            $share_title   = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
            $share_content = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);
        }

        $face = urlencode($share_icon);
        $title = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $share_title));
        $content = str_replace('{$BONUS_TTL}', $group['count'], $share_content);
        //$face = urlencode(get_config_db('API_BONUS_SHARE_FACE',$site_id));
        //$title = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], get_config_db('API_BONUS_SHARE_TITLE', $site_id)));
        //$content = str_replace('{$BONUS_TTL}', $group['count'], get_config_db('API_BONUS_SHARE_CONTENT', $site_id));
        $content = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $content));
        $host = app_conf('API_BONUS_SHARE_HOST');

        $sendFlag = 0;
        if ($group['expired_at'] > time() && $group['leftNum'] > 0) {
            $sendFlag = 1;
        } elseif ($group['leftNum'] == 0) {
            $sendFlag = 2;
        }

        $bonusUrl = urlencode($host.'/hongbao/GetHongbao?sn='.$group['encrypt']); // web端提供
        $this->tpl->assign('url', $bonusUrl);
        $this->tpl->assign('face', $face);
        $this->tpl->assign('title', $title);
        $this->tpl->assign('content', $content);
        $this->tpl->assign('group', $group);
        $this->tpl->assign('sendFlag', $sendFlag);
        $this->tpl->assign('site_id', $site_id);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}
