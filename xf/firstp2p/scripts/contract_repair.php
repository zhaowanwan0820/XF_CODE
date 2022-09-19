<?php
/**
 * 补发合同
 */
require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\DealModel;
use core\service\ContractService;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$project_id = isset($argv[1]) ? intval($argv[1]) : 0;
if($project_id <= 0){
    exit('id错误');
}
$deal_list = DealModel::instance()->getDealByProId($project_id);

$contract_service = new ContractService();
foreach($deal_list as $deal){
    $rs = $contract_service->contractRenew($deal['id']);
    echo '借款id：'.$deal['id'].'，共'.$rs['count'].'条合同，成功补发'.$rs['num']."条\n";
}