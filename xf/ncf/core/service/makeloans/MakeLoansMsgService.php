<?php
namespace core\service\makeloans;

use core\service\BaseService;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\enum\UserEnum;
use core\enum\MsgBoxEnum;
use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\msgbox\MsgboxService;
use libs\sms\SmsServer;

/**
 * 放款消息服务类
 * Class MakeLoansMsgService
 * @package core\service\makeloans
 */
class MakeLoansMsgService extends BaseService
{
    /**
     * 放款成功，根据用户订阅设置发送sms
     * @param $dealId 标的Id
     * @return bool
     */
    public function sendSms($dealId) {
        $deal_service = new DealService();
        if (app_conf('SMS_ON') == 1  && $deal_service->isDealDT($dealId) == false) {
            $deal_data = DealModel::instance()->find($dealId);
            $deal_load = DealLoadModel::instance()->getNonReserveDealLoanUserList($dealId);//排除预约投资
            foreach ($deal_load as $val) {
                $money = format_price($val['m']);
                $user = UserService::getUserByCondition("id={$val['user_id']}");
                if ($user['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($user['id']); // by fanjingwen
                } else {
                    $_mobile = $user['mobile'];
                    $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
                }

                $sms_content = array(
                    'account_title' => $accountTitle,
                    'deal_name' => msubstr($deal_data['name'], 0, 9),
                    'money' => $money,
                    'cnt' => $val['c'],
                    'now_time' => date('m-d H:i'),
                );

                $tpl = 'TPL_SMS_DEAL_BID_MERGE_NEW';

                $ret = SmsServer::instance()->send($_mobile, $tpl, $sms_content, $user['id'], get_deal_siteid($dealId));
            }
            return true;
        } else {
            return true;
        }
    }

    /**
     * 放款成功，根据用户订阅设置发送站内信
     * @param $dealId 标的Id
     * @return bool
     */
    public function sendMsg($dealId) {
        $dealId = intval($dealId);
        $dealService = new DealService();
        $deal = DealModel::instance()->find($dealId);
        // 获取合并后的投资列表
        $loan_list = DealLoadModel::instance()->findAll("`deal_id`='{$dealId}' ORDER BY `id` ASC");
        $user_loan_info_collection = array();
        foreach ($loan_list as $k => $v) {
            // 不统计预约投标
            if ($v['source_type'] == DealLoadModel::$SOURCE_TYPE['reservation']) {
                continue;
            }
            if (isset($user_loan_info_collection[$v['user_id']])) {
                $user_loan_info_collection[$v['user_id']]['money'] += $v['money'];
                $user_loan_info_collection[$v['user_id']]['count'] += 1;
            } else {
                $user_loan_info_collection[$v['user_id']]['money'] = $v['money'];
                $user_loan_info_collection[$v['user_id']]['count'] = 1;
            }
        }
        if(!$dealService->isDealDT($dealId)){//多投不发送站内信
            // 发送站内信
            foreach ($user_loan_info_collection as $user_id => $loan_info) {
                $content = sprintf('您投资的“%s”（共%d笔）已成交，投资款%s已放款，开始计息。', $deal['name'], $loan_info['count'], format_price($loan_info['money']));
                $structured_content = array(
                    'main_content' => $content,
                    'turn_type' => MsgBoxEnum::TURN_TYPE_REPAY_CALENDAR,
                );
                $msgbox = new MsgboxService();
                $msgbox->create($user_id, MsgBoxEnum::TYPE_DEAL_LOAN_TIPS, '放款计息', $content, $structured_content);
            }
        }
    }
}

