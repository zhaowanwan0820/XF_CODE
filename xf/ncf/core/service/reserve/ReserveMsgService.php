<?php
/**
 * 短期标信息服务
 *
 * @date 2016-11-15
 * @author guofeng@ucfgroup.com
 */

namespace core\service\reserve;

use core\service\BaseService;
use core\service\user\UserService;
use core\service\msgbox\MsgboxService;
use core\dao\reserve\ReservationCacheModel;
use libs\utils\Logger;
use libs\sms\SmsServer;
use core\enum\UserEnum;
use core\enum\MsgBoxEnum;

class ReserveMsgService extends BaseService
{
    const TYPE_DEAL_REPAY = 1; //还款
    const TYPE_DEAL_LOANS = 2; //放款
    const TYPE_DEAL_CONTRACT = 3; //合同

    private static $sendMethod = [
        self::TYPE_DEAL_REPAY => 'mergeSendRepayMsg',
        self::TYPE_DEAL_LOANS => 'mergeSendLoansMsg',
        self::TYPE_DEAL_CONTRACT => 'mergeSendContractMsg',
    ];

    /**
     * 合并发送信息
     */
    public function mergeSendMsg($userId, $startTime, $endTime, $type) {
        if (!isset(self::$sendMethod[$type])) {
            return false;
        }
        $method = self::$sendMethod[$type];
        $this->$method($userId, $startTime, $endTime);
    }

    /**
     * 合并发送还款信息
     */
    public function mergeSendRepayMsg($userId, $startTime, $endTime) {
        if (app_conf("SMS_ON") != 1) {
            return false;
        }

        $reservationCacheModel = new ReservationCacheModel();
        $repaySum = $reservationCacheModel->getUserReserveDealRepayCache($userId, $startTime + date('Z'));
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("getReserveDealRepaySumByUserId, result: %s, userId: %d, startTime: %d, endTime: %d", json_encode($repaySum), $userId, $startTime, $endTime))));
        if (empty($repaySum) || empty($repaySum['cnt'])) {
            return true;
        }

        $user = UserService::getUserById($userId);
        $count = $repaySum['cnt'];
        unset($repaySum['cnt']);

        $total = array_sum($repaySum);

        $tmpArr = array();
        $structed_str = sprintf("项目：随心约（共%d次）\n", $count);
        $prepay_tips = '';
        $turn_type = 0;
        if (isset($repaySum['principal']) && $repaySum['principal'] > 0) {
            $tmpArr[] = "本金" . format_price($repaySum['principal']);
            $structed_str .= sprintf("本金：%s\n", format_price($repaySum['principal']));
        }
        if (isset($repaySum['intrest']) && $repaySum['intrest'] > 0) {
            $tmpArr[] = "利息" . format_price($repaySum['intrest']);
            $structed_str .= sprintf("利息：%s\n", format_price($repaySum['intrest']));
        }
        if (isset($repaySum['prepay']) && $repaySum['prepay'] > 0) {
            $tmpArr[] = "提前还款本金" . format_price($repaySum['prepay']);
            $structed_str .= sprintf("提前还款本金：%s\n", format_price($repaySum['prepay']));
            $prepay_tips = '提前回款';
            $turn_type = MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST;
        }
        if (isset($repaySum['compensation']) && $repaySum['compensation'] > 0) {
            $tmpArr[] = "提前还款补偿金" . format_price($repaySum['compensation']);
            $structed_str .= sprintf("提前还款补偿金：%s\n", format_price($repaySum['compensation']));
        }
        if (isset($repaySum['impose']) && $repaySum['impose'] > 0) {
            $tmpArr[] = "逾期罚息" . format_price($repaySum['impose']);
            $structed_str .= sprintf("逾期罚息：%s\n", format_price($repaySum['impose']));
        }
        if (isset($repaySum['prepayIntrest']) && $repaySum['prepayIntrest'] > 0) {
            $tmpArr[] = "提前还款利息" . format_price($repaySum['prepayIntrest']);
            $structed_str .= sprintf("提前还款利息：%s\n", format_price($repaySum['prepayIntrest']));
        }

        if ($user['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
        {
            $mobile = 'enterprise';
            $accountTitle = get_company_shortname($user['id']); // by fanjingwen
        } else {
            $mobile = $user['mobile'];
            $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
        }
        $params = array(
            'account_title' => $accountTitle,
            'now_time' => to_date($startTime, 'Y年m月d日'),
            'money' => format_price($total),
            'cnt' => $count,
            'content' => implode("，", $tmpArr),
        );

        $result = SmsServer::instance()->send($mobile, 'TPL_SMS_RESERVE_LOAN_REPAY_MERGE', $params, $userId);

        $content = sprintf('您于%s通过网信随心约成功回款合计%s（共%s次匹配），含%s。', to_date($startTime, 'Y年m月d日'), format_price($total), $count, implode("，", $tmpArr));
        $structed_str .= sprintf("回款日期：%s\n", to_date($startTime, 'Y-m-d'));
        $structed_content = array(
            'money' => sprintf('+%s', format_price($total)),
            'main_content' => rtrim($structed_str),
            'prepay_tips' => $prepay_tips,
        );
        if (!empty($turn_type)) {
            $structed_content['turn_type'] = $turn_type;
        }
        $msgbox = new MsgBoxService();
        $msgbox->create($userId, 9, '回款', $content, $structed_content);
        return $result;
    }

    /**
     * 合并发送放款信息
     */
    public function mergeSendLoansMsg($userId, $startTime, $endTime) {
        if (app_conf('SMS_ON') != 1) {
            return true;
        }

        $user = UserService::getUserById($userId);
        $loanSum = ReservationCacheModel::instance()->getUserReserveDealLoansCache($userId, $startTime + date('Z'));
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("getReserveDealLoanSumByUserId, result: %s, userId: %d, startTime: %d, endTime: %d", json_encode($loanSum), $userId, $startTime, $endTime))));
        if (empty($loanSum) || empty($loanSum['c'])) {
            return true;
        }

        $money = format_price($loanSum['m']);

        if ($user['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
        {
            $mobile = 'enterprise';
            $accountTitle = get_company_shortname($user['id']); // by fanjingwen
        } else {
            $mobile = $user['mobile'];
            $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
        }
        $sms_content = array(
            'account_title' => $accountTitle,
            'now_time' => to_date($startTime, 'Y年m月d日'),
            'money' => $money,
            'cnt' => $loanSum['c'],
        );

        $tpl = 'TPL_SMS_RESERVE_DEAL_BID_MERGE';
        $result = SmsServer::instance()->send($mobile, $tpl, $sms_content, $userId);

        $content = sprintf('您于%s通过网信随心约成功预约匹配合计%s（共%s次匹配）。', to_date($startTime, 'Y年m月d日'), $money, $loanSum['c']);
        $msgbox = new MsgboxService();
        $msgbox->create($userId, 19, '放款计息', $content);

        return $result;
    }

    /**
     * 合并发送合同短信
     */
    public function mergeSendContractMsg($userId, $startTime, $endTime) {
        Logger::info('ReserveDealContractMsgEvent');
        $content = sprintf('您于%s通过网信随心约预约匹配的项目合同已下发。', date('Y年m月d日', $startTime));
        $msgbox = new MsgBoxService();
        $msgbox->create($userId, 32, '合同下发', $content);
        //send_user_msg("合同下发",$content,0,$userId,get_gmtime(),0,true,32);
        return true;
    }
}
