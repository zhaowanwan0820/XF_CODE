<?php
/**
 * 补发标的某一类合同
 */
require_once dirname(__FILE__) . '/../app/init.php';

use core\service\ContractService;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
$type = isset($argv[2]) ? intval($argv[2]) : '';
if($deal_id <= 0){
    exit('deal_id错误');
}
$contract_service = new ContractService();
$rs = $contract_service->contractRenew($deal_id, $type);
echo '借款id：'.$deal_id.'，共'.$rs['count'].'条合同需补发，成功补发'.$rs['num']."条\n";
