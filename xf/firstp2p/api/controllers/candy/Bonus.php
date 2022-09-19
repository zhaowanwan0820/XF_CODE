<?php

namespace api\controllers\candy;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\BonusService;
use core\service\bonus\BonusUser;
use core\service\WXBonusService;
use core\dao\BonusConfModel;
use core\service\candy\CandyActivityService;

class Bonus extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        /*
        if ((new CandyActivityService())->inBonus($loginUser['id'])) {
            (new WXBonusService)->acquireXinLi($loginUser['id'], date('Y-m-d'));
        }

        $page = 1;
        $pageSize = 10;

        $groupList = (new WXBonusService)->getXinLiList($loginUser['id'], $page, $pageSize);

        // $groupList = $this->rpc->local('BonusService\get_group_list', array($loginUser['id'], true, $page, $pageSize));
        $list = array();
        $time = time();
        $isXinLiWeixinOpen = BonusConfModel::get('XINLI_WEIXIN_SWITCH');
        if ($isXinLiWeixinOpen) {
            $host = BonusConfModel::get('XINLI_SHARE_HOST');
        } else {

            $userList = BonusConfModel::get('XINLI_USER_WHITELIST');
            $userList = explode(',', $userList);
            if (in_array($loginUser['id'], $userList)) {

                $host = BonusConfModel::get('XINLI_SHARE_HOST');

            } else {

                $host = app_conf('API_BONUS_SHARE_HOST');
            }
        }
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($loginUser['id'])), 10);
        // $bonusTemplete = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getBonusTempleteBySiteId', array($site_id)), 10);

        $share_icon = BonusConfModel::get('XINLI_SHARE_ICON') ?: get_config_db('API_BONUS_SHARE_FACE',$site_id);
        $share_title = BonusConfModel::get('XINLI_SHARE_TITLE') ?: get_config_db('API_BONUS_SHARE_TITLE', $site_id);
        $share_content = BonusConfModel::get('XINLI_SHARE_CONTENT') ?: get_config_db('API_BONUS_SHARE_CONTENT', $site_id);

        // $share_icon    = get_config_db('API_BONUS_SHARE_FACE',$site_id);
        // $share_title   = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
        // $share_content = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);

        // $share_icon    = get_config_db('API_BONUS_SHARE_FACE',$site_id);
        // $share_title   = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
        // $share_content = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);

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
        $this->tpl->assign('site_id', $site_id);
        */

        $share_icon = BonusConfModel::get('XINLI_SHARE_ICON') ?: get_config_db('API_BONUS_SHARE_FACE',$site_id);
        $share_title = BonusConfModel::get('XINLI_SHARE_TITLE') ?: get_config_db('API_BONUS_SHARE_TITLE', $site_id);
        // $share_content = BonusConfModel::get('XINLI_SHARE_CONTENT') ?: get_config_db('API_BONUS_SHARE_CONTENT', $site_id);
        $face = urlencode($share_icon);
        $this->tpl->assign('face', $face);
        $this->tpl->assign('title', $share_title);

        $this->tpl->assign('token', $data['token']);
    }

    public function _after_invoke() {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }
}
