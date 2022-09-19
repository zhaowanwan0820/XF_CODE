<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class AjaxSend extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int'),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = $data['site_id'] ? $data['site_id'] : 1 ;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $page = isset($data['page']) ? $data['page'] : 1;
        $pageSize = 10;

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

        $this->json_data = array(
            'count' => $groupList['count'],
            'list' => $list,
            'face' => $face,
            'title' => $title,
        );
    }

}
