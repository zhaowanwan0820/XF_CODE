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
        statistics($userInfo, $id, $statisticsByRole);
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
function statistics($userInfo,$userId, &$statisticsByRole)
{
    // 借款人、投资人维度统计用户
    $db = \libs\db\Db::getInstance('firstp2p','slave');
    $id = $db->getOne("SELECT id FROM firstp2p_deal WHERE user_id = '{$userId}' LIMIT 1");
    $statisticsKey = 'loanAmount';
    if (empty($id)) {
        $statisticsKey = 'investAmount';
    }
    if ($userInfo['money'] >= 0) {
        $statisticsByRole[$statisticsKey]['money'] = bcadd($statisticsByRole[$statisticsKey]['money'], $userInfo['money'], 2);
    } else {
        $statisticsByRole[$statisticsKey]['minusmoney'] = bcadd($userInfo['money'],$statisticsByRole[$statisticsKey]['minusmoney'],2);
    }
    if ($userInfo['lock_money'] >= 0) {
        $statisticsByRole[$statisticsKey]['lock_money'] = bcadd($userInfo['lock_money'], $statisticsByRole[$statisticsKey]['lock_money'], 2);
    } else {
        $statisticsByRole[$statisticsKey]['minuslock_money'] = bcadd($userInfo['lock_money'], $statisticsByRole[$statisticsKey]['minuslock_money'], 2);
    }
}

$body .= <<<TABLETH
<div>
<table>
<tr>
    <th>统计维度</th>
    <th>正余额</th>
    <th>负余额</th>
    <th>正冻结</th>
    <th>负冻结</th>
</tr>
TABLETH;
    $body .= <<<DOS
<tr>
    <td>投资人</td>
    <td>{$statisticsByRole['investAmount']['money']}</td>
    <td>{$statisticsByRole['investAmount']['minusmoney']}</td>
    <td>{$statisticsByRole['investAmount']['lock_money']}</td>
    <td>{$statisticsByRole['investAmount']['minuslock_money']}</td>
</tr>
<tr>
    <td>借款人</td>
    <td>{$statisticsByRole['loanAmount']['money']}</td>
    <td>{$statisticsByRole['loanAmount']['minusmoney']}</td>
    <td>{$statisticsByRole['loanAmount']['lock_money']}</td>
    <td>{$statisticsByRole['loanAmount']['minuslock_money']}</td>
</tr>
DOS;
$body .= '</table></div>'.PHP_EOL;
echo $body;

// 发邮件
$msgcenter = new Msgcenter();
$subject = "firstp2p用户总余额-按照投资人/借款人维度";
$mailAddress = app_conf('PAYMENT_USER_TOTAL_MAIL');
$msgcenter->setMsg($mailAddress, 0, $body, false, $subject);
$ret = $msgcenter->save();
PaymentApi::log('UserGroupMoneyByRoleCheckEnd. ret:'.$ret);

\libs\utils\Script::end();
