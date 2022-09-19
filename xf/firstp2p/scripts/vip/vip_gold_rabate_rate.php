<?php
/**
 * vip 优长金交易返利
 * php scripts/vip_gold_rebate_rate.php
 *
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\PaymentApi;
use core\service\vip\VipService;
use core\service\DealService;
use core\service\GoldService;

\libs\utils\Script::start();

$startTime = microtime(true);

PaymentApi::log('vipGoldRabateRate start.'.date('Y-m-d H:i:s'));
//查询前一天放款的gold标的列表
//根据标的列表获取当天的所有交易信息
//逐条处理返利
$rebateTime  = strtotime(date('Y-m-d'));

$vipService = new VipService();
$goldService = new GoldService();
$loanIds = $goldService->getLoanDealIds();
$result['totalAmount'] = 0;
$result['count'] = 0;

if ($loanIds) {
    PaymentApi::log('vip_gold返利:gold标的ids' . json_encode($loanIds));
    foreach ($loanIds as $dealId) {
        $dealRes = $goldService->getDealById($dealId);
        $dealInfo = $dealRes['data'];
        PaymentApi::log('vip_gold返利:gold dealInfo' . json_encode($dealInfo, JSON_UNESCAPED_UNICODE));
        //get full loads
        $dealLoadsRes = $goldService->getDealLog($dealId, true);
        $dealLoads = $dealLoadsRes['data'];
        if ($dealLoads) {
            foreach($dealLoads as $dealLoadInfo) {
                try{
                    PaymentApi::log('vip_gold返利:gold dealLoadInfo' . json_encode($dealLoadInfo, JSON_UNESCAPED_UNICODE));
                    $log = $vipService->vipGoldRebateRate($dealLoadInfo['userId'], $dealLoadInfo['id'], $dealLoadInfo['money'], $dealInfo['loantype'], strtotime($dealLoadInfo['createTime']), $dealInfo['repayTime'], $rebateTime);
                    if (isset($log['allowance_money'])) {
                        $result['totalAmount'] += $log['allowance_money'];
                        $result['count']++;
                    }
                } catch (\Exception $e) {
                    PaymentApi::log('vip_gold返利:ERR:' . $e->getMessage());
                }
            }
        }
    }
}
sendReport($result);
PaymentApi::log('vipGoldRabateRate end.'.date('Y-m-d H:i:s'));
\libs\utils\Script::end();

function sendReport($result) {
    $currentDate = date('Y-m-d');
    $subject = $currentDate.'vip优长金加息统计';
    $content = "<h3>$subject</h3>";
    $content .= "<table border=1 style='text-align: center'>";
    $content .= "<tr><th>日期</th><th>加息总金额</th><th>加息记录数</th></tr>";
    $content .= "<tr><td> {$currentDate} </td><td>". $result['totalAmount']. "</td><td>{$result['count']}</td></tr>";
    $content .= "</table>";
    $mail = new \NCFGroup\Common\Library\MailSendCloud();
    $mailAddress = ['liguizhi@ucfgroup.com'];
    $ret = $mail->send($subject, $content, $mailAddress);
}
