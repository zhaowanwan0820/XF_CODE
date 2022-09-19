<?php
/**
 * ChannelFeeService.php
 *
 * @date 2014-04-22
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use app\models\service\Finance;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealSiteModel;
use core\dao\DealLoadModel;
use core\dao\DealChannelModel;
use core\dao\DealChannelLogModel;
use core\dao\CouponLogModel;
use core\dao\FinanceQueueModel;
use libs\utils\Aes;

class ChannelFeeService extends BaseService {

    protected static $factor_default = 1.0000;

    /**
     * 增加渠道推广的投标记录
     * 用于前台链接推广和后台人工添加的渠道推广记录
     *
     * @param $deal_load_id 投标的id
     * @param $channel_value 渠道号
     * @param $add_type 添加类型 1:前台链接; 2:后台手工添加
     * @param $pay_factor 返利系数 可空，用于手工设置
     * @return 处理结果
     *
     */
    public function insert_deal_channel_log($deal_load_id, $channel_value, $add_type, $pay_factor = false) {
        if (empty($deal_load_id) || empty($channel_value) || empty($add_type)) {
            return false;
        }

        $channel_model = new DealChannelModel();
        $channel_id = $channel_model->add_deal_channel($channel_value);
        if (empty($channel_id)) {
            return false;
        }

        $deal_load = DealLoadModel::instance()->find($deal_load_id);
        $deal = DealModel::instance()->find($deal_load['deal_id']);
        if (empty($deal)) {
            return false;
        }

        // 已经使用优惠券，则不能使用邀请码
        $coupon_log_dao = new CouponLogModel();
        $coupons = $coupon_log_dao->findByDealLoadId($deal_load_id);
        if (!empty($coupons)) {
            return false;
        }

        $deal_site = DealSiteModel::instance()->getSiteByDeal($deal['id']);
        $site_id = $deal_site['site_id'];

        $pay_factor = $pay_factor ? $pay_factor : $this->get_channel_pay_factor($channel_value, $site_id);
        if (empty($pay_factor)) {
            return false;
        }

        $pay_fee = $this->getPayFee($deal, $deal_load, $pay_factor);

        $deal_status = $deal['deal_status'] == 3 ? 2 : ($deal['deal_status'] >= 4 ? 1 : 0);

        //增加记录
        $channel_log_model = new DealChannelLogModel();
        $channel_log_model->channel_id = $channel_id;
        $channel_log_model->deal_id = $deal['id'];
        $channel_log_model->advisor_fee_rate = $deal['advisor_fee_rate'];
        $channel_log_model->pay_factor = $pay_factor;
        $channel_log_model->user_id = $deal_load['user_id'];
        $channel_log_model->deal_load_id = $deal_load['id'];
        $channel_log_model->deal_load_money = $deal_load['money'];
        $channel_log_model->pay_fee = $pay_fee;
        $channel_log_model->deal_status = $deal_status;
        $channel_log_model->fee_status = 0;
        $channel_log_model->create_time = get_gmtime();
        $channel_log_model->pay_time = 0;
        $channel_log_model->add_type = $add_type;
        $existDealLoad = DealChannelLogModel::instance()->getLogByDealLoanId($channel_log_model['deal_load_id']);
        if (!empty($existDealLoad)) {
            return false;
        }
        return $channel_log_model->insert();
    }

    public function getPayFee($deal, $deal_load, $pay_factor) {
        //计算返利金额
        $advisor_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['advisor_fee_rate'], $deal['repay_time']);
        $pay_fee = $advisor_fee_rate * $deal_load['money'] * $pay_factor * 0.01;
        $pay_fee = round($pay_fee, 2);
        return $pay_fee;
    }

    public static function updatePayFee($channel_log_id) {
        $channel_log = DealChannelLogModel::instance()->find($channel_log_id);
        if (empty($channel_log)) {
            return false;
        }
//        if (!empty($channel_log['pay_fee']) && $channel_log['pay_fee'] > 0) {
//            return true;
//        }

        // 2014-01-14 19:00:00 之前的单子存的是期间利率
        $advisor_fee_rate = $channel_log['advisor_fee_rate'];
        $time_before = strtotime('2014-01-14 19:00:00');
        $create_time = $channel_log['create_time'] + (3600 * 8);
        if ($create_time > $time_before) {
            $deal = DealModel::instance()->find($channel_log['deal_id']);
            $advisor_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $advisor_fee_rate, $deal['repay_time']);
        };

        $pay_fee = $advisor_fee_rate * $channel_log['deal_load_money'] * $channel_log['pay_factor'] * 0.01;
        if (empty($pay_fee) || $pay_fee <= 0) {
            return false;
        }
        $pay_fee = round($pay_fee, 2);

        $channel_log->pay_fee = $pay_fee;
        $channel_log->save();
        return $pay_fee;
    }

    /**
     * 前台记录渠道推广的投标记录
     * 用于前台链接推广的投标成功记录，从cookie中获取渠道号
     *
     * @param $deal_load_id 投标的id
     * @param $deal 订单信息
     * @return 处理结果
     */
    function add_deal_channel_log($deal_load_id) {
        //获取request参数
        $channel_value = \es_cookie::get('channel') ? trim(\es_cookie::get('channel')) : '';
        if (empty($channel_value)) {
            return true;
        }

        // 已经使用优惠券，则不能使用邀请码
        try {
            $coupon_log_dao = new CouponLogModel();
            $coupons = $coupon_log_dao->findByDealLoadId($deal_load_id);
            if (!empty($coupons)) {
                //todo return false?
                return true;
            }
        } catch (\Exception $e) {
            return true;
        }

        $add_type = 1; // 通过链接推广
        return $this->insert_deal_channel_log($deal_load_id, $channel_value, $add_type);
    }


    /**
     * 获取渠道号对应的返利系数
     *
     * 网站返利系数都为1；顾问个人有系数值，则取个人系数值，否则取所属组别的系数
     * 顾问个人user表中的返利系数值默认为0，认为是没有设置，取默认值1
     * @param $channel_value 渠道号
     * @return string 返利系数
     */
    public function get_channel_pay_factor($channel_value, $site_id = false) {
        // 返利系数翻倍活动。要设置统一系数值，起始结束时间必须设置一个才会生效
        $start_date = get_config_db('CHANNEL_PAY_FACTOR_START_DATE', $site_id);
        $end_date = get_config_db('CHANNEL_PAY_FACTOR_END_DATE', $site_id);
        $factor_common = get_config_db('CHANNEL_PAY_FACTOR_COMMON', $site_id);
        $now = time();
        $start_date = strtotime($start_date);
        $end_date = strtotime($end_date);
        if (!empty($factor_common) && is_numeric($factor_common) && !(empty($start_date) && empty($end_date))) {
            $factor = $factor_common;
            if (!empty($start_date) && $now < $start_date) {
                $factor = false;
            }
            if (!empty($end_date) && $now > $end_date) {
                $factor = false;
            }
            if ($factor !== false) {
                return $factor_common;
            }

        }

        $factor = self::$factor_default;
        if (is_numeric($channel_value)) { // 会员类型
            $channel_value = intval($channel_value);
            $user_model = new UserModel();
            $advisor_info = $user_model->find($channel_value);
            //$advisor_info = get_user_info($channel_value, true);
            if (!empty($advisor_info) && $advisor_info['is_effect'] != 0 && $advisor_info['is_delete'] != 1) {
                if (!empty($advisor_info['channel_pay_factor']) && $advisor_info['channel_pay_factor'] > 0) {
                    $factor = $advisor_info['channel_pay_factor'];
//            } else { // 不取群组系数
//                $sql_group_factor = "SELECT channel_pay_factor FROM " . DB_PREFIX . "user_group WHERE id=" . $advisor_info['group_id'];
//                $group_factor = $GLOBALS['db']->getOne($sql_group_factor);
//                if (!empty($group_factor) && $group_factor > 0) {
//                    $factor = $group_factor;
//                }
                }
            }
        }
        return $factor;
    }

    /**
     * 获取更新渠道信息
     * 根据渠道号获取渠道信息，当渠道号为有效user_id时，若渠道记录不存在，则自动添加
     *
     * @param $channel_value 渠道号
     * @return 处理结果
     */
    public function add_deal_channel($channel_value) {
        //判断channel类型，channel_value为字符串，当网站处理；channel_value为int且为user_id有效值，否则无效
        $channel_type = 1;
        if (is_numeric($channel_value)) { // 会员类型
            $channel_value = intval($channel_value);
            $advisor_info = get_user_info($channel_value, true);
            if (empty($advisor_info) || $advisor_info['is_effect'] == 0 || $advisor_info['is_delete'] == 1) {
                return false;
//        } else if ($channel_value == $GLOBALS['user_info']['id']) { //推广渠道不允许是自己，目前允许
//            return false;
            } else {
                $channel_type = 0;
            }
        }

        //获取渠道ID，如果是新渠道，则新增
        $channel_id = DealChannelModel::instance()->getIdByTypeAndValue($channel_type, $channel_value);
        if (empty($channel_id)) {
            if ($channel_type == 0) {
                $time = get_gmtime();
                $channel_id = DealChannelModel::instance()->addRecord($advisor_info['id'], 0, $advisor_info['user_name'], $time, $time);
                if ($channel_id <= 0) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return $channel_id;
    }


    /**
     * 根据订单id更新推广记录的订单状态
     *
     * @param $deal_id 订单id
     * @param $deal_status 订单状态 0:投标成功; 1:订单还清; 2:流标'
     */
    public function update_deal_channel_log_status($deal_id, $deal_status) {
        $rs = DealChannelLogModel::instance()->updateStatusByDeal($deal_id, $deal_status);

        //订单变成还款中时，自动结算邀请返利
        if ($deal_status == 1 && get_system_conf_by_deal($deal_id, 'CHANNEL_AUTO_PAY_ON') == 1) {
            $pay_list = DealChannelLogModel::instance()->getListWithLog($deal_id);
            if (!empty($pay_list)) {
                foreach ($pay_list as $item) {
                    $this->pay_channel_fee($item['id']);
                }
            }
        }


        return $rs;
    }

    /**
     * 根据推广记录id结算返利
     *
     * @param $channel_log_id 推广记录id
     */
    public function pay_channel_fee($channel_log_id) {
        if (empty($channel_log_id)) {
            return false;
        }
        //只处理顾问类型，不处理网站类型
        $log_info = DealChannelLogModel::instance()->getInfoByLogId($channel_log_id);
        if (empty($log_info)) {
            return false;
        }

        //获取相关信息
        $syncRemoteData = array();
        $userinfo = UserModel::instance()->find($log_info['user_id']);
        $rel_user_name = $userinfo['user_name'];
        $channel_user_info = UserModel::instance()->find($log_info['channel_value'], 'id, user_name, real_name, mobile, email, user_type');
        $deal = DealModel::instance()->find($log_info['deal_id'], 'id, name, repay_time, loantype');
        $shop_title = get_deal_domain_title($deal['id']);
        if (empty($rel_user_name) || empty($channel_user_info) || empty($deal) || empty($shop_title)) {
            return false;
        }
        $deal['url'] = '/d/'.Aes::encryptForDeal($deal['id']);
        //url("index", "deal", array("id" => $deal['id']));

        //计算返利金额
//    $advisor_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $log_info['advisor_fee_rate'], $deal['repay_time']);
//    $money = $advisor_fee_rate * $log_info['deal_load_money'] * $log_info['pay_factor'] * 0.01;
//    if (empty($money)) {
//        return false;
//    }
//    $money = round($money, 2);
        $money = $log_info['pay_fee'];

        //更新推广记录结算状态为已结清
        $rs = DealChannelLogModel::updateStatusByLogId($channel_log_id);
        if (empty($rs)) {
            return false;
        }

        //更新用户账户资金记录
        \FP::import("libs.libs.user");
        $log_note = $rel_user_name . "受您邀请，投资" . $deal['name'] . "项目，投资金额" . format_price($log_info['deal_load_money']) . "，投资期限";
        $log_note .= $deal['repay_time'] . ($deal['loantype'] == 5 ? '天' : '个月');
    // TODO finance  推广返利 | 推广用户获得平台的返利补贴 | 已同步
        if (bccomp($money, '0.00', 2) > 0 ) {
            $syncRemoteData[] = array(
                'outOrderId' => 'DEALCHANNEL|' . $channel_log_id,
                'payerId' => $platform_user_id,
                'receiverId' => $channel_user_info['id'],
                'repaymentAmount' => $money, // 以分为单位
                'curType' => 'CNY',
                'bizType' => '3',
                'batchId' => '',
            );
        }
        modify_account(array("money" => $money), $channel_user_info['id'], "邀请返利", true, $log_note);
        // 平台账户支出
        $log_note = "编号" . $deal['id'] . " " . $deal['name'] . " 投资记录ID" . $log_info['deal_load_id'] . " 推广人姓名" . $channel_user_info['real_name'];
        $platform_user_id = app_conf('DEAL_CONSULT_FEE_USER_ID');
    // TODO finance 推广返利平台补贴 已同步
        modify_account(array("money" => -$money), $platform_user_id, "返利支出", true, $log_note);
        if (!empty($syncRemoteData)) {
            FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
        }
        $content = "您好，您在" . $shop_title . "的邀请的投资 “<a href=\"" . $deal['url'] . "\">" . $deal['name'] . "</a>”成功放款。";
        $content .= "您从" . $rel_user_name . "处获得返利" . $money . "元，感谢您的关注和支持。";
        //站内信
        \FP::import("libs.common.app");
        send_user_msg("邀请返利成功", $content, 0, $channel_user_info['id'], get_gmtime(), 0, true, 1);

        \FP::import("libs.libs.msgcenter");
        $msgcenter = new \Msgcenter();

        //短信通知
        if (app_conf("SMS_ON") == 1 && app_conf('SMS_SEND_REPAY') == 1) {
            $notice = array(
                "from_user_name" => $channel_user_info['user_name'],
                "user_name" => $rel_user_name,
                "money" => $money,
            );
            // SMSSend 邀请返利邮件通知
            $_mobile = $channel_user_info['mobile'];
            \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_DEAL_CHANNEL_PAY_SMS_NEW', $notice, $channel_user_info['id']);
        }

        //邮件通知
        if (app_conf('MAIL_ON') == 1) {
            $site_host = get_deal_domain($deal['id']);
            $notice['user_name'] = $channel_user_info['user_name'];
            $notice['deal_name'] = $deal['name'];
            $notice['deal_url'] = $site_host . $deal['url'];
            $notice['site_name'] = $shop_title;
            $notice['site_url'] = $site_host . APP_ROOT;
            $notice['help_url'] = $site_host . url("index", "helpcenter");
            $notice['msg_cof_setting_url'] = $site_host . url("index", "uc_msg#setting");
            $notice['repay_money'] = $money;
            $notice['repay_from'] = $rel_user_name;
            // EMailSend 邀请返利邮件通知
            $msgcenter->setMsg($channel_user_info['email'], $channel_user_info['id'], $notice, 'TPL_DEAL_CHANNEL_PAY_EMAIL', "“" . $deal['name'] . "”邀请返利通知");
        }
        @$msgcenter->save();

        return true;
    }


}
