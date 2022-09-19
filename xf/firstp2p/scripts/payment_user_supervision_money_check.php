<?php
/**
 * 用户余额核对
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');

use libs\utils\PaymentApi;
use core\service\PaymentCheckService;
use core\dao\UserModel;
use core\dao\UserThirdBalanceModel;
use core\service\SupervisionAccountService;
use libs\mail\Mail;

\libs\utils\Script::start();

$startTime = microtime(true);
$paymentCheckService = new \core\service\PaymentCheckService();

define('MAX_SHOW_COUNT', 1000);

PaymentApi::log('UserSupervisionMoneyCheckFirstStart.');

//全量用户对账
$exceptionUserIds = array();
$maxUserId = $paymentCheckService->getMaxUserId();
$supervisionAccountService = new SupervisionAccountService();
$totalCount = 0;
for ($i = 1; $i <= $maxUserId; $i += 1000)
{
    $userIds = [];
    $users = UserModel::instance()->findAllViaSlave("id >= {$i} AND id < {$i}+1000 AND supervision_user_id>0", true, 'id, supervision_user_id');
    if (empty($users)) {
        usleep(10);
        continue;
    }
    foreach ($users as $user) {
        $userIds[] = $user['id'];
    }

    $totalCount += count($userIds);
    $p2pSupervisionMoney = UserThirdBalanceModel::instance()->getMultiUserSupervisionBalance($userIds);

    $supervisionUsersMoney = $paymentCheckService->getSupervisionMoney($userIds);
    foreach ($p2pSupervisionMoney as $userId => $moneyData)
    {
        $supervisionMoney = 0;
        if (isset($supervisionUsersMoney[$userId])) {
            $supervisionMoney = $supervisionUsersMoney[$userId];
        }
        // 用户余额、冻结金额为负数
        if (bccomp($moneyData['supervisionBalance'], '0.00', 2) < 0 || bccomp($moneyData['supervisionLockMoney'], '0.00', 2) < 0) {
            $exceptionUserIds[] = $userId;
            continue;
        }
        $supervisionBalance = bcdiv($supervisionMoney, 100, 2);
        // 余额对账
        if (bccomp($moneyData['supervisionBalance'], $supervisionBalance, 2) !== 0) {
            $exceptionUserIds[] = $userId;
            continue;
        }
    }
}

PaymentApi::log('UserSupervisonMoneyCheckSecondStart.');

//问题用户第二次对账
$exceptionUsers = array();
$specialUsers = array();
for ($i = 0; $i < count($exceptionUserIds); $i += 1000)
{
    $userIds = array_slice($exceptionUserIds, $i, 1000);
    $p2pSupervisionMoney = UserThirdBalanceModel::instance()->getMultiUserSupervisionBalance($userIds);
    $supervisionUsersMoney = $paymentCheckService->getSupervisionMoney($userIds);
    foreach ($p2pSupervisionMoney as $userId => $moneyData)
    {
        $userInfo = $GLOBALS['db']->getRow("SELECT real_name, id FROM firstp2p_user WHERE id='{$userId}'");
        $supervisionMoney = 0;
        if (isset($supervisionUsersMoney[$userId])) {
            $supervisionMoney = $supervisionUsersMoney[$userId];
        }
        // 用户余额、冻结金额为负数
        if (bccomp($moneyData['supervisionBalance'], '0.00', 2) < 0 || bccomp($moneyData['supervisionLockMoney'], '0.00', 2) < 0) {
            $specialUsers[$userId] = array('user' => $userInfo, 'desc' => '可用/冻结金额为负', 'money' => $moneyData['supervisionBalance'], 'lock_money' => $moneyData['supervisionLockMoney']);
            continue;
        }
        $supervisionBalance = bcdiv($supervisionMoney, 100, 2);
        if (bccomp($moneyData['supervisionBalance'], $supervisionBalance, 2) !== 0) {
            $diff = bcsub($moneyData['supervisionBalance'], $supervisionBalance, 2);
            $exceptionUsers[$userId] = array('user' => $userInfo, 'note' => '余额不相等', 'p2p' => $moneyData['supervisionBalance'], 'ucfpay' => $supervisionBalance, 'diff' => $diff);
            continue;
        }
    }
}

PaymentApi::log('UserSupervisionMoneyCheckSecondEnd.');

function userSort($a, $b) { return $a['diff'] < $b['diff']; }
//只在异常用户数少于1000的时候排序，防止占用内存过大
if (count($exceptionUsers) < 1000 && count($exceptionUsers) > 0)
{
    uasort($exceptionUsers, 'userSort');
}

PaymentApi::log('UserSupervisionMoneyCheckEnd. cost:'.round(microtime(true) - $startTime, 3));


//短信通知
$subject = date('Y年n月j日').'存管用户余额对账';
$content = "{$subject}. 总用户数:{$totalCount}, 异常个数:".count($exceptionUsers);

// 移除支付垫资账户
if (isset($exceptionUsers[8517874])) {
    unset($exceptionUsers[8517874]);
}

//发送邮件
$subject = date('Y年n月j日').'存管用户余额对账';
$body = '';
$body .= '<style>table { width:100%; margin:5px 0; background:#666; font-size:13px; border-spacing:1px; }
th { padding:5px; background:#698CC3; color:#fff; }
td { background:#F8F8F8; padding:5px 6px 3px 6px; }</style>';
$body .= "<h3>$subject</h3>";
$body .= '<ul style="color:#1f497d;">';
$body .= '<li>对账时间: '.date('H:i:s ~ ', $startTime).date('H:i:s').'</li>';
$body .= '<li>对账耗时: '.round(microtime(true) - $startTime).' 秒</li>';
$body .= '<li>总用户数: '.number_format($totalCount).' 个</li>';
$body .= '<li>异常个数: '.count($exceptionUsers).' 个</li>';
$body .= '</ul>';

if (!empty($exceptionUsers)) {
    $body .= '<b>异常用户详情(按差异额由大到小排序):</b>';
    $body .= '<table style="width:800px;">';
    $body .= '<tr>
        <th width="80">用户ID</td>
        <th width="100">姓名</td>
        <th width="120">P2P</th>
        <th width="120">存管</th>
        <th width="100">差异</th>
        <th>备注</th>
        </tr>';
    $count = 0;
    foreach ($exceptionUsers as $userId => $item)
    {
        if ($count++ >= MAX_SHOW_COUNT) {
            $body .= '<tr><td colspan="6">已省略显示更多异常用户</td></tr>';
            break;
        }

        $body .= "<tr>
            <td>{$userId}</td>
            <td>{$item['user']['real_name']}</td>
            <td>{$item['p2p']}</td>
            <td>{$item['ucfpay']}</td>
            <td><b>{$item['diff']}</b></td>
            <td>{$item['note']}</td>
            </tr>";
    }
    $body .= '</table>';
}


if (!empty($specialUsers)) {
    $body .= '<b>异常状态用户详情（可用或者冻结为负）<b>';
    $body .= '<table style="width:800px;">';
    $body .= '<tr>
        <th width="80">用户ID</td>
        <th width="100">姓名</td>
        <th width="170">可用金额</th>
        <th>备注</th>
        </tr>';
    $count = 0;
    foreach ($specialUsers as $userId => $item)
    {
        if ($count++ >= MAX_SHOW_COUNT) {
            $body .= '<tr><td colspan="6">已省略显示更多异常用户</td></tr>';
            break;
        }
        $body .= "<tr>
            <td>{$userId}</td>
            <td>{$item['user']['real_name']}</td>
            <td>{$item['money']}</td>
            <td>{$item['desc']}</td>
            </tr>";
    }

    $body .= '</table>';
}


//$msgcenter = new \Msgcenter();
//$mail = new NCFGroup\Common\Library\MailSendCloud();
// 20点对账邮件 只发理财研发
$runAt = date('H');
$mailAddress = ['wangqunqiang@ucfgroup.com'];
if ($runAt === '20')
{
    $mailAddress = ['wangqunqiang@ucfgroup.com','quanhengzhuang@ucfgroup.com','guofeng3@ucfgroup.com','luzhengshuai@ucfgroup.com'];
}
else
{
    $mailAddress = explode(',', app_conf('PAYMENT_USER_MONEY_CHECK_MAIL'));
}
// 微信群发
$wxRecieverNames = str_replace(',', '|', str_replace('@ucfgroup.com','',$mailAddress));
$result = file_get_contents('http://itil.firstp2p.com/api/weixin/sendText?to='.$wxRecieverNames.'&content='.urlencode($content).'&sms=1&appId=payment');
PaymentApi::log('UserSupervisionMoneyChecksms.end. ret:'.$result);

$mail = new Mail();
$mail->setFrom('noreply@unitedbank.cn', '海口联合农商银行');
$ret = $mail->send($subject, $body, $mailAddress);

PaymentApi::log('UserSupervisionMoneyCheck Result:'.json_encode($exceptionUsers));
PaymentApi::log('UserSupervisionMoneyCheckMailSend. ret:'.json_encode($ret));

\libs\utils\Script::end();
