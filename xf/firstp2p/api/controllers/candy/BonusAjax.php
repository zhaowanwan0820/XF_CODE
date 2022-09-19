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
use core\service\UserService;


class BonusAjax extends AppBaseAction
{

    // const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int'),
            'pageSize' => array('filter' => 'int'),
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

        $page = isset($data['page']) ? $data['page'] : 1;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;

        if ($page == 1) {

            if ((new CandyActivityService())->inBonus($loginUser['id'])) {
                // 判断用户是否有投资
                if ((new UserService())->hasLoan($loginUser['id'])) {
                    (new WXBonusService)->acquireXinLi($loginUser['id'], date('Y-m-d'));
                }
            }

        }

        $groupList = (new WXBonusService)->getXinLiList($loginUser['id'], $page, $pageSize);
        $totalPage = $groupList['count'];

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

        $share_icon = BonusConfModel::get('XINLI_SHARE_ICON') ?: get_config_db('API_BONUS_SHARE_FACE',$site_id);
        $share_title = BonusConfModel::get('XINLI_SHARE_TITLE') ?: get_config_db('API_BONUS_SHARE_TITLE', $site_id);
        $share_content = BonusConfModel::get('XINLI_SHARE_CONTENT') ?: get_config_db('API_BONUS_SHARE_CONTENT', $site_id);

        foreach ($groupList['list'] as $item) {
            $tmp = array();
            $tmp['id'] = $item['id'];
            $tmp['createdAt'] = format_date($item['created_at'], 'Y-m-d H:i:s');
            $tmp['expiredAt'] = format_date($item['expired_at'] - 1, 'Y-m-d H:i:s');
            $tmp['count'] = intval($item['count']);
            $tmp['usedNum'] = isset($item['use_num']) ? intval($item['use_num']) : 0;
            $tmp['sendNum'] = intval($item['send_num']);
            $tmp['leftNum'] = intval($item['count'] - $item['send_num']);
            if ($tmp['leftNum'] <= 0) { // 发光了
                $tmp['flag'] = 0;
            } elseif ($item['expired_at'] < $time) { // 过期
                $tmp['flag'] = 2;
            } else { // 可以发
                $tmp['flag'] = 1;
                    $tmp['url'] = urlencode($host . $item['link'] . '?sn='.$item['sn'] . '&cn=' . $senderUserCoupon['short_alias']); // web端提供
                $tmp['shareContent'] = str_replace('{$BONUS_TTL}', $item['count'], $share_content);
                $tmp['shareContent'] = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $tmp['shareContent']));
            }
            $list[] = $tmp;
        }
        // 分享链接扩展信息
        $face = urlencode($share_icon);
        $title = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $share_title));

        $this->json_data = [
            'list' => $list,
            'face' => $face,
            'title' => $title,
            'count' => $groupList['count'],
            'totalPage' => ceil($groupList['count'] / $pageSize),
            'page' => $page,
            'pageSize' => $pageSize,
        ];

    }

}
