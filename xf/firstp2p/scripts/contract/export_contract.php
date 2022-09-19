<?php

/*
 * 给定标的ID列表,导出所有的合同
 * 参数1,标id,以','分隔,传0默认赋值
 * 参数2, type 1:非打戳 2:打戳
 */
require_once dirname(__FILE__) . '/../../app/init.php';
require_once dirname(__FILE__) . '/../../libs/common/app.php';
require_once dirname(__FILE__) . '/../../libs/common/functions.php';
require_once dirname(__FILE__) . '/../../system/libs/msgcenter.php';

use core\service\contract\ContractViewerService;

use core\dao\DealModel;
use core\dao\FastDfsModel;
use core\dao\ContractFilesWithNumModel;

use libs\utils\Rpc;

use libs\utils\Logger;

use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Protos\Contract\RequestGetContractByProjectId;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(isset($argv[1]) && ($argv[1] != 0)){
    $dealIds = explode(',',$argv[1]);
}else{
    echo '参数错误!';
}

// 只允许传1或者2 默认为2下载时间戳的
$type = isset($argv[2]) && in_array(intval($argv[2]), [1, 2]) ? intval($argv[2]) : 2;

$base_path = dirname(__FILE__) . '/../../log/logger/contract_export_' . date('Y-m-d', time());

if (!is_dir($base_path)) {
    @mkdir($base_path);
}

$contractViewerService = new ContractViewerService();
$rpc = new Rpc('contractRpc');

\FP::import("libs.tcpdf.tcpdf");
\FP::import("libs.tcpdf.mkpdf");


function download($response, $base_path, $deal, $type, $is_project_contract = false)
{

    if (count($response->list) >= 1) {
        foreach ($response->list as $record) {
            $contract_info = array();
            $pdf_path = sprintf("%s/%s/", $base_path, $deal['id']);
            $file_name = $record['number'] . ".pdf";

            if (!is_dir($pdf_path)) {
                @mkdir($pdf_path);
            }

            $file_path = $pdf_path . $file_name;
            if (file_exists($file_path)) {
                continue;
            }
            if ($type == 1) {
                if(($deal['deal_type'] == DealModel::DEAL_TYPE_EXCLUSIVE) && $is_project_contract){
                    $contract_info = ContractViewerService::getOneFetchedContract($record['id'], $deal['project_id'], 2);
                }else{
                    $contract_info = ContractViewerService::getOneFetchedContract($record['id'], $deal['id'], 1);
                }
                $mkpdf = new \Mkpdf ();
                $mkpdf->mk($file_path, $contract_info['content']);
                unset($mkpdf,$contract_info);
                usleep(10000);
            } elseif ($type == 2) {
                $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($record['number']);

                if (!empty($ret) && !empty($ret[0])) {
                    $fileInfo = array();
                    $fileInfo = end($ret);
                    $dfs = new FastDfsModel();
                    $fileContent = $dfs->readTobuff($fileInfo['group_id'], $fileInfo['path']);
                    if (!empty($fileContent)) {
                        file_put_contents($file_path, $fileContent);
                        unset($ret, $fileInfo, $fileContent, $dfs, $contract_info);
                    } else {
                        echo $file_path . " create tsa contract fail!" . "\n";
                        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $file_path . " create tsa contract fail!")));
                    }
                } else {
                    // 如果记录表中没有信息则
                    echo $file_path . " no tsa record!" . "\n";
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $file_path . " no tsa record!")));
                }
            } else {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $file_path . " type 非1和2 !")));
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $file_path . " success !")));
            echo $file_path . ' success !' . "\n";
            unset($contract_info, $file_path, $file_name);
        }
        usleep(100000); // 每下载一个标的的所有合同，休息100ms
    }
}

foreach ($dealIds as $dealId) {

    echo $dealId . "\n";
    $deal = DealModel::instance()->find($dealId);
    $request = new RequestGetContractByDealId();
    $request->setDealId(intval($dealId));


    $request->setSourceType(intval($deal['deal_type']));

    // 如果是专享，则要下载项目合同
    if ($deal['deal_type'] == DealModel::DEAL_TYPE_EXCLUSIVE) {
        $contractRequest = new RequestGetContractByProjectId();
        $contractRequest->setProjectId(intval($deal['project_id']));
        $contractRequest->setSourceType(intval($deal['deal_type']));
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getContractByProjectId", $contractRequest);
        download($response, $base_path, $deal, $type, true);
    }
    // 下载标的合同
    $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getContractByDealId", $request);
    download($response, $base_path, $deal, $type);
}
