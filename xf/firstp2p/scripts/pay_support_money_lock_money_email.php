<?php
/**
 * 每日0点统计用户总余额
 *
 * @author 张若识<zhangruoshi@ucfgroup.com>
 */
set_time_limit(0);
require(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/../system/utils/es_mail.php');

$body = "截止" . format_date(time()) . "用户账户总余额统计";
echo $body;
$body.= "<style>.table-c table{border-right:1px solid #ccc;border-bottom:1px solid #ccc}.table-c table td{border-left:1px solid #ccc;border-top:1px solid #ccc}</style>";
$body.= "<div class='table-c'><table border='0' cellspacing='0' cellpadding='0'><tr><td>可用余额</td><td>投资冻结</td><td>提现冻结</td><td>冻结总额</td></tr>";

$sql = 'SELECT SUM(money) AS t FROM firstp2p_user';
$totalMoney = number_format($GLOBALS['db']->get_slave()->getOne($sql), 2, '.', ',');
$sql = "select sum(money) from firstp2p_deal_load where deal_id in (select id from firstp2p_deal where `deal_status` in (0,1,2))";
$loanFreezeMoney = number_format($GLOBALS['db']->get_slave()->getOne($sql), 2, '.', ',');
$sql = "SELECT sum(money) FROM firstp2p_user_carry WHERE withdraw_status IN (0,3) AND `status` NOT IN (2,4) AND create_time > UNIX_TIMESTAMP('2014-08-14 00:00:00') - 28800";
$withdrawFreezeMoney = number_format($GLOBALS['db']->get_slave()->getOne($sql), 2, '.', ',');
//$financeFreezeMoney = number_format($GLOBALS['db']->get_slave()->getOne(""), 2, false, ',');
$lockTotalMoney = number_format($GLOBALS['db']->get_slave()->getOne("SELECT SUM(lock_money) FROM firstp2p_user"), 2, '.', ',');
$body .= "<tr>
    <td>{$totalMoney}</td>
    <td>{$loanFreezeMoney}</td>
    <td>{$withdrawFreezeMoney}</td>
    <td>{$lockTotalMoney}</td>
    </tr>";
$body.="</table></div>";
$content = '总可用:'.$totalMoney."\n投资冻结：".$loanFreezeMoney."\n提现冻结:".$withdrawFreezeMoney."\n总冻结：".$lockTotalMoney."\n";
echo $content;
//发送短信
$mobiles = array('18611187809', '15811252203');
\libs\sms\SmsServer::sendAlertSms($mobiles,$content);


//发送邮件
$title = "firstp2p用户总余额";
$msgcenter = new msgcenter();
$msgcenter->setMsg('zhangcaixia@ucfgroup.com,wangqunqiang@ucfgroup.com',0, $body, false, $title);
$r = $msgcenter->save();
echo $r ? "发送邮件成功\n" : "发送邮件失败\n";
