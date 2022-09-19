<?php

/*
 * 给定标的ID列表,导出所有的合同
 * 参数1,标id,以','分隔,传0默认赋值
 * 优化措施：
 * 1 导出一份合同休眠0.1秒，已经导出了的合同则不重复导出
 * 2 内存增加至5120M
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\service\contract\ContractViewerService;

use core\dao\DealModel;
use core\dao\FastDfsModel;
use core\dao\ContractFilesWithNumModel;

use libs\utils\Rpc;

use libs\utils\Logger;

use NCFGroup\Protos\Contract\RequestGetContractByDealId;

set_time_limit(0);
ini_set('memory_limit', '5120M');

if(isset($argv[1]) && ($argv[1] != 0)){
    $dealIds = explode(',',$argv[1]);
}else{

    echo '参数错误!';
}

if(isset($argv[2])){
    $type = intval($argv[2]);
}

$base_path = dirname(__FILE__).'/../../log/logger/contract_export_'.date('Y-m-d',time());

if(!is_dir($base_path)){
    @mkdir($base_path);
}

$contractViewerService = new ContractViewerService();
$rpc = new Rpc('contractRpc');

\FP::import("libs.tcpdf.tcpdf");
\FP::import("libs.tcpdf.mkpdf");
$mkpdf = new \Mkpdf ();

foreach($dealIds as $dealId) {

    echo $dealId;
    $deal = DealModel::instance()->find($dealId);
    $request = new RequestGetContractByDealId();
    $request->setDealId(intval($dealId));
    $request->setSourceType(intval($deal['deal_type']));

    $contract_info = array();

    $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getContractByDealId", $request);
    if (count($response->list) >= 1) {
        foreach ($response->list as $record) {

            $pdf_path = sprintf("%s/%s/", $base_path, $dealId);
            $file_name = $record['number'] . ".pdf";

            if(!is_dir($pdf_path)){
                @mkdir($pdf_path);
            }

            $file_path = $pdf_path . $file_name;
            if(file_exists($file_path)){
                continue;
            }
            $contract_info = ContractViewerService::getOneFetchedContract($record['id'], $dealId, 1);
            if($type == 1){
                $mkpdf->mk($file_path, $contract_info['content']);
            }elseif($type == 2){
                $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($contract_info['number']);

                if(!empty($ret) && !empty($ret[0])){
                    $fileInfo = array();
                    $fileInfo = end($ret);
                    $dfs = new FastDfsModel();
                    $fileContent = $dfs->readTobuff($fileInfo['group_id'],$fileInfo['path']);
                    if(!empty($fileContent)){
                        file_put_contents($file_path, $fileContent);
                        unset($ret,$fileInfo,$fileContent,$dfs);
                    }else{
                        echo $file_path." create tsa contract fail!". "\n";
                        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$file_path." create tsa contract fail!")));
                    }
                }else{
                    // 如果记录表中没有信息则
                    echo $file_path." no tsa record!". "\n";
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$file_path." no tsa record!")));
                }
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$file_path." success !")));

            echo $file_path . ' success !' . "\n";
            unset($contract_info,$file_path,$file_name);
            usleep(100000); // 休息100ms
        }
    }
}
