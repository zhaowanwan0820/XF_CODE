<?php
namespace core\event;

use core\dao\DealLoanPartRepayModel;
use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\dao\DealLoanRepayModel;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealLoanTypeModel;
use core\service\DealService;
use libs\sms\SmsServer;


class DealRepayMsgEvent extends BaseEvent {
    private $_deal_repay_id;
    private $_deal_id;

    public function __construct($deal_repay_id, $deal_id) {
        $this->_deal_repay_id = $deal_repay_id;
        $this->_deal_id = $deal_id;
    }

    public function execute() {
        $deal_service = new DealService();
        $deal_id = $this->_deal_id;
        if (app_conf("SMS_ON") == 1 && $deal_service-> isDealDT($deal_id) == false) {
            $deal_repay_id = $this->_deal_repay_id;

            $deal = DealModel::instance()->find($deal_id);

            if ($deal['type_id'] == DealLoanTypeModel::TYPE_XFD) {
                return true;
            }

            $dlr_model = new DealLoanRepayModel();
            $dlpr_model = new DealLoanPartRepayModel();
            $arr_money = $dlr_model->getNonReserveListByDealId($deal_id, $deal_repay_id); //排除预约投资
            foreach ($arr_money as $k => $moneyInfo) {
                $user = UserModel::instance()->find($k);
                $deal_load = DealLoadModel::instance()->find($moneyInfo['deal_loan_id']);
                unset($moneyInfo['deal_loan_id']);

                if ($deal_service->isDealJF($deal_load['site_id']) == true) {
                    continue;
                }
                if($dlpr_model->isPartNotRepayUser($deal_repay_id,$k)){
                    continue;
                }

                $count = $moneyInfo['cnt'];
                unset($moneyInfo['cnt']);

                $total = array_sum($moneyInfo);

                $tmp_arr = array();
                if ($moneyInfo['principal'] > 0) {
                    $tmp_arr[] = "本金" . format_price($moneyInfo['principal']);
                }
                if ($moneyInfo['intrest'] > 0) {
                    $tmp_arr[] = "利息" . format_price($moneyInfo['intrest']);
                }
                if ($moneyInfo['prepay'] > 0) {
                    $tmp_arr[] = "提前还款本金" . format_price($moneyInfo['prepay']);
                }
                if ($moneyInfo['compensation'] > 0) {
                    $tmp_arr[] = "提前还款补偿金" . format_price($moneyInfo['compensation']);
                }
                if ($moneyInfo['impose'] > 0) {
                    $tmp_arr[] = "逾期罚息" . format_price($moneyInfo['impose']);
                }
                if ($moneyInfo['prepayIntrest'] > 0) {
                    $tmp_arr[] = "提前还款利息" . format_price($moneyInfo['prepayIntrest']);
                }

                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($user['id']); // by fanjingwen
                } else {
                    $_mobile = $user['mobile'];
                    $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                }
                $params = array(
                    'account_title' => $accountTitle,
                    'deal_name' => msubstr($deal['name'], 0, 8),
                    'money' => format_price($total),
                    'cnt' => $count,
                    'content' => implode("，", $tmp_arr),
                );

                //哈哈农庄还款后给用户发送本金转出短信
                if(($deal_service->isDealHF($deal_id)) && (($moneyInfo['principal'] > 0)||($moneyInfo['prepay'] > 0))){
                    $params['content'].= "。本金已根据您的授权转入云图控股账户，详情查询您的账户中合同协议，如有问题咨询400-110-0025";
                }

                SmsServer::instance()->send($_mobile, 'TPL_SMS_LOAN_REPAY_MERGE_NEW', $params, $user['id'], $deal_load['site_id']);

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
