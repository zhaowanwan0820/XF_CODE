<?php

require(dirname(__DIR__) . '/app/init.php');

$query = base64_decode(@$argv[1]);
if (empty($query)) {
    echo "输入参数错误!" . PHP_EOL;
    exit(255);
}

try {
    $db = \libs\db\Db::getInstance('firstp2p', 'master');
    $db->query($query);
    echo "影响数据行数: " . $db->affected_rows() . PHP_EOL;
} catch (\Exception $e) {
    echo "修复数据发生错误!" . PHP_EOL;
    exit(255);
}
