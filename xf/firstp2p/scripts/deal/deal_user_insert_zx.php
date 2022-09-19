<?php
/**
 * 有专享在途的用户，插入标的定制数据，20180502
 */
namespace scripts;

require_once(dirname(__FILE__) . '/../../app/init.php');

set_time_limit(0);
ini_set('memory_limit', '1024M');

//@param $deal_id_list 标的id数组
//@param $deal_type 0值取专享和交易所，0取交易所且不在专享里的用户
//@param $is_execute 是否执行数据库操作
$opts = getopt("e:d:t:");
$is_execute = isset($opts['e']) && intval($opts['e']) ? intval($opts['e']) : false;
$deal_id_list = isset($opts['d']) && !empty($opts['d']) ? trim($opts['d']) : '';
$deal_type  = isset($opts['t']) && intval($opts['t']) ? intval($opts['t']) : false;

if (!empty($deal_id_list)) {
    $deal_id_list = explode(',', $deal_id_list);
}

echo __FILE__ . ' | inserDealUserZX | start' . PHP_EOL;
//$deal_id_list = array('5457680','5475274','5475750','5477981','5477976','5463089','5473309','5472693','5473426','5476362','5476356');
$dealCustomUserService = new \core\service\DealCustomUserService();
$dealCustomUserService->inserDealUserZX($deal_id_list, $deal_type, $is_execute);
echo __FILE__ . ' | inserDealUserZX | done' . PHP_EOL;
