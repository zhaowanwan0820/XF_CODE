<?php

ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\PaymentApi;

\libs\utils\Script::start();

$startTime = microtime(true);

PaymentApi::log('Account Check start.');

$accountList = explode(',', app_conf('ACCOUNT_CHECK_LIST'));
// $accountList = ['2064710'];

$date = $argv[1];
if (empty($date)) {
    $date = date('Y-m-d', strtotime('-1 day'));
}
$startTime = strtotime($date) - 28800;
$endTime = $startTime + 86400 - 1;

// $startTime = 0;

$count = 10000;

$path = "/tmp/";
$ftpPath = '/apps/product/userlog/';
//写入列名
$mailTitle = ["日期时间","账户操作类型","金额","备注"];
$title = ['ID', "日期时间","账户操作类型","金额","备注"];

$title = implode(',', $title);
$mailTitle = implode(',', $mailTitle);

$db = \libs\db\Db::getInstance('firstp2p', 'slave');
foreach ($accountList as $accountId) {
    $totalMoney  = 0;
    $fileName = "{$date}_{$accountId}";
    $mailFileName = "transferlog_{$date}_{$accountId}";
    $filePath = $ftpPath. "{$fileName}.csv";
    $mailFilePath = $path . "{$mailFileName}.csv";
    $fp = fopen($filePath, "w+");
    $mailFp = fopen($mailFilePath, "w+");
    fputcsv($fp, explode(',', $title));
    fputcsv($mailFp, explode(',', $mailTitle));
    $tableId = $accountId % 64;
    $table = 'firstp2p_user_log_'.$tableId;
    $sqlCnt = sprintf('SELECT COUNT(id) AS total FROM %s WHERE log_user_id = "%s" and log_time BETWEEN %s AND %s and log_info = "红包充值"', $table, $accountId, $startTime, $endTime);
    $sqlData = sprintf('SELECT id, money, note, from_unixtime(log_time+28800) as log_time FROM %s WHERE log_user_id = "%s" and log_time BETWEEN %s AND %s and log_info = "红包充值" order by id ASC', $table, $accountId, $startTime, $endTime);

    $totalCnt = $db->getRow($sqlCnt);

    $totalCnt = $totalCnt['total'];

    $pages = ceil($totalCnt / $count);
    for($page = 0; $page < $pages; $page++) {
        $sqlData = sprintf('%s LIMIT %s, %s', $sqlData, $page * $count, $count);
        $list = $db->getAll($sqlData);
        foreach ($list as $row) {
            $money = abs($row['money']);
            $totalMoney = bcadd($totalMoney, $money, 2);
            fputcsv($mailFp, explode(',', sprintf("%s,%s,%s,%s", $row['log_time'], '红包充值', $money, $row['note'])));
            //fputcsv($fp, explode(',', sprintf("%s,%s,%s,%s,%s", "LOG{$tableId}_{$row['id']}", $row['log_time'], '红包充值', $money, $row['note'])));
            fwrite($fp, sprintf("%s,%s,%s,%s,%s\n", "LOG{$tableId}_{$row['id']}", $row['log_time'], '红包充值', $money, $row['note']));
        }
    }
    fclose($fp);
    fclose($mailFp);
    sendAccountCheckMail($date, number_format($totalMoney, 2), ['path' => $mailFilePath, 'name' => $mailFileName]);
}

\libs\utils\Script::end();

function sendAccountCheckMail($date, $totalMoney, $file)
{
    $subject = $date.'红包账户资金流水';
    $content = "<h3>$subject</h3>";
    $content .= "<table border=1 style='text-align: center'>";
    $content .= "<tr><th>总金额</th></tr>";
    $content .= "<tr><td> {$totalMoney} 元</td></tr>";
    $content .= "</table>";
    $mail = new \NCFGroup\Common\Library\MailSendCloud();
    $mailList = app_conf('BONUS_ACCOUNT_CHECK_MAIL_LIST') ?: 'wangshijie@ucfgroup.com';
    $mailList = explode(',', $mailList);
    $ret = $mail->send($subject, $content, $mailList, [$file]);
}
