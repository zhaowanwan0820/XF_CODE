<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '1024M');

require_once dirname(__FILE__).'/../../app/init.php';

use libs\utils\PaymentApi;

\libs\utils\Script::start();

$startTime = microtime(true);

PaymentApi::log('vip clear_history start.'.date('Y-m-d H:i:s'));

$vipsql = 'SELECT * FROM firstp2p_vip_point_log WHERE id<2000000 order by id asc ';
PaymentApi::log('vip clear_history sql:'.$vipsql);
$p2pdb = \libs\db\Db::getInstance('vip','master','utf8',1);
$result = $p2pdb->query($vipsql);

while($result && ($data = $p2pdb->fetchRow($result))) {
    try{
        PaymentApi::log('vip删除log:'.json_encode($data,JSON_UNESCAPED_UNICODE));
        if ($data) {
            $delSql = 'DELETE FROM firstp2p_vip_point_log WHERE id='.intval($data['id']);
            echo $delSql."\n";
            echo json_encode($data,JSON_UNESCAPED_UNICODE)."\n";
            $res = $p2pdb->query($delSql);
        }
    } catch (\Exception $e) {
        PaymentApi::log('vip降级异常' . $e->getMessage());
    }
}
PaymentApi::log('vip clear_history end.'.date('Y-m-d H:i:s'));
\libs\utils\Script::end();
