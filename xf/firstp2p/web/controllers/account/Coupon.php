<?php
/**
 * 我的邀请码
 * @author 曹龙<caolong@ucfgroup.com>
 **/

namespace web\controllers\account;

use core\service\CouponLogService;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\CouponService;
use core\service\CouponBindService;
use core\service\OpenService;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

/**
 * 我的邀请码
 * @author <caolong@ucfgroup.com>
 **/
class Coupon extends BaseAction {

    /**
     * 页面缓存时间
     * @var int
     */
    private static $_cache_time = 40;

    public function init() {
        if (!$this->check_login()) return false;
    }

    public function invoke() {
        //若为分站，是否可用优惠券
        if (!isCouponValidForBranchSite()) {
            return app_redirect(url("index"));
        }
        $user_id = intval($GLOBALS['user_info']['id']);
        if (empty($user_id)) {
            return app_redirect(url("index"));
        }
        $this->tpl->assign('user_id', $user_id);
        $this->tpl->assign('domain', get_domain());

        $is_used_code = $this->rpc->local('CouponService\isCouponUsed', array($user_id));
        //$is_used_code = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\isCouponUsed',array($user_id)),self::$_cache_time);
        //没有通过身份认证   并且没有使用过
        if ((!$GLOBALS['user_info']['real_name'] || $GLOBALS['user_info']['idcardpassed'] != 1) && !$is_used_code) {
            $this->tpl->assign('is_not_code', true);
        } else {
            $this->tpl->assign('is_not_code', false);
        }
        // 生成短码
        $coupon = $this->rpc->local('CouponService\getUserCoupon', array($user_id));
        // 已获总待返和已返
        $couponLogService  = new CouponLogService(CouponLogService::MODULE_TYPE_P2P,CouponLogService::DATA_TYPE_INVITE);
        $rotalRefererRebateAmount = $couponLogService->getTotalRefererRebateAmount($user_id);

        $totalInviteNumber = $this->rpc->local("CouponLogService\getTotalInviteNumber",array($user_id));

        $isBigUser =  $this->rpc->local("CouponLogService\getCountUser",array($user_id));

        //特殊用户组
        $this->tpl->assign('coupon', $coupon);
        $this->tpl->assign('rotalRefererRebateAmount',$rotalRefererRebateAmount);
        $this->tpl->assign('totalInviteNumber',$totalInviteNumber);
        $this->tpl->assign('isBigUser', $isBigUser);

        $this->tpl->assign('select_content', "手机号");
        // O2O
        $isO2O = false;
        if ($this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_HY_USER', $user_id))
            || $this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_SELLER', $user_id))) {
            $isO2O = true;
        }
        $this->tpl->assign("isO2O", $isO2O);

        $gameadvliststr =  $this->rpc->local('AdvService\getAdv', array("邀请返利游戏邀请入口_2015"));
        $gameadv = json_decode($gameadvliststr, true);
        if (!is_string(@$gameadv["title"])) {
            $gameadvliststr = "null";
        }
        $this->tpl->assign("gameadvlist", htmlspecialchars_decode($gameadvliststr));

        //现金红包活动分享
        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        // 返利文案
        $referer_rebate_info = $this->rpc->local('CouponService\getRebateInfo', array($user_id));
        if($referer_rebate_info["rebate_effect_days"] || !$referer_rebate_info["basic_group_id"])  {
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG",$site_id);
        }else{
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG_NO_LIMIT",$site_id);
        }
        $showcashbonus = false;
        if ($this->rpc->local('BonusService\isCashBonusSender', array($user_id))) {//现金红包分享
            $share_url = app_conf('API_BONUS_SHARE_HOST') . '/hongbao/CashGet?cn=' . $coupon['short_alias'];
            $share_url_escape = $share_url;
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
            $showcashbonus = true;
        } else {
            //根据分站来显示，邀请码链接
            $domain = get_domain();
            if (empty($domain)){
                $domain = app_conf("API_BONUS_SHARE_HOST");
            }

            if (!$this->is_wxlc && !$this->is_firstp2p) { //分站
                $share_msg  = "100元开启财富之旅！历史平均年化收益8％~12％，0手续费，期限灵活。注册首投就送送送！";
                $share_msg .= "任务勋章、投资券、加息券等，你想要的玩法全都有！直接上红包，红包密码{COUPON}。";
                $site_id = $this->appInfo['id'];

                //判断分站是否配置自己的分享链接
                $setParams = (array) json_decode($this->appInfo['setParams'], true);
                if(!empty($setParams['myInviteUrl'])) {
                    $euid = $this->getEuid($user_id);
                    $share_url = $setParams['myInviteUrl'];
                    $share_url = $share_url . '&device=web&euid=' . $euid;
                    $share_url_escape = str_replace('&', '%26', $share_url);
                }else {
                    if ($this->showSiteCoupon()) { //显示分站邀请码
                        $euid = $this->getEuid($user_id);
                        $this->tpl->assign('site_invite_code', $this->appInfo['inviteCode']);
                        $share_url = $domain . '/?cn=' . $this->appInfo['inviteCode'] . '&euid=' . $euid;
                        $share_url_escape = str_replace('&', '%26', $share_url);
                        $share_msg .= "{DOMAIN}/user/register?cn={COUPON}%26euid={EUID}";
                        $share_msg = str_replace(array('{COUPON}', '{DOMAIN}', '{EUID}'), array($this->appInfo['inviteCode'], $domain, $euid), $share_msg);
                    } else { //显示自己的邀请码
                        $share_msg .= "{DOMAIN}/user/register?cn={COUPON}";
                        $share_msg = str_replace(array('{COUPON}', '{DOMAIN}'), array($coupon['short_alias'], $domain), $share_msg);
                        $share_url_escape = $share_url = $domain . '/?cn=' . $coupon['short_alias'];
                    }
                }
            } else { //主站和普惠
                //如果分站用户来登录主站，显示分站的邀请码，不显示邀请注册，邀请奖励等
                $appInfo = $this->getAppInfoByInviteCode($user_id);
                $showSiteCoupon = false;
                if(!empty($appInfo)){
                    $setParams = (array) json_decode($appInfo['setParams'], true);
                    $showSiteCoupon = !empty($appInfo['inviteCode']) && $setParams['showSiteCoupon'];
                }
                //showSiteCoupon标志位
                if($showSiteCoupon){
                    //显示分站邀请码信息
                    $this->appInfo = $appInfo;
                    $this->tpl->assign('showSiteCoupon', $showSiteCoupon);
                    $euid = $this->getEuid($user_id);
                    $this->tpl->assign('site_invite_code', $appInfo['inviteCode']);
                    $domain = 'http://'.$appInfo['usedWebDomain'];
                    $share_url = $domain . '/?cn=' . $appInfo['inviteCode'] . '&euid=' . $euid;
                    $share_url_escape = str_replace('&', '%26', $share_url);
                    $share_msg .= "{DOMAIN}/user/register?cn={COUPON}%26euid={EUID}";
                    $share_msg = str_replace(array('{COUPON}', '{DOMAIN}', '{EUID}'), array($this->appInfo['inviteCode'], $domain, $euid), $share_msg);
                }else{
                    $share_msg = get_config_db("COUPON_WEB_ACCOUNT_COUPON_PAGE_SHAREMSG", $site_id);
                    $share_url = $domain . '/?cn=' . $coupon['short_alias'];
                    $share_url_escape = $share_url;
                }
            }
        }

        $wxBonusSrv = new \core\service\WXBonusService();
        $inviteSwitch = $wxBonusSrv->isInviter($user_id, $site_id);
        if ($inviteSwitch) {
            $share_url = $wxBonusSrv->getShareUrl($coupon['short_alias']);
            $share_url_escape = str_replace('&', '%26', $share_url);
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
        }

        $couponModelTypes = CouponLogService::getModelTypes();
        unset($couponModelTypes[CouponLogService::MODULE_TYPE_THIRD]);

        $this->tpl->assign('couponModelTypes', $couponModelTypes);
        $this->tpl->assign('showcashbonus', $showcashbonus);
        $this->tpl->assign('idcardpassed', $GLOBALS['user_info']['idcardpassed']);
        $this->tpl->assign('share_url', $share_url);
        $this->tpl->assign('share_url_escape', $share_url_escape);
        $this->tpl->assign('share_msg', str_replace('{$COUPON}', $coupon['short_alias'], $share_msg));
        if(!empty($referer_rebate_msg)) {
            $referer_rebate_msg = str_replace('{$referer_rebate_ratio}', "<span class='color-red2'>{$coupon['referer_rebate_ratio']}</span>", $referer_rebate_msg);
        }
        $this->tpl->assign('referer_rebate_msg',$referer_rebate_msg);
        $this->tpl->assign("inc_file", "web/views/account/coupon.html");
        $this->template = "web/views/account/frame.html";
    }

    private function showSiteCoupon() {
        $showSiteCoupon = false;
        if (!$this->is_wxlc && !$this->is_firstp2p) {
            $setParams = (array) json_decode($this->appInfo['setParams'], true);
            $showSiteCoupon = !empty($this->appInfo['inviteCode']) && $setParams['showSiteCoupon'];
        }

        $this->tpl->assign('showSiteCoupon', $showSiteCoupon);
        return $showSiteCoupon;;
    }

    private function getAppInfoByInviteCode($userId){
        $couponBindSrv = new CouponBindService();
        $bindInfo = $couponBindSrv->getByUserId($userId);
        //检查邀请人的邀请码是否在配置中
        $showSiteCouponArray = explode(',', trim(app_conf('SHOW_SITE_COUPON')));
        if (!empty($bindInfo) && !empty($bindInfo['short_alias']) && in_array($bindInfo['short_alias'], $showSiteCouponArray)){
            //根据邀请码获取AppInfo信息
            $inviteCode = $bindInfo['short_alias'];
            $openSrv = new OpenService();
            $appInfo = $openSrv->getAppInfoByInviteCode($inviteCode);
            if (!empty($appInfo)){
                return $appInfo;
            }
        }
        return false;
    }

}
