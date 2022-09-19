<?php
//红包测试，禁止在线上运行
//by zhangruoshi

$_SERVER['HTTP_X_FORWARDED_HOST'] = 'lc.firstp2p.com';
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

use core\service\DealLoadService;
use core\service\BonusService;
use core\dao\DealModel;

$bonus_service = new BonusService();
$deal_model = new DealModel();

echo "红包组id,投资金额,期限,红包组金额,红包数,单个红包金额\r\n";
$sql = "select * from firstp2p_deal_load where money>10000 order by id desc";
$db = $GLOBALS['db'];
$res = $db->query($sql);
while($row = $db->fetch_array($res)){
    $deal_info = $deal_model::instance()->find($row['deal_id'], '`loantype`, `repay_time`');
    if($deal_info['loantype'] == 5){
        $year_ratio = number_format($deal_info['repay_time'] / 360, 2);
    }else{
        $year_ratio = number_format($deal_info['repay_time'] / 12, 2);
    }
    
    $sn = $bonus_service->generation($row['user_id'], $row['id'], $row['money'],$year_ratio);
    $bonus = $bonus_service->get_list_by_sn($sn);
    $bonus_money = '';
    foreach($bonus as $k=>$v){
        $bonus_money.=$v['money'].'|';
    }
    $bonus_group = $bonus_service->get_group_info_by_sn($sn);
    echo $bonus_group['id'].','.$bonus_group['deal_load_money'].','.$year_ratio.','.$bonus_group['money'].','.$bonus_group['count'].','.$bonus_money."\r\n";
}

 