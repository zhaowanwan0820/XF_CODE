<?php
/**
 * 存管总余额核对
 */
ini_set('memory_limit', '1024M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/../system/utils/es_mail.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';
FP::import("libs.common.dict");

use libs\utils\PaymentApi;
use libs\mail\Mail;
use libs\db\Db;
use core\service\SupervisionAccountService;

\libs\utils\Script::start();
// 同步垫资账户资金
$accountService  = new SupervisionAccountService();
// 存管垫资账户id
$paymentUserId = app_conf('SUPERVISION_ADVANCE_ACCOUNT');
$userBalance = $accountService->balanceSearch($paymentUserId);
if (!empty($userBalance['status']) && $userBalance['status'] == 'S') {
    $db = Db::getInstance('firstp2p', 'master');
    $supervisionBalance = bcdiv($userBalance['data']['availableBalance'], 100, 2);
    $db->query("UPDATE firstp2p_user_third_balance SET supervision_balance = {$supervisionBalance} WHERE user_id = '{$paymentUserId}'");
}
$statistics = array();

PaymentApi::log('SupervisionUserMoneyEmail start.');

$sql = "SELECT SUM(supervision_balance) AS totalMoney, SUM(supervision_lock_money) AS totalLockMoney,SUM(supervision_balance+supervision_lock_money) AS accountSum FROM firstp2p_user_third_balance WHERE platform = 1";

$db = Db::getInstance('firstp2p', 'slave');
$info = $db->getRow($sql);
$sql = "SELECT user_id, supervision_balance, supervision_lock_money FROM firstp2p_user_third_balance WHERE platform = 1 AND (supervision_balance < 0 OR supervision_lock_money < 0)";
$minusMoneyUsers = $db->getAll($sql);
$minusMoney = 0;
$minusLockMoney = 0;
foreach ($minusMoneyUsers as $minusMoneyUser) {
    if (bccomp($minusMoneyUser['supervision_balance'], '0.00', 2)  < 0) {
        $minusMoney = bcadd($minusMoney, $minusMoneyUser['supervision_balance'], 2);
    } else if (bccomp($minusMoneyUser['supervision_lock_money'], '0.00', 2) < 0) {
        $minusLockMoney = bcadd($minusLockMoney, $minusMoneyUser['supervision_lock_money'], 2);
    }
}

$body = "<style>.table-c table{border-right:1px solid #ccc;border-bottom:1px solid #ccc}.table-c table td{padding:2px; font-family:微软雅黑; border-left:1px solid #ccc;border-top:1px solid #ccc}</style>";
$body .= '截止'.date('Y-m-d').' 00:00:00网贷用户账户总余额统计';
$body .='<div class="table-c"><table border="0" cellspacing="0" cellpadding="0" >';
$body .= <<<TABLETH
<tr>
    <th>用户组</th>
    <th>正余额</th>
    <th>负余额</th>
    <th>正冻结</th>
    <th>负冻结</th>
    <th>合计</th>
</tr>
TABLETH;
$body .= <<<DOS
        <tr>
            <td>全部用户</td>
            <td>{$info['totalMoney']}</td>
            <td>{$minusMoney}</td>
            <td>{$info['totalLockMoney']}</td>
            <td>{$minusLockMoney}</td>
            <td>{$info['accountSum']}</td>
        </tr>
DOS;

$agencyUser = [
  4159, 7307870, 7307872, 6442574, 8346009, 6823306, 9669412, 8355675
];
foreach ($agencyUser as $userId) {
    $userInfo = $db->getRow("SELECT SUM(supervision_balance) AS supervision_balance, SUM(supervision_lock_money) AS supervision_lock_money FROM firstp2p_user_third_balance WHERE user_id = '{$userId}' AND platform = 1");
    $userInfo['sum'] = bcadd($userInfo['supervision_balance'], $userInfo['supervision_lock_money'], 2);
    $userInfo['supervision_balance'] = bcadd($userInfo['supervision_balance'], 0, 2);
    $userInfo['supervision_lock_money'] = bcadd($userInfo['supervision_lock_money'], 0, 2);
    $body .= <<<DOS
        <tr>
            <td>{$userId}</td>
            <td>{$userInfo['supervision_balance']}</td>
            <td>-</td>
            <td>{$userInfo['supervision_lock_money']}</td>
            <td>-</td>
            <td>{$userInfo['sum']}</td>
        </tr>
DOS;
}
$body .= '</table></div>'.PHP_EOL;

if (!empty($minusMoneyUsers)) {
    $alarmBody= '';
    $alarmBody.="<style>.table-c table{border-right:1px solid #ccc;border-bottom:1px solid #ccc} .table-c table tr td{padding:2px; font-family:微软雅黑; border-left:1px solid #ccc;border-top:1px solid #ccc}</style>";
    $alarmBody.='<div class="table-c"><table border="0" cellspacing="0" cellpadding="0" >';
    $alarmBody.= <<<TABLETH
    <tr>
        <th colspan="3"><span style="color:blue; font-weight:bold;">网信普惠用户账户异常信息</span></th>
    </tr>
    <tr>
        <th>普惠用户标识</th>
        <th>可用余额</th>
        <th>冻结金额</th>
    </tr>
TABLETH;

    foreach ($minusMoneyUsers as $userInfo) {
        $alarmBody.= <<<DOS
            <tr>
                <td>{$userInfo['user_id']}</td>
                <td>{$userInfo['supervision_balance']}</td>
                <td>{$userInfo['supervision_lock_money']}</td>
            </tr>
DOS;
    }
    $alarmBody .= '</table></div>'.PHP_EOL;

    $body.= '<br/>'.$alarmBody;
}

// 发邮件
$msgcenter = new Msgcenter();
$subject = "网信普惠用户总余额";
$mailAddress = app_conf('PAYMENT_USER_TOTAL_MAIL');
$mailAddress = explode(',', $mailAddress);
$mail = new Mail();
$mail->setFrom('noreply@unitedbank.cn', '海口联合农商银行');
$result = $mail->send($subject, $body, $mailAddress);
PaymentApi::log('SupervisionUserMoneyEmail. ret:'.json_encode($result));

\libs\utils\Script::end();
