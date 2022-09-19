<?php
/**
 * vip交易返利
 * php scripts/vip_rebate_rate.php [20170731]
 * 可指定执行某一天的加息返利脚本,不传则执行当天
 *
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\PaymentApi;
use core\service\vip\VipService;
use core\service\DealService;
use core\dao\DealModel;
use NCFGroup\Protos\Ptp\Enum\VipEnum;


\libs\utils\Script::start();

$startTime = microtime(true);

PaymentApi::log('vipRabateRate start.');
//查询当天放款的标的列表[脚本在晚10点后执行]
//根据标的列表获取当天的所有交易信息
//逐条处理返利
$repayTime = strtotime(date('Y-m-d')) - 8*3600;
$rebateTime  = time();
$daySpan = 1;//默认只执行一天的返利
if ($argc > 1) {
    //如果指定了返利日期
    $rateDate = $argv[1];
    $rebateTime = strtotime($rateDate);
    $daySpan = isset($argv[2]) ? (int)$argv[2] : $daySpan;
    $repayTime = strtotime($rateDate) - 86400 * $daySpan - 8 * 3600;
} 

$dealSql = 'SELECT id, deal_type FROM firstp2p_deal WHERE repay_start_time>='.$repayTime. ' AND repay_start_time<'.($repayTime + 86400 * $daySpan) . ' order by id asc';
$p2pdb = \libs\db\Db::getInstance('firstp2p','master','utf8',1);
$result = $p2pdb->query($dealSql);
$vipService = new VipService();
$dealService = new DealService();
PaymentApi::log('vip返利:sql:' . $dealSql);

$rebateResult['totalAmount'] = 0;
$rebateResult['count'] = 0;

while($result && ($data = $p2pdb->fetchRow($result))) {
    PaymentApi::log('vip返利标的' . $data['id'].'|dealInfo|'.json_encode($data,JSON_UNESCAPED_UNICODE));
    //排除多投的标的
    if ($dealService->isDealDT($data['id'])) {
        PaymentApi::log('vip返利:多投标的' . $data['id']);
        continue;
    }

    $dealLoadSql = 'SELECT * FROM firstp2p_deal_load WHERE deal_id='.$data['id'] . ' order by id asc';
    $dealRes = $p2pdb->query($dealLoadSql);
    while($dealRes && ($dealLoadInfo = $p2pdb->fetchRow($dealRes))) {
        try{
            $sourceType = ($data['deal_type'] == DealModel::DEAL_TYPE_GENERAL) ? VipEnum::VIP_SOURCE_P2P : VipEnum::VIP_SOURCE_ZHUANXIANG;
            $log = $vipService->vipRebateRate($dealLoadInfo, $rebateTime, $sourceType);
            if (isset($log['allowance_money'])) {
                $rebateResult['totalAmount'] += $log['allowance_money'];
                $rebateResult['count']++;
            }
        } catch (\Exception $e) {
            PaymentApi::log('vip返利异常'.'userId|'.$dealLoadInfo['user_id'].'|dealLoadId'. $dealLoadInfo['id'] . $e->getMessage());
            continue;
        }
    }
}
sendReport($rebateResult);
\libs\utils\Script::end();

function sendReport($result) {
    $currentDate = date('Y-m-d');
    $subject = $currentDate.'vip投资p2p&专享加息统计';
    $content = "<h3>$subject</h3>";
    $content .= "<table border=1 style='text-align: center'>";
    $content .= "<tr><th>日期</th><th>加息总金额</th><th>加息记录数</th></tr>";
    $content .= "<tr><td> {$currentDate} </td><td>". $result['totalAmount']. "</td><td>{$result['count']}</td></tr>";
    $content .= "</table>";
    $mail = new \NCFGroup\Common\Library\MailSendCloud();
    $mailAddress = ['liguizhi@ucfgroup.com'];
    $ret = $mail->send($subject, $content, $mailAddress);
}
