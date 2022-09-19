<?php
/**
 * 异步刷新cache数据
 */

use libs\utils\Logger;
use core\dao\DealModel;
use core\dao\DealSiteModel;
require_once dirname(__FILE__).'/../app/init.php';
set_time_limit(0);

$i = 0;
$limit = 100;

do {
    $start = $i * $limit;
    $i++;

    $arr = $GLOBALS['db']->getAll("SELECT * FROM `firstp2p_deal_site` ORDER BY `id` LIMIT {$start}, {$limit}");

    if (empty($arr)) {
        break;
    }
    
    foreach ($arr as $v) {
        $sql = "UPDATE `firstp2p_deal` SET `site_id` = '{$v['site_id']}' WHERE `id` = '{$v['deal_id']}' AND `site_id` = '0';";
        
        echo $sql."\n";
    }

} while(true);
