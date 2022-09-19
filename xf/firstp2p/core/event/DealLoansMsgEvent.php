<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\TaskService;

use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\UserModel;
use core\dao\DealLoanTypeModel;
use libs\utils\Site;
use core\service\DealService;
use libs\sms\SmsServer;

// 放款时发送投资合并短信
class DealLoansMsgEvent extends BaseEvent {
    private $_deal_id;

    public function __construct($deal_id) {
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        $deal_service = new DealService();
        if (app_conf('SMS_ON') == 1  && $deal_service-> isDealDT($this->_deal_id) == false) {
            $deal_data = DealModel::instance()->find($this->_deal_id);
            $deal_load = DealLoadModel::instance()->getNonReserveDealLoanUserList($this->_deal_id);//排除预约投资

            foreach ($deal_load as $val) {
                $money = format_price($val['m']);
                
                $user = UserModel::instance()->find($val['user_id']);
                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($user['id']); // by fanjingwen
                } else {
                    $_mobile = $user['mobile'];
                    $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                }

                $sms_content = array(
                    'account_title' => $accountTitle,
                    'deal_name' => msubstr($deal_data['name'], 0, 9),
                    'money' => $money,
                    'cnt' => $val['c'],
                    'now_time' => date('m-d H:i'),
                );

                if($deal_data['loantype'] == 7) {
                    $tpl = 'TPL_SMS_DEAL_BID_MERGE_GYB_NEW';
                    $sms_content = array(
                        'account_title' => $accountTitle,
                        'deal_name' => msubstr($deal_data['name'], 0, 9),
                        'money' => $money,
                    );
                }else{
                    $tpl = 'TPL_SMS_DEAL_BID_MERGE_NEW';
                }

                $ret = SmsServer::instance()->send($_mobile, $tpl, $sms_content, $user['id'], get_deal_siteid($this->_deal_id));
            }
            return true;
        } else {
            return true;
        }
    }

    public function alertMails() {
        return array();
    }
}
