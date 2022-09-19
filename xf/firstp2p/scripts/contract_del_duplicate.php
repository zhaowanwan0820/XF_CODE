<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/11/23
 * Time: 下午2:24
 */

require_once dirname(__FILE__) . '/../app/init.php';

use core\dao\ContractModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
if($deal_id === 0){
    echo "未输入标的ID";
    exit();
}

$contract_model = new ContractModel();
$exe_count = 0;
$contract_res = $contract_model->findAllBySql("SELECT user_id,agency_id,deal_load_id,`type` FROM firstp2p_contract WHERE deal_id = '".$deal_id."' ORDER BY id DESC",true);
foreach($contract_res as $contract) {
    $contract_ids = $contract_model->findAllBySql("SELECT id FROM firstp2p_contract WHERE deal_id = '".$deal_id."' AND user_id = '".$contract['user_id']."' AND agency_id = '".$contract['agency_id']."' AND deal_load_id = '".$contract['deal_load_id']."' AND type = '".$contract['type']."' ORDER BY id DESC",true);
    $count = count($contract_ids);
    if($count <> 1){
        foreach($contract_ids as $v){
            echo $v['id']."\n";
            if($count <> 1){
                echo "DELETE FROM firstp2p_contract WHERE id = '".$v['id']."';"."\n";
                $exe_count ++;
                $count --;
            }
        }
    }
}

echo "共需删除".$exe_count."条记录！";