<?php
/**
 * 生成合同
 */
require_once dirname(__FILE__) . '/../app/init.php';
use core\dao\OpLogModel;
use core\dao\OpStatusModel;
use core\dao\DealContractModel;
use core\dao\DealModel;
use core\service\ContractService;
use core\service\DealService;
use libs\utils\Logger;

ini_set('memory_limit', '1024M');
set_time_limit(0);

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
if($deal_id <= 0){
    exit('id错误');
}

$cont_service = new ContractService();
$del = $cont_service->delContByDeal($deal_id);

$deal_service = new DealService();
$res = $deal_service->sendDealContract($deal_id);

if($res){
    $op_status = new OpStatusModel();
    $params = array(':op_name' => OpLogModel::instance()->get_opname_by_content($deal_id, OpLogModel::OPNAME_DEAL_CONTRACT));
    $op_row = $op_status->findBy("`op_name` = ':op_name'", 'id', $params);
    if($op_row){
        $op_status->update_status($op_row->id, 1);
    }
}

$dc_model = new DealContractModel();
$r = $dc_model->findAll("`deal_id`='{$deal_id}'");
if (!$r) {
    $deal = DealModel::instance()->find($deal_id);
    //如果合同签署使用新的规则（4*n+2）,则多生成一条资产管理方签署记录
    if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
        $deal['contract_version'] = 2;
    }
    $dc_model->create($deal);

}

$msg = $res ? 'success' : 'fail';

echo $msg;
Logger::wLog(array('data' => "result:{$msg}|deal_id:".$deal_id, 'script/contract_make'));