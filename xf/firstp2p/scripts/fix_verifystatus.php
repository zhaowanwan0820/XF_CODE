<?php
/**
 * 银行名称修复工具
 * 每天晚上11点开始执行,修复用户已绑卡但是银行名称为空的问题
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ERROR);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

define('__DEBUG', false);

use core\dao\UserModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;
use core\service\UserCreditService;
ini_set('memory_limit', '2048M');
set_time_limit(0);

$argv = $_SERVER['argv'];

$startId = !empty($argv[1]) ? intval($argv[1]) : 0;
$endId = !empty($argv[2]) ? intval($argv[2]) : 0;

$processId = 619343;

if ($startId <= $processId)
{
    $startId = $processId;
}

if ($startId == 0 or $endId == 0)
{
    exit('请输入大于0的起始和结束ID');
}


$bankcardModel = new UserBankcardModel();
$creditService = new UserCreditService();
$list = $GLOBALS['db']->get_slave()->getCol("SELECT user_id FROM `firstp2p_user_bankcard` WHERE verify_status = 0 AND id > '{$startId}' AND id <= '{$endId}'");
$timestamp = time();
$count = 0;
if (is_array($list))
{
    foreach ($list as $userId)
    {
        $isCredible = $creditService->isCredible($userId);
        if ($isCredible === true)
        {
            $sql = "UPDATE firstp2p_user_bankcard SET verify_status = 1,update_time = '{$timestamp}' WHERE user_id = '{$userId}'";
            $count ++;
            $GLOBALS['db']->query($sql);
        }
    }
}
$content = '修复完成,总共修复了'.$count.'个用户';
$mobiles = array('18611187809');
 \libs\sms\SmsServer::sendAlertSms($mobiles,$content);


