<?php
/**
 * 用户余额核对
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/../system/utils/es_mail.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';
FP::import("libs.common.dict");

use libs\utils\PaymentApi;
use core\service\PaymentCheckService;
//use Ncfgroup\Itil\Plugins\Weixin;

\libs\utils\Script::start();

$startTime = microtime(true);
$paymentCheckService = new \core\service\PaymentCheckService();

define('MAX_SHOW_COUNT', 500);

PaymentApi::log('UserMoneyCheckFirstStart.');

//全量用户对账
$exceptionUserIds = array();
$maxUserId = $paymentCheckService->getMaxUserId();
for ($i = 1; $i <= $maxUserId; $i += 1000)
{
    $userMoney = $paymentCheckService->getUserMoney("id BETWEEN {$i} AND {$i}+1000 AND payment_user_id>0");
    $ucfpayMoney = $paymentCheckService->getUcfpayMoney(array_keys($userMoney));

    foreach ($userMoney as $id => $moneyData)
    {
        if (!isset($ucfpayMoney[$id])) {
            $exceptionUserIds[] = $id;
            continue;
        }

        // 用户余额、冻结金额为负数
        if (bccomp($moneyData['money'], '0.00', 2) < 0 || bccomp($moneyData['lock_money'], '0.00', 2) < 0) {
            $exceptionUserIds[] = $id;
            continue;
        }

        // 总余额对账
        if (bccomp($moneyData['sum'], $ucfpayMoney[$id], 2) !== 0) {
            $exceptionUserIds[] = $id;
            continue;
        }
    }
}

PaymentApi::log('UserMoneyCheckSecondStart.');

//问题用户第二次对账
$result = array();
for ($i = 0; $i < count($exceptionUserIds); $i += 1000)
{
    $userIds = array_slice($exceptionUserIds, $i, 1000);

    $userMoney = $paymentCheckService->getUserMoney('id IN ('.implode(',', $userIds).')');
    $ucfpayMoney = $paymentCheckService->getUcfpayMoney(array_keys($userMoney));

    foreach ($userMoney as $id => $moneyData)
    {
        $userInfo = $GLOBALS['db']->getRow("SELECT real_name, id FROM firstp2p_user WHERE id='{$id}'");

        if (!isset($ucfpayMoney[$id])) {
            $result[$id] = array('user' => $userInfo, 'note' => '用户查询失败', 'p2p' => $moneyData['sum'], 'ucfpay' => '-', 'diff' => '-');
            continue;
        }

        // 用户余额、冻结金额为负数
        if (bccomp($moneyData['money'], '0.00', 2) < 0 || bccomp($moneyData['lock_money'], '0.00', 2) < 0) {
            $result[$id] = array('user' => $userInfo, 'desc' => '可用/冻结金额为负', 'money' => $moneyData['money'], 'lock_money' => $moneyData['lock_money'], 'diff' => '-');
            continue;
        }

        if (bccomp($moneyData['sum'], $ucfpayMoney[$id], 2) !== 0) {
            $diff = bcsub($moneyData['sum'], $ucfpayMoney[$id], 2);
            $result[$id] = array('user' => $userInfo, 'note' => '余额不相等', 'p2p' => $moneyData['sum'], 'ucfpay' => $ucfpayMoney[$id], 'diff' => $diff);
            continue;
        }
    }
}

PaymentApi::log('UserMoneyCheckSecondEnd.');

function userSort($a, $b) { return $a['diff'] < $b['diff']; }
//只在异常用户数少于1000的时候排序，防止占用内存过大
if (count($result) < 1000)
{
    uasort($result, 'userSort');
}

PaymentApi::log('UserMoneyCheckEnd. cost:'.round(microtime(true) - $startTime, 3));

$exceptionUsers = array();
$fundUsers = array();
$specialUsers = array();
foreach ($result as $id => $item)
{
    $item['note'] = $paymentCheckService->getUserNote($id);
    if ($item['note']['fund'] != 0 && bccomp($item['note']['fund'], $item['diff'], 2) === 0) {
        $fundUsers[$id] = $item;
        continue;
    }
    // 处理余额冻结为负，或者总和为0的用户
    if (isset($item['money']) && isset($item['lock_money'])) {
        $specialUsers[$id] = $item;
        continue;
    }
    $exceptionUsers[$id] = $item;
}

//写入到itil库中
$paymentCheckService->batchInsert($exceptionUsers);
PaymentApi::log('UserMoneyCheckInsertDb.');

//短信通知
$subject = date('Y年n月j日').'资金账户平台用户余额对账';
$content = "{$subject}. 总用户数:{$maxUserId}, 异常个数:".count($exceptionUsers).", 基金相关:".count($fundUsers).".";

//$recipients = explode(',', str_replace('@ucfgroup.com', '', app_conf('PAYMENT_USER_MONEY_CHECK_MAIL')));
//$wxResult = Weixin::instance()->sendText("1000002", $recipients, $subject.$content);
PaymentApi::log('UserMoneyChecksms.end. result:'.json_encode($exceptionUsers));
// 跳过易宝中转账户
$yeepayUserId = app_conf('YEEPAY_TRANFER_UID');
if (isset($exceptionUsers[$yeepayUserId])) {
    unset($exceptionUsers[$yeepayUserId]);
}
//发送邮件
$subject = date('Y年n月j日').'资金账户平台用户余额对账';
$body = '';
$body .= '<style>table { width:100%; margin:5px 0; background:#666; font-size:13px; border-spacing:1px; }
th { padding:5px; background:#698CC3; color:#fff; }
td { background:#F8F8F8; padding:5px 6px 3px 6px; }</style>';
$body .= "<h3>$subject</h3>";
$body .= '<ul style="color:#1f497d;">';
$body .= '<li>对账时间: '.date('H:i:s ~ ', $startTime).date('H:i:s').'</li>';
$body .= '<li>对账耗时: '.round(microtime(true) - $startTime).' 秒</li>';
$body .= '<li>总用户数: '.number_format($maxUserId).' 个</li>';
$body .= '<li>异常个数: '.count($exceptionUsers).' 个 (不含基金相关'.count($fundUsers).'个)</li>';
$body .= '</ul>';

$body .= '<b>异常用户详情(按差异额由大到小排序):</b>';
$body .= '<table style="width:800px;">';
$body .= '<tr>
    <th width="80">用户ID</td>
    <th width="100">姓名</td>
    <th width="120">P2P总额</th>
    <th width="120">支付总额</th>
    <th width="100">差异</th>
    <th>备注</th>
    </tr>';
$count = 0;
foreach ($exceptionUsers as $id => $item)
{
    if ($count++ >= MAX_SHOW_COUNT) {
        $body .= '<tr><td colspan="6">已省略显示更多异常用户</td></tr>';
        break;
    }

    $body .= "<tr>
        <td>{$id}</td>
        <td>{$item['user']['real_name']}</td>
        <td>{$item['p2p']}</td>
        <td>{$item['ucfpay']}</td>
        <td><b>{$item['diff']}</b></td>
        <td>{$item['note']['note']}</td>
        </tr>";
}
$body .= '</table>';

$body .= '<b>基金相关异常用户详情(按差异额由大到小排序):</b>';
$body .= '<table style="width:800px;">';
$body .= '<tr>
    <th width="80">用户ID</td>
    <th width="100">姓名</td>
    <th width="120">P2P总额</th>
    <th width="120">支付总额</th>
    <th width="100">差异</th>
    <th>备注</th>
    </tr>';
$count = 0;
foreach ($fundUsers as $id => $item)
{
    if ($count++ >= MAX_SHOW_COUNT) {
        $body .= '<tr><td colspan="6">已省略显示更多异常用户</td></tr>';
        break;
    }
    $body .= "<tr>
        <td>{$id}</td>
        <td>{$item['user']['real_name']}</td>
        <td>{$item['p2p']}</td>
        <td>{$item['ucfpay']}</td>
        <td><b>{$item['diff']}</b></td>
        <td>{$item['note']['note']}</td>
        </tr>";
}

$body .= '</table>';

$body .= '<b>异常状态用户详情（可用或者冻结为负以及可用加冻结为0）<b>';
$body .= '<table style="width:800px;">';
$body .= '<tr>
    <th width="80">用户ID</td>
    <th width="100">姓名</td>
    <th width="170">可用金额</th>
    <th width="170">冻结金额</th>
    <th>备注</th>
    </tr>';
$count = 0;
foreach ($specialUsers as $id => $item)
{
    if ($count++ >= MAX_SHOW_COUNT) {
        $body .= '<tr><td colspan="6">已省略显示更多异常用户</td></tr>';
        break;
    }
    $body .= "<tr>
        <td>{$id}</td>
        <td>{$item['user']['real_name']}</td>
        <td>{$item['money']}</td>
        <td>{$item['lock_money']}</td>
        <td>{$item['desc']}</td>
        </tr>";
}

$body .= '</table>';


$msgcenter = new Msgcenter();
// 20点对账邮件 只发理财研发
$runAt = date('H');
$mailAddress = 'wangqunqiang@ucfgroup.com';
if ($runAt === '20')
{
    $mailAddress = 'wangqunqiang@ucfgroup.com,quanhengzhuang@ucfgroup.com,guofeng3@ucfgroup.com,luzhengshuai@ucfgroup.com';
}
else
{
    $mailAddress = app_conf('PAYMENT_USER_MONEY_CHECK_MAIL');
}
// 微信群发
$wxRecieverNames = str_replace(',', '|', str_replace('@ucfgroup.com','',$mailAddress));
$result = file_get_contents('http://itil.firstp2p.com/api/weixin/sendText?to='.$wxRecieverNames.'&content='.urlencode($content).'&sms=1&appId=payment');
PaymentApi::log('UserMoneyChecksms&WX.end. ret:'.json_encode($result));
$msgcenter->setMsg($mailAddress, 0, $body, false, $subject);
$ret = $msgcenter->save();
PaymentApi::log('UserMoneyCheck Result:'.var_export($exceptionUsers, true));
PaymentApi::log('UserMoneyCheckMailSend. ret:'.$ret);

\libs\utils\Script::end();
