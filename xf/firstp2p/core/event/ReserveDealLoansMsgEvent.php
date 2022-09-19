<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\TaskService;
use core\service\MsgBoxService;

use core\dao\DealLoadModel;
use core\dao\UserModel;
use core\dao\ReservationCacheModel;
use libs\sms\SmsServer;


class ReserveDealLoansMsgEvent extends BaseEvent {
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
        if (app_conf('SMS_ON') == 1) {
            $user = UserModel::instance()->find($this->userId);

            if ($this->fromCache) {
                $loanSum = ReservationCacheModel::instance()->getUserReserveDealLoansCache($this->userId, $this->startTime + date('Z'));
            } else {
                $loanSum = DealLoadModel::instance()->getReserveDealLoanSumByUserId($this->userId, $this->startTime, $this->endTime);
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, sprintf("getReserveDealLoanSumByUserId, result: %s, userId: %d, startTime: %d, endTime: %d", json_encode($loanSum), $this->userId, $this->startTime, $this->endTime))));
            if (empty($loanSum) || empty($loanSum['c'])) {
                return true;
            }

            $money = format_price($loanSum['m']);

            if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
            {
                $mobile = 'enterprise';
                $accountTitle = get_company_shortname($user['id']); // by fanjingwen
            } else {
                $mobile = $user['mobile'];
                $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
            }
            $sms_content = array(
                'account_title' => $accountTitle,
                'now_time' => to_date($this->startTime, 'Y年m月d日'),
                'money' => $money,
                'cnt' => $loanSum['c'],
            );

            $tpl = 'TPL_SMS_RESERVE_DEAL_BID_MERGE';
            $result = SmsServer::instance()->send($mobile, $tpl, $sms_content, $this->userId, 1);

            $content = sprintf('您于%s通过网信随心约成功预约匹配合计%s（共%s次匹配）。', to_date($this->startTime, 'Y年m月d日'), $money, $loanSum['c']);
            $msgbox = new MsgBoxService();
            $msgbox->create($this->userId, 19, '放款计息', $content);

            return $result;

        } else {
            return true;
        }
    }

    public function alertMails() {
        return array('weiwei12@ucfgroup.com');
    }
}
