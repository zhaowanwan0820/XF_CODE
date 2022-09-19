<?php

/*
 * 给定标的ID,导出指定的合同(无时间戳)
 * 参数1,合同类型,目前则为tpl_identifier_id
 * 参数2,标id,以','分隔,传0默认赋值
 * 参数3,合同导出起点
 * 参数4,步长
 * 注:
 * 如果只传合同类型,则按照数序导出,文件名以数字递增+合同编号命名
 * 如传入标的ID列表,则按照合同编号命名
 * php scripts/contract/export_contract_deal_ids.php 14  5425649
 * php scripts/contract/export_contract_deal_ids.php 12
 * php scripts/contract/export_contract_deal_ids.php 13
 * php scripts/contract/export_contract_deal_ids.php 14
 * php scripts/contract/export_contract_deal_ids.php 27 只有一标
 *
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\service\contract\ContractViewerService;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\ContractContentModel;

use libs\utils\Rpc;

use libs\utils\Logger;

use NCFGroup\Protos\Contract\RequestGetContractByType;

use core\service\ContractService;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(isset($argv[1])){
    $contractType = intval($argv[1]);
}

if(isset($argv[1])){
    $contractType = intval($argv[1]);
}

if(isset($argv[2]) && ($argv[2] != 0)){
    $dealIds = explode(',',$argv[2]);
}else{
    $dealIds = array(157492 , 158337 , 158460);

    if(isset($argv[3])){
        $start = intval($argv[3]);
    }

    if(isset($argv[4])){
        $limit = intval($argv[4]);
    }
}

$base_path = dirname(__FILE__).'/../../log/logger/contract_export';
$pdf_path = sprintf("%s/%s/", $base_path, $contractType);

if(!is_dir($base_path)){
    @mkdir($base_path);
}

if(!is_dir($pdf_path)){
    @mkdir($pdf_path);
}

$contractViewerService = new ContractViewerService();
$i = 1;
$rpc = new Rpc('contractRpc');

\FP::import("libs.tcpdf.tcpdf");
\FP::import("libs.tcpdf.mkpdf");
$mkpdf = new \Mkpdf ();

foreach($dealIds as $dealId) {

    if(($start > 0) && ($limit > 0)){
        //跳出条件
        if($i > ($start + $limit)){
            break;
        }

        //跳过条件
        if($i < ($start + 1)){
            $i++;
            continue;
        }
    }

    $deal = DealModel::instance()->find($dealId);

    $request = new \NCFGroup\Protos\Contract\RequestGetContractByType();
    $request->setDealId(intval($dealId));
    $request->setType(intval($contractType));
    $request->setSourceType(intval($deal['deal_type']));

    if ($dealId <= 12123) {
        $contract_info = array();
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getOldContractByType", $request);

        if (count($response->data) >= 1) {
            foreach ($response->data as $record) {
                if($contractType == 5){
                    $file_name = $i ."_".$record['number']. ".pdf";
                }else{
                    $file_name = $record['number'] . ".pdf";
                }
                $file_path = $pdf_path . $file_name;

                $contract_info['content'] = ContractContentModel::instance()->find($record['id']);
                $mkpdf->mk($file_path, $contract_info['content']);
                echo $file_path . ' success !' . "\n";
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$file_path." success !")));
                unset($contract_info['content'],$file_path,$file_name);
            }
        }

    } else {

        // getContractByType  有些标的合同的type全为0(包括咨询协议和借款合同，目前只要借款合同)
        // $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getContractByType", $request);

        $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getContractByTplIdentity", $request);
 /*       //以下代码用于导专享的项目合同
        $request = new \NCFGroup\Protos\Contract\RequestGetContractByProjectId();
        $request->setProjectId(intval($deal['project_id']));
        $request->setSourceType(intval($deal['deal_type']));
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract", "getContractByProjectId", $request);
        if (count($response->getList()) >= 1) {
  */
        if (count($response->data) >= 1) {
            foreach ($response->data as $record) {
                $file_name = $i ."_".$record['number']. ".pdf";
                $file_path = $pdf_path . $file_name;
                $contract_info = ContractViewerService::getOneFetchedContract($record['id'], $dealId, 1);
                //$contract_info = ContractViewerService::getOneFetchedContract($record['id'], $dealId, 2); // 1为标的合同,2为项目合同

                $mkpdf->mk($file_path, $contract_info['content']);
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$file_path." success !", "dealId:".$dealId)));

                echo $file_path . ' success !' . " dealId:".$dealId. "\n";
                //手动清理内存
                unset($contract_info,$file_path,$file_name);
            }
            // 可以使用grep  tpl_identity_creat_pdf_success  p2p_info_18_04_20.log |wc -l
            // 用于统计生成合同的数量
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$pdf_path." tpl_identity_creat_pdf_success !", "dealId:".$dealId)));
            usleep(100000); // 休息100ms
        }
    }

    $i++;
}
