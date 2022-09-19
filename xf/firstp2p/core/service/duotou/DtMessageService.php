<?php

/**
 * DtMessageService.php
 *
 * @date 2016-01-20
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
namespace core\service\duotou;

use core\dao\UserModel;
use libs\utils\Site;
use core\service\MsgBoxService;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use libs\sms\SmsServer;

class DtMessageService {

    const TYPE_BID_SUCCESS          = 1;//投标完成
    const TYPE_REDEMPTION_APPLY     = 2;//赎回申请
    const TYPE_REDEMPTION_SUCCESS   = 3;//赎回成功
    const TYPE_INTEREST_SETTLEMENT  = 4;//结息
    const TYPE_REVOKE_SUCCESS       = 5;//撤销成功

    /**
     * 发送消息
     * @param int $type 类型
     * @param array $msgInfo 消息信息
     * @return boolean 是否发送成功
     */
    public static function sendMessage($type,array $msgInfo){
        switch ($type) {
            case self::TYPE_BID_SUCCESS:
                $mail_title          = "申请加入完成";
                $content = '<p>您已申请加入“'.$msgInfo['name'].'”项目，加入金额为'. format_price($msgInfo['money']);
                $main_content = $content;
                $money = 0;
                if (app_conf("SMS_ON")==1){
                    $sms_content = array(
                            'now_time' => to_date(get_gmtime(), 'm-d H:i'),
                            'deal_name' => msubstr($msgInfo['name'], 0, 9),
                            'money' => format_price($msgInfo['money']),
                    );
                    $user_model = new UserModel();
                    $user = $user_model->find($msgInfo['userId']);

                    SmsServer::instance()->send($user['mobile'], 'TPL_SMS_DEAL_BID', $sms_content, $user['id'], $msgInfo['siteId']);
                }
                break;
            case self::TYPE_REDEMPTION_APPLY:
                $mail_title          = "转让/退出申请";
                $content = '<p>您加入的“'.$msgInfo['name'].'”本金'. format_price($msgInfo["money"]) .'转让/退出申请已成功提交，待结利息将按加入资产还款日发放到您的账户中。';
                $main_content = $content;
                $money = 0;
                if (app_conf("SMS_ON")==1){
                    $sms_content = array(
                            'now_time' => to_date(get_gmtime(), 'm-d H:i'),
                            'deal_name' => msubstr($msgInfo['name'], 0, 9),
                            'money' => format_price($msgInfo['money']),
                    );
                    $user_model = new UserModel();
                    $user = $user_model->find($msgInfo['userId']);

                    SmsServer::instance()->send($user['mobile'], 'TPL_SMS_DTB_REDEMPTION_APPLY', $sms_content, $user['id'], $msgInfo['siteId']);
                }
                break;
            case self::TYPE_REDEMPTION_SUCCESS:
                $mail_title          = "转让/退出成功";
                if($msgInfo["holdDays"] > 0) {//需要扣除管理费
                    $content = '<p>您加入的“'.$msgInfo['name'].'”已持有'.$msgInfo["holdDays"].'天，收取智多新-管理服务费'. format_price($msgInfo["fee"]) .'，转让/退出本金'. format_price($msgInfo["money"]) .'成功,请查验账户。';
                } else {
                    $content = '<p>您加入的“'.$msgInfo['name'].'”转让/退出本金'.format_price($msgInfo["money"]) .'成功,请查验账户。';
                }
                if(intval($msgInfo['isClean']) == 1) {
                    $content = '<p>您加入的“'.$msgInfo['name'].'”已完成清盘，本金加入金额'. format_price($msgInfo["money"]) .'已到账,请查验账户。';
                }
                $main_content = rtrim(sprintf("%s%s%s",
                                        sprintf("项目：%s\n", $msgInfo['name']),
                                        sprintf("本金：%s\n", format_price($msgInfo['money'])),
                                        $msgInfo["holdDays"] > 0 ? sprintf("智多新-转让服务费：%s\n", format_price($msgInfo["fee"])) : ''
                                        ));
                $money = sprintf("+%s", $msgInfo["holdDays"] > 0 ? number_format($msgInfo['money'] - $msgInfo["fee"], 2) : number_format($msgInfo['money'], 2));
                break;
            case self::TYPE_INTEREST_SETTLEMENT:
                $mail_title          = "智多新红包";
                $content = '<p>您加入的“'.$msgInfo['name'].'”项目本期红包'. format_price($msgInfo["money"]) .'已结，请查验账户。';
                $main_content = rtrim(sprintf("%s%s",
                                        sprintf("项目：%s\n", $msgInfo['name']),
                                        sprintf("红包：%s\n", format_price($msgInfo['money']))
                                        ));
                $money = sprintf("+%s", format_price($msgInfo['money']));
                break;
            case self::TYPE_REVOKE_SUCCESS:
                $mail_title          = "撤销成功";
                $content = '<p>您加入的“'.$msgInfo['name'].'”转让/退出本金'.format_price($msgInfo["money"]) .'成功,请查验账户。';
                $main_content = rtrim(sprintf("%s%s",
                    sprintf("项目：%s\n", $msgInfo['name']),
                    sprintf("本金：%s\n", format_price($msgInfo['money']))
                ));
                $money = sprintf("+%s", $msgInfo["holdDays"] > 0 ? number_format($msgInfo['money'] - $msgInfo["fee"], 2) : number_format($msgInfo['money'], 2));
                break;
        }
        $structured_content = array(
            'main_content' => $main_content,
            'money' => $money,
        );
        $msgbox = new MsgBoxService();
        $msgbox->create($msgInfo['userId'], 37, $mail_title, $content, $structured_content);
        return true;
    }
}

?>
