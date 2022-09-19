<?php
/**
 * 项目到期前十日系统自动发送项目到期通知书
 * 01 01 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php payment_request.php
 * @author wenyanlei 20140618
 */
require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../system/utils/logger.php';

use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealAgencyModel;
use core\dao\DealRepayModel;
use core\dao\DealLoanTypeModel;

set_time_limit(0);

$switch = intval(app_conf('PAYMENT_NOTICE'));
if($switch != 1){
    die('PAYMENT_NOTICE CLOSE');
}

$ten_start = to_timespan(date('Y-m-d', strtotime('+10 day'))); // 十天之后的开始时间
$eleven_start = to_timespan(date('Y-m-d', strtotime('+11 day'))); // 十一天之后的开始时间

$send_time = format_date(time(), 'Y年m月d日');
$period_time = format_date(strtotime('+4 day'), 'Y年m月d日');

// 查询出所有的指定天数之内需要还款的标
$repay_list = DealRepayModel::instance()->findAll(
        sprintf("true_repay_time = 0 AND status = 0 AND repay_time >= '%s' AND repay_time < '%s'", $ten_start,$eleven_start));

// 获取 消费贷的 type_id
$type_id_xfd = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XFD);

if ($repay_list) {
    $msgcenter = new Msgcenter();
    foreach ($repay_list as $repay_info) {
        $deal_info = DealModel::instance()->findBy(sprintf("id = %d AND deal_status = 4 AND is_delete = 0", $repay_info['deal_id']));

        // 不给 首山-消费贷 发催收短信
        if ($deal_info['type_id'] == $type_id_xfd) {
            continue;
        }

        if ($deal_info) {
            $agency_info = DealAgencyModel::instance()->find($deal_info['advisory_id'],'name,repay_inform_email');
            $user_info = UserModel::instance()->find($deal_info['user_id'],'real_name');

            if($agency_info && $agency_info['repay_inform_email']){
                $notice = array();
                $notice['agency_name'] = $agency_info['name'];
                $notice['repay_time'] = to_date($repay_info['repay_time'],'Y年m月d日');
                $notice['deal_name'] = $deal_info['name'];
                $notice['borrow_user'] = $user_info['real_name'];
                $notice['borrow_time'] = to_date($deal_info['repay_start_time'],'Y年m月d日');
                $notice['repay_money'] = format_price($repay_info['repay_money'], true);
                $notice['borrow_amount'] = format_price($deal_info['borrow_amount'], true);
                $notice['period_time'] = $period_time;
                $notice['send_time'] = $send_time;

                $notice_title = "借款人到期还款通知书";
                $email_arr = explode(',', $agency_info['repay_inform_email']);
                foreach($email_arr as $email_one){
                    if(is_email($email_one)){
                        $msgcenter->setMsg($email_one, 0, $notice, 'TPL_PAYMENT_NOTICE', $notice_title);
                        echo '借款id:',$repay_info['deal_id'],'，已发送至：',$email_one,"\r\n";
                    }
                }
            }
        }
    }
    $msgcenter->save();
}
?>
