<?php
namespace core\service\repay;



use core\dao\deal\DealLoadModel;
use core\dao\deal\DealModel;
use core\dao\repay\DealLoanRepayModel;
use core\dao\repay\DealRepayModel;
use core\enum\DealLoanTypeEnum;
use core\enum\UserEnum;
use core\service\deal\DealService;
use core\service\user\UserService;
use libs\sms\SmsServer;
use core\service\BaseService;
use libs\utils\Aes;

class DealRepayMsgService extends BaseService {


    public static function sendMsgBox($dealId,$repayId,$nexRepayId){
        $deal = DealModel::instance()->find($dealId);
        $deal['share_url'] = get_deal_domain($deal['id']) . '/d/'. Aes::encryptForDeal($deal['id']); // 向出借人发送站内信和邮件
        // 获取标的期数信息
        $deal['repay_periods_sum'] = DealRepayModel::instance()->getDealRepayPeriodsSumByUserId($deal['id'], $deal['user_id']);
        $deal['repay_periods_order'] = DealRepayModel::instance()->getDealRepayPeriodsOrderByUserId($deal['id'], $deal['user_id']);

        // 获取非预约投标用户的 id 集合
        $loan_user_id_collection = DealLoadModel::instance()->getDealLoanUserIdsExReservation($dealId);
        $deal_service = new DealService();
        if($deal_service->isDealDT($deal['id']) || $deal_service->isDealDTV3($deal['id'])){//多投不发送站内信
            return true;
        }
        foreach ($loan_user_id_collection as $loan_user_id) {
            $user = UserService::getUserById($loan_user_id);

            DealLoanRepayModel::instance()->sendMsg($deal, $user, $repayId, $nexRepayId);
        }
        return true;
    }

    public static function sendSms($dealId,$repayId){
        $deal_service = new DealService();
        if (app_conf("SMS_ON") != 1 ||  $deal_service-> isDealDT($dealId) || $deal_service->isDealDTV3($dealId)) {
            return true;
        }
        $deal = DealModel::instance()->find($dealId);
        //if ($deal['type_id'] == DealLoanTypeEnum::TYPE_XFD) {
        //    return true;
        //}
        $dlr_model = new DealLoanRepayModel();
        $arr_money = $dlr_model->getNonReserveListByDealId($dealId, $repayId); //排除预约投资

        foreach ($arr_money as $k => $moneyInfo) {
            $user = UserService::getUserById($k);
            $deal_load = DealLoadModel::instance()->findViaSlave($moneyInfo['deal_loan_id']);
            unset($moneyInfo['deal_loan_id']);

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

            if ($user['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
            {
                $_mobile = 'enterprise';
                $accountTitle = get_company_shortname($user['id']); // by fanjingwen
            } else {
                $_mobile = $user['mobile'];
                $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
            }
            $params = array(
                'account_title' => $accountTitle,
                'deal_name' => msubstr($deal['name'], 0, 8),
                'money' => format_price($total),
                'cnt' => $count,
                'content' => implode("，", $tmp_arr),
            );
            SmsServer::instance()->send($_mobile, 'TPL_SMS_LOAN_REPAY_MERGE_NEW', $params, $user['id'], $deal_load['site_id']);
        }
        return true;
    }
}
