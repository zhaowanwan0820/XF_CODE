<?php
/**
 * 每日17点统计用户总余额
 *
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
set_time_limit(0);
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use core\service\PaymentCheckService;
use core\dao\UserGroupModel;

\libs\utils\Script::start();
ini_set('memory_limit', '2048M');
$paymentCheckService = new \core\service\PaymentCheckService();
// 统计结果
$statistics = array();
$statistics['money'] = 0.00;
$statistics['lock_money'] = 0.00;
$statistics['loanFreezeMoney'] = 0.00;
$statistics['withdrawFreezeMoney'] = 0.00;

PaymentApi::log('PaymentSupportStatistics start');

// 等待资料，未满标， 满标未放款标的id 列表
$dealIdList = $GLOBALS['db']->get_slave()->getCol("SELECT id FROM firstp2p_deal WHERE deal_status IN (0,1,2)");
$dealIdInStr = implode(',', $dealIdList);


//全量用户总额扫描
$exceptionUserIds = array();
$maxUserId = $paymentCheckService->getMaxUserId();
for ($i = 1; $i < $maxUserId; $i += 1000)
{
    $userMoney = $paymentCheckService->getUserMoney("id BETWEEN {$i} AND {$i}+1000");
    foreach ($userMoney as $id => $userInfo)
    {
        statistics($id, $userInfo, $statistics);
    }
}

/**
 * 统计用户的用户的可用余额和冻结金额
 */
function statistics($userId, $userInfo, &$statistics)
{
    // 可用余额总数
    $statistics['money'] = bcadd($statistics['money'], $userInfo['money'], 2);
    // 冻结金额总数
    $statistics['lock_money'] = bcadd($statistics['lock_money'], $userInfo['lock_money'], 2);
    // 用户投资冻结汇总
    $userLoanFreezeMoney = 0.00;
    if (!empty($dealIdInStr))
    {
        $sql = "SELECT SUM(money) as loanFreezeMoney FROM firstp2p_deal_load WHERE user_id = '{$userId}' AND deal_id IN ({$dealIdInStr})";
        $userLoanFreezeMoney = $GLOBALS['db']->get_slave()->getOne($sql);
    }
    $statistics['loanFreezeMoney'] = bcadd($statistics['loanFreezeMoney'], $userLoanFreezeMoney, 2);
    // 用户提现冻结汇总
    $userWithdrawFreezeMoney = 0.00;
    $sql = "SELECT SUM(money) as withdrawFreezeMoney FROM firstp2p_user_carry WHERE user_id = '{$userId}' AND withdraw_status IN (0,3) AND status IN (0,1,3)";
    $userWithdrawFreezeMoney = $GLOBALS['db']->get_slave()->getOne($sql);
    $statistics['withdrawFreezeMoney'] = bcadd($statistics['withdrawFreezeMoney'], $userWithdrawFreezeMoney, 2);
}

$body = "截止" . format_date(time()) . "用户账户总余额统计";
$body.= "<style>.table-c table{border-right:1px solid #ccc;border-bottom:1px solid #ccc}.table-c table td{border-left:1px solid #ccc;border-top:1px solid #ccc}</style>";
$body.= "<div class='table-c'><table border='0' cellspacing='0' cellpadding='0'><tr><td>可用余额</td><td>投资冻结</td><td>提现冻结</td><td>冻结总额</td></tr>";
$loanFreezeMoney = number_format($statistics['loanFreezeMoney'], 2, '.', ',');
$withdrawFreezeMoney = number_format($statistics['withdrawFreezeMoney'], 2, '.', ',');
$totalMoney = number_format($statistics['money'], 2, '.', ',');
$lockTotalMoney = number_format($statistics['lock_money'], 2, '.', ',');
$body .= "<tr>
    <td>{$totalMoney}</td>
    <td>{$loanFreezeMoney}</td>
    <td>{$withdrawFreezeMoney}</td>
    <td>{$lockTotalMoney}</td>
    </tr>";
$body.="</table></div>";

//发送短信
$content = '总可用:'.$totalMoney."\n投资冻结：".$loanFreezeMoney."\n提现冻结:".$withdrawFreezeMoney."\n总冻结：".$lockTotalMoney."\n";

$mobiles = array('18611187809', '15811252203');
\libs\sms\SmsServer::sendAlertSms($mobiles,$content);


//发送邮件
$title = "firstp2p用户总余额";
$msgcenter = new msgcenter();
$msgcenter->setMsg('zhangcaixia@ucfgroup.com,wangqunqiang@ucfgroup.com',0, $body, false, $title);
$r = $msgcenter->save();
echo $r ? "发送邮件成功\n" : "发送邮件失败\n";

PaymentApi::log('PaymentSupportStatistics end');
