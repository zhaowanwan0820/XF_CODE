<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\BonusService;
use core\service\bonus\BonusUser;

class Send extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'wxb' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = isset($data['site_id']) ? $data['site_id'] : 1 ;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $showWxb = true;//isset($data['wxb']) && $data['wxb'] == 'true' ? true : false ;

        $page = 1;
        $pageSize = 10;

        if ($this->app_version >= 320) {
            $sendResult = (new BonusUser())->getUserByUid($loginUser['id']);
            $this->tpl->assign('by_used_count', $sendResult['by_used_count']);
            $this->tpl->assign('by_get_count', $sendResult['by_get_count']);
        }

        $groupList = $this->rpc->local('BonusService\get_group_list', array($loginUser['id'], true, $page, $pageSize));
        $list = array();
        $time = time();
        $host = app_conf('API_BONUS_SHARE_HOST');
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($loginUser['id'])), 10);
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

        foreach ($groupList['list'] as $item) {
            $tmp = array();
            $tmp['id'] = $item['id'];
            $tmp['isNew'] = $item['isNew'] ?: 0;
            $tmp['createdAt'] = format_date($item['created_at'], 'Y-m-d H:i:s');
            $tmp['expiredAt'] = format_date($item['expired_at'] - 1, 'Y-m-d H:i:s');
            $tmp['loanId'] = $item['deal_load_id'];
            $tmp['count'] = $item['count'];
            $tmp['usedNum'] = $item['use_num'];
            $tmp['sendNum'] = $item['send_num'];
            $tmp['leftNum'] = $item['count'] - $item['send_num'];
            if ($tmp['leftNum'] <= 0) { // 发光了
                $tmp['flag'] = 0;
            } elseif ($item['expired_at'] < $time) { // 过期
                $tmp['flag'] = 2;
            } else { // 可以发
                $tmp['flag'] = 1;
                if (isset($item['link'])) {
                    $tmp['url'] = urlencode($host . $item['link'] . '?sn='.$item['sn']); // web端提供
                } else {
                    $tmp['url'] = urlencode($host.'/hongbao/GetHongbao?sn='.$item['id_encrypt']); // web端提供
                }
                $tmp['shareContent'] = str_replace('{$BONUS_TTL}', $item['count'], $share_content);
                //$tmp['shareContent'] = str_replace('{$BONUS_TTL}', $item['count'], get_config_db('API_BONUS_SHARE_CONTENT', $site_id));
                $tmp['shareContent'] = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $tmp['shareContent']));
            }
            $list[] = $tmp;
        }
        // 分享链接扩展信息
        $face = urlencode($share_icon);
        $title = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $share_title));
        //$face = urlencode(get_config_db('API_BONUS_SHARE_FACE',$site_id));
        //$title = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], get_config_db('API_BONUS_SHARE_TITLE', $site_id)));

        $this->tpl->assign('list', $list);
        $this->tpl->assign('face', $face);
        $this->tpl->assign('title', $title);
        $this->tpl->assign('count', $groupList['count']);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('site_id', $site_id);
        //拜年红包逻辑
        $happy_new_year_status = 0;
        $hny_group = app_conf('BONUS_HAPPY_NEW_YEAR');
        $hny_group_id = $this->rpc->local('BonusService\encrypt', array($hny_group));
        if ($hny_group_id > 0 && $site_id == 1) {
            $hny_active = $this->rpc->local('BonusService\getActivityByGroupId', array($hny_group_id));
            if ($hny_active) {
                $happy_new_year_status = 1;
                $this->tpl->assign('hny_title', $hny_active['subject']);
                $this->tpl->assign('hny_share_content',$hny_active['desc']);
                $static_host = app_conf('STATIC_HOST');
                $this->tpl->assign('hny_face', ((substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/'.$hny_active['icon']));
                $mobile = \libs\utils\Aes::encode($GLOBALS['user_info']['mobile'], base64_decode(BonusService::HONGBAO_AES_KEY));
                $url = sprintf('%s/hongbao/YxHongbaoBind?referUsn=%s&sn=%s&site_id=%s', app_conf('API_BONUS_SHARE_HOST'), urlencode($mobile), urlencode($hny_group), $site_id);
                $this->tpl->assign('hny_url', urlencode($url));
            }
        }
        $this->tpl->assign('happy_new_year_status', $happy_new_year_status);
        //-------------------------------------现金红包START------------------------
        if ($this->rpc->local('BonusService\isCashBonusSender', array($loginUser['id'], $site_id))) {
            $this->tpl->assign('cash_bonus_title', get_config_db('CASH_BONUS_SHARE_TITLE'));
            $this->tpl->assign('cash_bonus_content', str_replace('{$COUPON}', $senderUserCoupon['short_alias'], get_config_db('COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS')));
            $url = sprintf('%s/hongbao/CashGet?cn=%s&site_id=%s', app_conf('API_BONUS_SHARE_HOST'), $senderUserCoupon['short_alias'], $site_id);
            $this->tpl->assign('cash_bonus_url', urlencode($url));
            $this->tpl->assign('cash_bonus_face', get_config_db('CASH_BONUS_SHARE_FACE'));
            $this->tpl->assign('cash_bonus_status', 1);
        }
        //-------------------------------------现金红包 END-------------------------

        // 增加app H5 买红包的入口配置
        $showBuyBtnConf = intval(app_conf('APP_SHOW_BUY_BTN'));
        $showBuyBtn = !empty($showBuyBtnConf)?1:0;
        $this->tpl->assign('show_btn', $showBuyBtn);

        if ($showWxb) {
            $this->template = $this->getTemplate('send_new');
        }
    }

    public function _after_invoke() {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }
}
