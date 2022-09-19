<?php
/**
 * CouponInvite.php
 * @date 2014-10-08
 * @author longbo <longbo@ucfgroup.com>
 * 邀请码接口，app3.3版本后使用,
 */

namespace api\controllers\account;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\CouponService;
use core\service\CouponLogService;
use core\service\CouponBindService;
use core\service\OpenService;

/**
 * 我的优惠码接口
 *
 *
 * tips：优惠券说明;(在firstp2p后台配置，公共配置，优惠券客户端我的优惠码webview页面邀请说明，COUPON_APP_ACCOUNT_COUPON_PAGE_TIPS)
 * shareMsg: 点击邀请button后的分享文案; (在firstp2p后台配置，公共配置， 优惠券客户端点击邀请按钮文案，COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG)
 * Class CouponInvite
 * @package api\controllers\account
 */
class CouponInvite extends AppBaseAction {

    private $shareUrlPre = 'http://www.ncfwx.com/hongbao/CashGet?cn=%s';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "site_id" => array("filter" => "int", "message" => "id error"),
            //新wap站分享策略
            "from"        => array("filter" => "string"),
            "site_domain" => array("filter" => "string"),
            "site_coupon" => array("filter" => "string"),
            "site_logo"   => array("filter" => "string"),
            "euid_level"  => array("filter" => "string"),
            "euid"        => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['token'])) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = $data['site_id'] ? $data['site_id'] : 1 ;
        $GLOBALS['sys_config']['TPL_SITE_DIR'] = $GLOBALS['sys_config']['TPL_SITE_LIST'][$site_id];

        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $result = array();

        $isO2O = 0;
        if ($this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_HY_USER', $user['id']))
            || $this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_SELLER', $user['id']))) {
            $isO2O = 1;
        }

        $adv_id = $isO2O ? "O2O我的邀请码说明" : "我的邀请码说明";
        $adv_content = $this->rpc->local('AdvService\getAdv', array($adv_id));
        $invite_text = $this->completeH5($adv_content);
        $result['inviteTextH5'] = $invite_text;

        $result['isRealAuth'] = 1;
        $is_used_code = $this->rpc->local('CouponService\isCouponUsed', array($user['id']));
        //没有通过身份认证   并且没有使用过
        if (($user['idcardpassed'] !=1) && !$is_used_code) {
            $result['isRealAuth'] = 0;
            $this->json_data = $result;
            return;
        }

        $coupons = $this->rpc->local('CouponService\getUserCoupons', array($user['id']));
        if ($this->rpc->local('BonusService\isCashBonusSender', array($user['id'], $site_id))) {//现金红包分享
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
        } else {
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG", $site_id);
        }
        // 返利文案

        $referer_rebate_info = $this->rpc->local('CouponService\getRebateInfo', array($user['id']));
        if($referer_rebate_info["rebate_effect_days"] || !$referer_rebate_info["basic_group_id"]){
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG",site_id);
        }else{
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG_NO_LIMIT",site_id);
        }

        //根据site_id，然后配置对应站点的域名
        if ($site_id) {
            if (intval($site_id) != 0 && intval($site_id) != 1){
               $template_list = $GLOBALS['sys_config']['TEMPLATE_LIST'];
               $site_name = array_search($site_id,$template_list);
               if($site_name){
                 $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN']["$site_name"];
                 if ($site_domain) {
                    $this->shareUrlPre = str_replace(app_conf('WXLC_DOMAIN').'/hongbao/CashGet?cn=%s',$site_domain.'/user/register?type=h5&cn=%s',$this->shareUrlPre);
                    //$shareRegUrl = str_replace(app_conf('WXLC_DOMAIN'), $site_domain, $shareRegUrl);
                 }
               }
            }
        }
        $wxBonusSrv = new \core\service\WXBonusService();
        $inviteSwitch = $wxBonusSrv->isInviter($user['id'], $site_id);
        if ($inviteSwitch) {
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
        }
        //4.6版本后的分享url
        $shareRegUrl = get_config_db('APP_COUPON_INVITE_CARD_URL', $site_id);
        $coupon_list = array();
        foreach ($coupons as $k => $val){
            $c = $val;
            $c['couponId'] = $k;
            $c['rebate_ratio'] = sprintf("%.2f", $val['rebate_ratio']);
            $c['referer_rebate_ratio'] = sprintf("%.2f", $val['referer_rebate_ratio']);
            $c['shareMsg'] = urlencode(str_replace('{$COUPON}', $val['short_alias'], $share_msg));
            if(!empty($referer_rebate_msg)){
                $c['referer_rebate_msg'] = str_replace('{$referer_rebate_ratio}', $c['referer_rebate_ratio'], $referer_rebate_msg);
            }
            $c['shareUrl'] = sprintf($this->shareUrlPre, $val['short_alias']);
            if ($inviteSwitch) {
                $c['shareRegUrl'] = $wxBonusSrv->getShareUrl($val['short_alias']);
            } else {
                $c['shareRegUrl'] = str_replace('{$COUPON}', $val['short_alias'], $shareRegUrl);
            }
            $coupon_list[] = $c;
        }

        $dataType =  ($this->app_version == 10000 || $this->app_version >= 41200) ? CouponLogService::DATA_TYPE_INVITE : 0;
        $couponLogService  = new CouponLogService(CouponLogService::MODULE_TYPE_P2P,$dataType);
        $totalRefererRebateAmount = $couponLogService->getTotalRefererRebateAmount($user['id']);
        $result['couponLog']['referer_rebate_amount'] = number_format($totalRefererRebateAmount['referer_rebate_amount'],2);
        $result['couponLog']['referer_rebate_amount_no'] = number_format($totalRefererRebateAmount['referer_rebate_amount_no'],2);
        $TotalInviteNumber = $this->rpc->local('CouponLogService\getTotalInviteNumber', array($user['id']));
        $result['couponLog']['consume_user_count'] = $TotalInviteNumber;
        $result['coupons'] = $coupon_list;
        $result['shareMsg'] = $shareMsg;
        $result['siteid'] = $site_id;
        $result['isO2O'] = $isO2O;
        $result['shareTitle'] = get_config_db('APP_COUPON_INVITE_CARD_TITLE', $site_id);
        $result['shareDescribe'] = get_config_db('APP_COUPON_INVITE_CARD_DESCRIBE', $site_id);
        $result['shareImg'] = get_config_db('APP_COUPON_INVITE_CARD_IMG', $site_id);

        if ($inviteSwitch) {
            $result['shareTitle'] = get_config_db('CASH_BONUS_SHARE_TITLE', $site_id);
            $result['shareDescribe'] = get_config_db('COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS', $site_id);
        }

        $couponModelTypes = CouponLogService::getModelTypes();
        unset($couponModelTypes[CouponLogService::MODULE_TYPE_THIRD]);
        foreach($couponModelTypes as $modelKey => $modelName){
            if ($site_id == 100 && $modelKey == CouponLogService::MODULE_TYPE_P2P) {
                $modelName = '投资奖励';
            }
            $result['types'][] = array('typeid' => $modelKey,'typename' => $modelName);
        }

        if ($data['from'] == 'wap') {
            $data['userId'] = $user['id'];
            if (!$inviteSwitch) {
                $result = $this->wapShare($data, $result);
            }
        }

        $this->json_data = $result;
    }

    private function completeH5($content) {
        $html5 = <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="format-detection" content="telephone=no" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <title>邀请规则</title>
</head>
<body>
{$content}
</body>
</html>
HTML;
        return $html5;
    }


    private function wapZhuZhanShare($data, $result) {
        $openSrv = new OpenService();
        $appInfo = $openSrv->getAppInfoByUid($data['userId']);
        if(empty($appInfo)) {
            return $result;
        }

        $setParams = (array) json_decode($appInfo['setParams'], true);
        if ($appInfo['inviteCode'] && $setParams['showSiteCoupon']) {
            $data['couponId'] = $appInfo['inviteCode'];
            $data['showSiteCoupon'] = 1;
        } else{
            $data['couponId'] = $result['coupons'][0]['couponId'];
            $data['showSiteCoupon'] = 0;
        }

        $data['site_domain'] = $appInfo['usedWapDomain'];
        $data['site_logo']   = $appInfo['appLogo'];
        $data['euid_level']  = $setParams['euidLevel'] > 1 ? intval($setParams['euidLevel']) : 1;
        return $this->getFenZhanShareReturn($data, $result);
    }

    private function getFenZhanShareUrl($data) {
        $openSrv = new OpenService();
        $shareEuid = $openSrv->getEuid($data);
        if ($shareEuid) {
            $shareEuid = 'euid=' . $shareEuid;
        }

        return 'http://' . $data['site_domain'] . '/user/register?cn=' . $data['couponId'] . '&' . $shareEuid; // & => %26
    }

    private function getFenZhanShareMsg($data) {
        $shareMsg = "100元开启财富之旅！历史平均年化收益8％~12％，0手续费，期限灵活。任务勋章、投资券、加息券等，你想要的玩法全都有！邀请码:%s。%s";
        return sprintf($shareMsg, $data['couponId'], $data['share_url']);
    }

    private function getFenZhanShareReturn($data, $result) {
        $data['share_url'] = $this->getFenZhanShareUrl($data);
        return [
                'showSiteCoupon' => $data['showSiteCoupon'],
                'shareImg'       => $data['site_logo'],
                'couponLog'      => ($data['showSiteCoupon'] == 1) ? [] : $result['couponLog'],
                'types'          => ($data['showSiteCoupon'] == 1) ? [] : $result['types'],
                'coupons'        => [[
                   'couponId'    => $data['couponId'],
                   'shareRegUrl' => urlencode($data['share_url']),
                   'shareMsg'    => urlencode($this->getFenZhanShareMsg($data)),
                ]],
        ];
    }

    private function WapFenZhanShare($data, $result) {
        if ($data['site_coupon']) {
            $data['couponId'] = $data['site_coupon'];
            $data['showSiteCoupon'] = 1;
        }else{
            $data['couponId'] = $result['coupons'][0]['couponId'];
            $data['showSiteCoupon'] = 0;
        }

        return $this->getFenZhanShareReturn($data, $result);
    }

    private function wapShare($data, $result) {
        return $data['site_id'] > 1 ? $this->WapFenZhanShare($data, $result) : $this->wapZhuZhanShare($data, $result);
    }

}
