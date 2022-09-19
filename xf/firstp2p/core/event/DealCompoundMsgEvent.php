<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\dao\UserModel;
use core\dao\DealModel;
use libs\utils\Aes;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

require_once APP_ROOT_PATH . 'system/libs/msgcenter.php';

class DealCompoundMsgEvent extends BaseEvent {
    private $_user_id;
    private $_deal_id;
    private $_money_info;

    public function __construct($user_id, $deal_id, $money_info) {
        $this->_user_id = $user_id;
        $this->_deal_id = $deal_id;
        $this->_money_info = $money_info;
    }

    public function execute() {
        $money_info = $this->_money_info;
        $repay_money = format_price($money_info['repay_money']);
        $manage_money = format_price($money_info['manage_money']);

        $deal = DealModel::instance()->find($this->_deal_id);
        $user = UserModel::instance()->find($this->_user_id);

        $msgcenter = new \Msgcenter();

        if (app_conf("SMS_ON") == 1) {
            // 给出借人发短信
            if ($money_info['principal'] > 0) {
                $tmp_arr[] = "本金" . format_price($money_info['principal']);
            }
            if ($money_info['interest'] > 0) {
                $tmp_arr[] = "利息" . format_price($money_info['interest']);
            }
            $params = array(
                'deal_name' => msubstr($deal['name'], 0, 8),
                'money' => $repay_money,
                'content' => implode("，", $tmp_arr),
            );
            // SMSSend 项目回款短信
            \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_LOAN_REPAY', $params, $user['id']);
        }

        $deal['share_url'] = get_deal_domain($deal['id']) . '/d/'. Aes::encryptForDeal($deal['id']);

        // 给出借人发站内信
        $content = sprintf('您投资的“%s”成功回款%s，本次投资共回款%s，收益:%s元。本次投资已回款完毕。',
            $deal['name'], $repay_money, $repay_money, number_format($money_info['interest'], 2));
        $structured_content = array(
            'money' => sprintf('+%s', number_format($money_info['repay_money'], 2)),
            'main_content' => rtrim(sprintf("%s%s%s",
                                            sprintf("项目：%s\n", $deal['name']),
                                            empty($money_info['principal']) ? '' : sprintf("本金：%s\n", format_price($money_info['principal'])),
                                            empty($money_info['interest']) ? '' : sprintf("收益：%s\n", format_price($money_info['interest']))
                                            )),
            'turn_type' => MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST, // app 跳转类型标识
        );
        $msgbox = new MsgBoxService();
        $msgbox->create($user['id'], 9, '回款', $content, $structured_content);

        // 给出借人发邮件
        if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
        {
            $userName = $user['user_name'];
        }else{
            $userName = get_deal_username($user['id']);
        }
        $notice = array(
            "user_name"   => $userName ,
            "deal_name"   => $deal['name'],
            "deal_url"    => $deal['share_url'],
            "site_name"   => app_conf("SHOP_TITLE"),
            "help_url"    => get_deal_domain($deal['id']) . '/helpcenter',
            "repay_money" => rtrim($repay_money, '元'),
            "all_repay_money" => rtrim($repay_money, '元'),
            "all_income_money" => $money_info['interest'],
            "impose_money" => '',
        );
        $msgcenter->setMsg($user['email'], $user['id'], $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL_LAST', "“{$deal['name']}”回款通知");
        $msgcenter->save();

        return true;
    }

    public function alertMails() {
        return array('wangjiantong@ucfgroup.com');
    }
}
