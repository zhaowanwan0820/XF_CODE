<?php

namespace web\controllers\activity;

use libs\web\Form;
use libs\web\Url;
use libs\utils\Aes;
use web\controllers\BaseAction;
use libs\utils\Logger;


/**
 * 新首页PC一入口
 */
class NewUserPage extends BaseAction {

    public function invoke()
    {
        $switch = $this->rpc->local('NewUserPageService\isNewUserSwitchOpen');
        if ($switch == 0) {
            $this->show_error("当前页面已关闭");
        }
        $userInfo = $GLOBALS['user_info'];
        if (empty($userInfo)) {
            $uid = false;
        } else {
            $uid = intval($userInfo['id']);
        }

        $siteId = $this->getSiteId();

        // 获取当日注册用户数
        $registerCount = $this->rpc->local('UserService\getCountByDay', array(date("Y-m-d")));

        // 当日投资总用户数
        $loadUserCount = $this->rpc->local('DealLoadService\getLoadUsersNumByTime', array());

        // 获取用户进度
        $userStatus = $this->rpc->local('NewUserPageService\getNewUserProgress', array($uid));

        // 可投资标的列表
        $newUserDealsList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('NewUserPageService\getNewUserDeals', array($siteId)), 30);

        // 邀请码
        $inviteCode = isset($this->appInfo['inviteCode']) ? $this->appInfo['inviteCode'] : "";

        $dealsList = array();
        foreach ($newUserDealsList as $key => $value) {
            $dealsList[$key]['id'] = $value['id'];
            $dealsList[$key]['name'] = $value['name'];
            $dealsList[$key]['repayTime'] = $value['repay_time'];
            $dealsList[$key]['loanType'] = $value['loantype'];
            $dealsList[$key]['rate'] = number_format($value['rate'], 2);
            $dealsList[$key]['url'] = Url::gene("d", "", Aes::encryptForDeal($value['id']), true);
            $dealsList[$key]['deal_type'] = $value['deal_type'];
        }

        // 图片列表
        $imgList = $this->rpc->local('NewUserPageService\getPageInfoByInviteCode', array($inviteCode));

        // 投资列表
        $loadList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealLoadService\getLastLoadList', array(30)), 60);

        $list = array();
        foreach ($loadList as $key => $value) {
            $list[$key]['mobile'] = substr_replace($value['mobile'], str_repeat("*", 6), -8, -2);
            $list[$key]['money'] = number_format($value['loan_money'], 2, '.', ',');
        }


        $domain = get_domain();
        if (empty($domain)) {
            $domain = app_conf("API_BONUS_SHARE_HOST");
        }

        if ($uid) {
            // 生成短码
            $first_coupon = $this->rpc->local('CouponService\getUserCoupon', array($uid));
        } else {
            $first_coupon = array('short_alias' => '');
        }

        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        $share_url = !empty($first_coupon) ? $domain . '/user/register?cn=' . $first_coupon['short_alias'] : "";

        if (!$this->is_wxlc && !$this->is_firstp2p) { //分站
            $share_msg = "100元开启财富之旅！历史平均年化收益8％~12％，0手续费，期限灵活。注册首投就送送送！";
            $share_msg .= "任务勋章、投资券、加息券等，你想要的玩法全都有！直接上红包，红包密码{COUPON}。";
            $share_msg .= "{DOMAIN}/user/register?cn={COUPON}";
            $share_msg = str_replace(array('{COUPON}', '{DOMAIN}'), array($first_coupon['short_alias'], $domain), $share_msg);
        } else {
            $share_msg = get_config_db("COUPON_WEB_ACCOUNT_COUPON_PAGE_SHAREMSG", $site_id);
        }


        Logger::info(implode(' | ', array(__CLASS__, APP, json_encode(array('pageId' => $imgList['id'], 'inviteCode' => $inviteCode, 'userId' => $uid)))));

        $this->tpl->assign('uid', $uid);

        $this->tpl->assign('loadList', json_encode($list));

        $this->tpl->assign('share_url', $share_url);
        $this->tpl->assign('share_msg', str_replace('{$COUPON}', $first_coupon['short_alias'], $share_msg));
        $this->tpl->assign('switch', $this->rpc->local('NewUserPageService\isNewUserSwitchOpen', array())); //新手专区的开关
        $this->tpl->assign('userStatus', $userStatus); //用户的进度状态
        $this->tpl->assign('registerCount', $registerCount); //当日注册总用户数
        $this->tpl->assign('newUserDealsList', $dealsList); //可投资列表
        $this->tpl->assign('imgList', $imgList); //配置图片列表
        $this->tpl->assign('loadUserCount', $loadUserCount); //当日投资总用户数

        if (!$this->is_wxlc && !$this->is_firstp2p) { //分站
            $this->template = 'web/views/v3/activity/new_user_page_fz.html';
        }
        if ($this->is_wxlc) {
            $this->template = 'web/views/v3/activity/new_user_page_2018_v1.html';
        }
    }
}
