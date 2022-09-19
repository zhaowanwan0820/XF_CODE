<?php
/**
 * 批量发短信接口
 * 用户ID文件不要超过100万行
 * 使用方式: php batch_sms_send.php userid.txt 300
 */
set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once(dirname(__FILE__).'/../../app/init.php');
\libs\utils\Script::start();

use libs\db\Db;
use libs\utils\Process;
use libs\sms\WeilaiSms;

if (Process::exists($_SERVER['PHP_SELF'])) {
    exit("进程已经启动\n");
}

//短信内容
$content = '尊敬的用户：“网信理财”品牌升级为“网信”，明确定位为金融科技开放平台。升级后，您在平台上已经达成的各项投融资交易及签署的相关合同效力均不受影响，请您放心。我们将竭诚为您提供更丰富的产品服务和更好的用户体验，感谢您的支持和信任。【网信理财】';

//每次发送条数
$COUNT_ONE_TIME = isset($argv[2]) ? intval($argv[2]) : 500;

//用户ID文件
$filename = $argv[1];
if (!is_file($filename)) {
    exit('用户ID文件没传或不存在');
}

$ids = file_get_contents($filename);
$ids = explode("\n", trim($ids));
$ids = array_chunk($ids, $COUNT_ONE_TIME);

//短信发送
$sms = new WeilaiSms();
foreach ($ids as $userIds) {
    $sql = "SELECT mobile FROM firstp2p_user WHERE id IN ('".implode("','", $userIds)."')";
    $result = Db::getInstance('firstp2p', 'slave')->getAll($sql);

    $mobiles = array();
    foreach ($result as $item) {
        $mobiles[] = $item['mobile'];
    }

    $ret = $sms->sendVerify(implode(',', $mobiles), $content);
    \libs\utils\Script::log("batch_sms_send. count:{$COUNT_ONE_TIME}, ret:{$ret}, userIds:".implode(',', $userIds));
}

\libs\utils\Script::end();
