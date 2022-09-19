<?php

require dirname(__FILE__).'/../../app/init.php';

use libs\utils\Logger;
use libs\db\Db;

Logger::info("candy_auto_update_coupon. begin.");
$db = Db::getInstance('candy');
$couponInfo = $db->getAll("SELECT * FROM candy_shop_product WHERE status=1 AND is_limited=1 AND daily_stock>0");
foreach ($couponInfo as $key => $val) {
    $where = "id = " . $val['id'];
    $data = [
        'update_time' => time(),
        'stock' => $val['daily_stock'],
    ];
    $db->update('candy_shop_product', $data, $where);
}
Logger::info("candy_auto_update_coupon. finished. update quantity:" . count($couponInfo));
