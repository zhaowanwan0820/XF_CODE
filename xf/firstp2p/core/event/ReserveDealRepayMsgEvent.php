<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\MsgBoxService;

use core\dao\DealLoanRepayModel;
use core\dao\UserModel;
use core\dao\ReservationCacheModel;

use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use libs\sms\SmsServer;

class ReserveDealRepayMsgEvent extends BaseEvent {
    private $userId;
    private $startTime;
    private $endTime;
    private $fromCache; //读取缓存

    public function __construct($userId, $startTime, $endTime, $fromCache = false) {
        $this->userId = $userId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->fromCache = $fromCache;
    }

    public function execute() {
        if (app_conf("SMS_ON") == 1) {

            //读取缓存
            if ($this->fromCache) {
                $reservationCacheModel = new ReservationCacheModel();
                $repaySum = $reservationCacheModel->getUserReserveDealRepayCache($this->userId, $this->startTime + date('Z'));
            } else {
                $DealLoanRepayModel = new DealLoanRepayModel();
                $repaySum = $DealLoanRepayModel->getReserveDealRepaySumByUserId($this->userId, $this->startTime, $this->endTime);
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("getReserveDealRepaySumByUserId, result: %s, userId: %d, startTime: %d, endTime: %d", json_encode($repaySum), $this->userId, $this->startTime, $this->endTime))));
            if (empty($repaySum) || empty($repaySum['cnt'])) {
                return true;
            }

            $user = UserModel::instance()->find($this->userId);

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

            if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
            {
                $mobile = 'enterprise';
                $accountTitle = get_company_shortname($user['id']); // by fanjingwen
            } else {
                $mobile = $user['mobile'];
                $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
            }
            $params = array(
                'account_title' => $accountTitle,
                'now_time' => to_date($this->startTime, 'Y年m月d日'),
                'money' => format_price($total),
                'cnt' => $count,
                'content' => implode("，", $tmpArr),
            );

            $result = SmsServer::instance()->send($mobile, 'TPL_SMS_RESERVE_LOAN_REPAY_MERGE', $params, $this->userId, 1);

            $content = sprintf('您于%s通过网信随心约成功回款合计%s（共%s次匹配），含%s。', to_date($this->startTime, 'Y年m月d日'), format_price($total), $count, implode("，", $tmpArr));
            $structed_str .= sprintf("回款日期：%s\n", to_date($this->startTime, 'Y-m-d'));
            $structed_content = array(
                'money' => sprintf('+%s', format_price($total)),
                'main_content' => rtrim($structed_str),
                'prepay_tips' => $prepay_tips,
            );
            if (!empty($turn_type)) {
                $structed_content['turn_type'] = $turn_type;
            }
            $msgbox = new MsgBoxService();
            $msgbox->create($this->userId, 9, '回款', $content, $structed_content);

            return $result;
        } else {
            return true;
        }
    }

    public function alertMails() {
        return array('weiwei12@ucfgroup.com');
    }
}
