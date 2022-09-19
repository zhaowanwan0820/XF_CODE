<?php
/**
 * 将指定数量的理财交易数据写入文件 数据格式[[姓名，身份证号，手机号](md5)，投资金额，投资时间]
 * @author <fanjingwen@ucfgroup.com>
 * @param [int] $data_count [要获取的记录条数]
 */
require_once dirname(__FILE__).'/../app/init.php';
set_time_limit(0);
ini_set('memory_limit', '2048M');

use core\dao\UserModel;
use core\dao\DealLoadModel;
use core\service\DealLoadService;

if (!isset($argv[1]) || !isset($argv[2])) {
    echo "接受两个参数：1、数据量；2、数据存储路径\n";
    exit(0);
}

$data_counts    = $argv[1]; // 要多少数据
$file_path      = $argv[2];  // 数据要存在哪

// 计算数据分块

$once_catch_counts = 10000;
$times = $data_counts/$once_catch_counts;
$block_size = intval(DealLoadModel::instance()->getLastInsertID()/$times);

// 打开文件，从数据库边读边写
$deal_load_service = new DealLoadService();
$content = file_get_contents($file_path);
for ($i = 0; $i < $times; ++$i) {
    $content .= $deal_load_service->getP2pUserDealDataFormat($i*$block_size, $once_catch_counts);
    file_put_contents($file_path, $content, FILE_APPEND);
}

echo "用户数据信息存储完成，文件路径：" . $file_path . "\n";
exit();