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
use core\dao\UserGroupModel;

\libs\utils\Script::start();

$startTime = microtime(true);
$paymentCheckService = new \core\service\PaymentCheckService();

$statistics = array();

$statisticsByRole = array(
    'investAmount' => [
        'lock_money' => 0.00,
        'money' => 0.00,
        'minusmoney' => 0.00,
        'minuslock_money' => 0.00,
    ],
    'loanAmount' => [
        'lock_money' => 0.00,
        'money' => 0.00,
        'minusmoney' => 0.00,
        'minuslock_money' => 0.00,
    ],
);

define('MAX_SHOW_COUNT', 500);

PaymentApi::log('UserGroupMoneyCheck.');

//全量用户对账
$exceptionUserIds = array();
$maxUserId = $paymentCheckService->getMaxUserId();
for ($i = 1; $i < $maxUserId; $i += 1000)
{
    $userMoney = $paymentCheckService->getUserMoney("id BETWEEN {$i} AND {$i}+1000");

    foreach ($userMoney as $id => $userInfo)
    {
        statistics($userInfo, $statistics, $id, $statisticsByRole);
    }
}

/**
 * 初始化一个用户组数据
 */
function initGroupData()
{
    $statistics = array();
    $statistics['money'] = 0.00;
    $statistics['minusmoney'] = 0.00;
    $statistics['lock_money'] = 0.00;
    $statistics['minuslock_money'] = 0.00;
    $statistics['sum'] = 0.00;
    return $statistics;
}

/**
 * 统计每个用户组的用户可用余额
 */
function statistics($userInfo,&$statistics, $userId)
{
    $groupId = $userInfo['group_id'];
    if (!isset($statistics[$groupId]))
    {
        $statistics[$groupId] = initGroupData();
    }
    if (bccomp($userInfo['money'], '0.00', 2) >= 0)
    {
        // 余额为正的总数
        $statistics[$groupId]['money'] = bcadd($statistics[$groupId]['money'], $userInfo['money'], 2);
    }
    else
    {
        // 余额为负的总数
        $statistics[$groupId]['minusmoney'] = bcadd($statistics[$groupId]['minusmoney'], $userInfo['money'], 2);
    }

    if (bccomp($userInfo['lock_money'], '0.00', 2) >= 0)
    {
        // 冻结余额为正的总数
        $statistics[$groupId]['lock_money'] = bcadd($statistics[$groupId]['lock_money'], $userInfo['lock_money'], 2);
    }
    else
    {
        // 冻结资金为负的总数
        $statistics[$groupId]['minuslock_money'] = bcadd($statistics[$groupId]['minuslock_money'], $userInfo['lock_money'], 2);
    }
    // 用户资金总额
    $statistics[$groupId]['sum'] = bcadd($statistics[$groupId]['sum'], $userInfo['sum'], 2);
}

// 读取组名称配置
$groups = UserGroupModel::instance()->findAllViaSlave(' 1 = 1 ','id,name');
// 组索引
$groupNameIndex = array();
foreach ($groups as $group)
{
    $groupNameIndex[$group['id']] = $group['name'];
}
// 构造html表单,顺带计算平台总额
$platformSum = array();
$platformSum['money'] = 0;
$platformSum['minusmoney'] = 0;
$platformSum['lock_money'] = 0;
$platformSum['minuslock_money'] = 0;
$platformSum['sum'] = 0;

$body = "<style>.table-c table{border-right:1px solid #ccc;border-bottom:1px solid #ccc}.table-c table td{border-left:1px solid #ccc;border-top:1px solid #ccc}</style>";
$body .= '截止'.date('Y-m-d').' 00:00:00用户账户总余额统计';
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
foreach ($groupNameIndex as $groupId => $groupName)
{
    $groupData = array();
    if (isset($statistics[$groupId]))
    {
        $groupData = $statistics[$groupId];
    }
    else
    {
        $groupData = initGroupData();
    }
    $body .= <<<TABLETD
<tr>
    <td>{$groupNameIndex[$groupId]}</td>
    <td>{$groupData['money']}</td>
    <td>{$groupData['minusmoney']}</td>
    <td>{$groupData['lock_money']}</td>
    <td>{$groupData['minuslock_money']}</td>
    <td>{$groupData['sum']}</td>
</tr>
TABLETD;
    $platformSum['money'] = bcadd($platformSum['money'], $groupData['money'], 2);
    $platformSum['minusmoney'] = bcadd($platformSum['minusmoney'], $groupData['minusmoney'], 2);
    $platformSum['lock_money'] = bcadd($platformSum['lock_money'], $groupData['lock_money'], 2);
    $platformSum['minuslock_money'] = bcadd($platformSum['minuslock_money'], $groupData['minuslock_money'], 2);
    $platformSum['sum'] = bcadd($platformSum['sum'], $groupData['sum'], 2);
}
// 合计
    $body .= <<<DOS
<tr>
    <td>合计</td>
    <td>{$platformSum['money']}</td>
    <td>{$platformSum['minusmoney']}</td>
    <td>{$platformSum['lock_money']}</td>
    <td>{$platformSum['minuslock_money']}</td>
    <td>{$platformSum['sum']}</td>
</tr>
DOS;

$user = explode(',', app_conf('PAYMENT_REPORT_USER_ID'));
if (empty($user)) {
    PaymentApi::log(__CLASS__ .'_UserId is empty.');
    exit;
}
PaymentApi::log(__CLASS__ .'_UserId: '. json_encode($user));

foreach ($user as $usersId)
{
    $users = $paymentCheckService->getUserMoney('id = '.$usersId);
    if (isset($users[$usersId])) {
        $userInfo= $users[$usersId];
        $body .= <<<DOS
        <tr>
            <td>{$usersId}</td>
            <td>可用余额:{$userInfo['money']}</td>
            <td>-</td>
            <td>{$userInfo['lock_money']}</td>
            <td>-</td>
            <td>{$userInfo['sum']}</td>
        </tr>
DOS;
    }
}

$body .= '</table></div>'.PHP_EOL;

// 发邮件
$msgcenter = new Msgcenter();
$subject = "firstp2p用户总余额";
$mailAddress = app_conf('PAYMENT_USER_TOTAL_MAIL');
$msgcenter->setMsg($mailAddress, 0, $body, false, $subject);
$ret = $msgcenter->save();
PaymentApi::log('UserGroupMoneyCheckEnd. ret:'.$ret);

\libs\utils\Script::end();
