<?php
/**
 * CouponPage.php
 *
 * @date 2014-06-17
 * @author daiyuxin <daiyuxin@ucfgroup.com>
 */

namespace api\controllers\account;


use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\CouponService;
use core\service\CouponLogService;

/**
 * 我的优惠码接口
 *
 *
 * tips：优惠券说明;(在firstp2p后台配置，公共配置，优惠券客户端我的优惠码webview页面邀请说明，COUPON_APP_ACCOUNT_COUPON_PAGE_TIPS)
 * shareMsg: 点击邀请button后的分享文案; (在firstp2p后台配置，公共配置， 优惠券客户端点击邀请按钮文案，COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG)
 * Class CouponPage
 * @package api\controllers\account
 */
class CouponPage extends AppBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "site_id" => array("filter" => "int", "message" => "id error"),
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
        $type = '';

        $GLOBALS['sys_config']['TPL_SITE_DIR'] = $GLOBALS['sys_config']['TPL_SITE_LIST'][$site_id];
        $app_version = $_SERVER['HTTP_VERSION'];

        $user = $this->getUserByToken();

        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $is_used_code = $this->rpc->local('CouponService\isCouponUsed', array($user['id']));
        //没有通过身份认证   并且没有使用过
        if(($user['idcardpassed'] !=1) && !$is_used_code){
            // 未实名认证用户的默认提示返利
            $newRegisterInviteRebateDefault = app_conf('NEW_REGISTER_INVITEE_REBATE_DEFAULT');
            $newRegisterRebateDefault = app_conf('NEW_REGISTER_REBATE_DEFAULT');
            $this->tpl->assign('newRegisterInviteRebateDefault', $newRegisterInviteRebateDefault);
            $this->tpl->assign('newRegisterRebateDefault', $newRegisterRebateDefault);
            $this->tpl->assign('rebateProfit', number_format(10000 * $newRegisterRebateDefault, 0, '', ''));
            $this->tpl->assign('is_not_code', true);
        }

        $coupons = $this->rpc->local('CouponService\getUserCoupons', array($user['id']));
        if ($this->rpc->local('BonusService\isCashBonusSender', array($user['id'], $site_id))) {//现金红包分享
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
        } else {
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG", $site_id);
        }

        $isO2O = false;
        if ($this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_HY_USER', $user['id']))
            || $this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_SELLER', $user['id']))) {
            $isO2O = true;
        }
        foreach ($coupons as &$c){
                $c['rebate_ratio'] = sprintf("%.2f", $c['rebate_ratio']);
                $c['referer_rebate_ratio'] = sprintf("%.2f", $c['referer_rebate_ratio']);
                $c['shareMsg'] = urlencode(str_replace('{$COUPON}', $c['short_alias'], $share_msg));
        }

        $firstCoupon = array_slice($coupons, 0, 1);
        $this->tpl->assign("coupons", $coupons);
        $this->tpl->assign("firstCoupon", $firstCoupon);
        $this->tpl->assign("shareMsg", $shareMsg);
        $this->tpl->assign("siteid", $siteid);
        $this->tpl->assign("isO2O", $isO2O);

        $coupon_log_res = $this->getLogPaid($type, $user['id']);
        $this->tpl->assign('coupon_log', $coupon_log_res['data']);

        /*if ($app_version >= 300) { // app3.0
            $this->template = str_replace('coupon_page','coupon_page_v3',$this->template);
        } elseif ($app_version >= 200) { // app2.0
            $this->template = str_replace('coupon_page','coupon_page_v2',$this->template);
        }*/
    }

    /*public function _after_invoke() {
        $this->tpl->display($this->template);
    }*/

    protected function getLogPaid($type, $user_id) {
        $code = '';
        $firstRow = 0;
        $pageSize = 50;

        $result = $this->rpc->local('CouponLogService\getLogPaid', array($type, $user_id, $firstRow, $pageSize, $code));
        

        $coupon_log_list = $result['data']['list'];
        foreach ($coupon_log_list as &$item) {
            $pay_status_text = '--';
            $note = '其他状态';
            $pay_money = number_format($item['referer_rebate_amount_2part'], 2);
            if ($item['pay_status'] == CouponService::PAY_STATUS_NO_IDPASSED) {
                $pay_status_text = '--';
                $note = "被邀请人尚未实名认证及绑定银行卡";
            } else if ($item['pay_status'] == CouponService::PAY_STATUS_IDPASSED) {
                $pay_status_text = '--';
                $note = "被邀请人尚未绑定银行卡";
            } else if (in_array($item['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID))) {
                if ($item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) {
                    $pay_status_text = '已返 <em>' . $item['count_pay'] . '</em> 次';
                    $pay_status_text .= '<br/>共计 <em>' . $item['sum_pay_refer_amount'] . '</em> 元';
                    $note = '邀请人已赎回<br/>返利完成';
                } else {
                    $pay_status_text = '已返 <em>' . $pay_money . '</em>';
                    $note = '返利完成';
                }
            } else if ($item['pay_status'] == CouponService::PAY_STATUS_PAYING) {
                $pay_status_text = '已返 <em>' . $item['count_pay'] . '</em> 次';
                $pay_status_text .= '<br/>共计 <em>' . $item['sum_pay_refer_amount'] . '</em> 元';
                $note = '投资放款后每7天返利一次，直至赎回。';
            } else {
                $pay_status_text = $item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND ? '待返' : '待返 ' . $pay_money;
                $note = $item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND ? '投资放款后每7天返利一次，直至赎回。' : "投资完成，预计15个工作日后获得返利";
            }

            if ($item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) {
                $log_info = "{$item['consume_real_name']}受您邀请，投资通知贷项目，{$item['deal_name']}项目。投资：{$item['deal_load_money']}";
            } else {
                $log_info = "{$item['consume_real_name']}受您邀请，投资{$item['deal_name']}项目。投资：{$item['deal_load_money']}，还款方式：{$item['repay_time']}{$item['loantype_time']}，{$item['loantype']}";
            }
            if ($item['repay_start_time']) {
                $log_info .= "，起息日：" . $item['repay_start_time'];
            }

            $item['pay_status_text'] = $pay_status_text;
            $item['note'] = $note;
            $item['log_info'] = $log_info;
        }
        $result['data']['list'] = $coupon_log_list;


        return $result;
    }
}
