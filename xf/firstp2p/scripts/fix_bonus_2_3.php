<?php
require(dirname(__FILE__) . '/../app/init.php');
require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
use core\dao\BonusModel;
use core\dao\UserModel;
error_reporting(0);
set_time_limit(0);

$whiteList = array(
1668211 => 0,
1682026 => 0,
1650744 => 0,
1683442 => 0,
1678814 => 0,
1682865 => 0,
1682249 => 0,
1609748 => 0,
1675372 => 0,
1683183 => 0,
1683205 => 0,
1683666 => 0,
1681777 => 0,
1679531 => 0,
1491772 => 0,
1683304 => 0,
1683648 => 0,
1683198 => 0,
1682741 => 0,
1683694 => 0,
1581147 => 0,
1682363 => 0,
1680512 => 0,
1682464 => 0,
1646730 => 0,
1594017 => 0,
1587885 => 0,
1683184 => 0,
1611231 => 0,
1677927 => 0,
1679550 => 0,
1655401 => 0,
1683833 => 0,
1681073 => 0,
1678463 => 0,
1524595 => 0,
1670636 => 0,
1683936 => 0,
1496558 => 0,
1682208 => 0,
1684076 => 0,
1684327 => 0,
1684268 => 0,
1533376 => 0,
1681009 => 0,
1682315 => 0,
1683925 => 0,
1683676 => 0,
1682225 => 0,
1536786 => 0,
1675309 => 0,
1682084 => 0,
1446014 => 0,
1684023 => 0,
1684436 => 0,
1511779 => 0,
1682402 => 0,
1682745 => 0,
1592991 => 0,
1682853 => 0,
1683900 => 0,
1682814 => 0,
1682075 => 0,
1681660 => 0,
1684520 => 0,
1654092 => 0,
1558823 => 0,
1683511 => 0,
1683412 => 0,
1684557 => 0,
1684012 => 0,
1683334 => 0,
1684460 => 0,
1681266 => 0,
1528550 => 0,
1602183 => 0,
1682276 => 0,
1670772 => 0,
1539534 => 0,
1670183 => 0,
1683376 => 0,
1684069 => 0,
1684746 => 0,
1684994 => 0,
1684555 => 0,
1683456 => 0,
1608896 => 0,
1226061 => 0,
1682874 => 0,
1684962 => 0,
1561848 => 0,
1684630 => 0,);
$startTime = strtotime('2015-04-07 21:28:50');
$endTime = strtotime('2015-04-08 10:00:00');
$sql = "SELECT owner_uid, money, mobile, type, refer_mobile FROM firstp2p_bonus WHERE type IN (1,2) AND created_at >= $startTime AND created_at <= $endTime AND money IN (20,30,40,60)";
$moneyResend = array(20 => 30, 30 => 20, 40 => 60, 60 => 40);
$result = $GLOBALS['db']->query($sql);
while($result && $data = $GLOBALS['db']->fetchRow($result)) {
    if (isset($whiteList[$data['owner_uid']])) {
        continue;
    }
    $money = intval($data['money']);
    $currentTime = time();
    $expiredTime = $currentTime + 86400;
    $user = UserModel::instance()->find($data['owner_uid'], 'id,user_name', true);
    $res = BonusModel::instance()->single_bonus(0, 0, $data['owner_uid'], $data['mobile'], 1, $moneyResend[$money], $currentTime, $expiredTime, NULL, $data['refer_mobile'], $data['type']);
    if ($res) {
        $msgcenter = new \Msgcenter();
        $params = array($moneyResend[$money]);
        $msgcenter->setMsg($data['mobile'], $data['owner_uid'], $params, 'TPL_SMS_BONUS_FIRST_DEAL_RESEND', '首投红包补发');
        $msgcenter->save();
        echo "SUCCESS\t". $data['owner_uid'] ."\t". $user['user_name'] ."\t". $data['mobile']. PHP_EOL;
    } else {
        echo "FAIL\t". $data['owner_uid'] ."\t". $user['user_name'] ."\t".$data['mobile']. PHP_EOL;
    }
}
