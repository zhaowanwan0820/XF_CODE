<?php

/*
 * 给定老版合同id
 * 参数1,标id,以','分隔,传0默认赋值
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\service\contract\ContractViewerService;

use core\dao\DealModel;
use core\dao\FastDfsModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\ContractContentModel;

use libs\utils\Rpc;

use libs\utils\Logger;

use NCFGroup\Protos\Contract\RequestGetContractByDealId;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(isset($argv[1]) && ($argv[1] != 0)){
    $contractIds = explode(',',$argv[1]);
}else{

    echo '参数错误!';
}


$base_path = dirname(__FILE__).'/../../log/logger/contract_export_'.date('Y-m-d',time()).'/';

if(!is_dir($base_path)){
    @mkdir($base_path);
}

$contractViewerService = new ContractViewerService();
$rpc = new Rpc('contractRpc');

\FP::import("libs.tcpdf.tcpdf");
\FP::import("libs.tcpdf.mkpdf");
$mkpdf = new \Mkpdf ();

$contract_info = array();
foreach($contractIds as $contractId) {
    $file_path = $base_path . $contractId. '.pdf';
    $contract_info['content'] = ContractContentModel::instance()->find($contractId);
    $mkpdf->mk($file_path, $contract_info['content']);
    echo $file_path . ' success !' . "\n";
    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$file_path." success !")));
    unset($contract_info['content'],$file_path);
}
