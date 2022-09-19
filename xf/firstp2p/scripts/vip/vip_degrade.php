<?php
/**
 * vip降级更新
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\PaymentApi;
use core\service\vip\VipService;

\libs\utils\Script::start();

$startTime = microtime(true);
$vipService = new VipService();

PaymentApi::log('vipDegrade start.');
//查询当天内要降级的vip(上个月进入保级状态的数据)
//调用降级逻辑

$relegateQueryEndTime = strtotime(date('Ym').'01');
$vipsql = 'SELECT user_id,service_grade,actual_grade,from_unixtime(relegate_time) as relegate_time FROM firstp2p_vip_account WHERE is_relegated = 1 and relegate_time<'.$relegateQueryEndTime . ' order by id asc ';
PaymentApi::log('vip降级sql:'.$vipsql);
$p2pdb = \libs\db\Db::getInstance('vip','master','utf8',1);
$result = $p2pdb->query($vipsql);
$vipService = new VipService();
$userArray = [];

while($result && ($data = $p2pdb->fetchRow($result))) {
    try{
        PaymentApi::log('vip降级data:'.json_encode($data,JSON_UNESCAPED_UNICODE));
        if ($data) {
            $vipService->degrade($data['user_id']);
            $userArray[]= $data;
        }
    } catch (\Exception $e) {
        PaymentApi::log('vip降级异常' . $e->getMessage());
    }
}

sendReport($userArray);
\libs\utils\Script::end();
function sendReport($result) {
    $currentDate = date('Y-m-d');
    $subject = $currentDate.'降级用户数据,总数'.count($result);
    $content = "<h3>$subject</h3>";
    $content .= "<table border=1 style='text-align: center'>";
    $content .= "<tr><th>用户id</th><th>服务等级</th><th>实际等级</th><th>保级时间</th></tr>";
    foreach($result as $item){
        $content .= "<tr><td> {$item['user_id']} </td><td>". $item['service_grade']. "</td><td>{$item['actual_grade']}</td><td>{$item['relegate_time']}</td></tr>";
    }
    $content .= "</table>";
    $mail = new \NCFGroup\Common\Library\MailSendCloud();
    $mailAddress = ['liguizhi@ucfgroup.com'];
    $ret = $mail->send($subject, $content, $mailAddress);
}
