<?php

/*
 * 给某个合同实时打戳，然后保存在服务器日志目录下
 * 参数1,标id
 * 参数2,合同id
 */
require_once dirname(__FILE__) . '/../../app/init.php';
require_once dirname(__FILE__) . '/../../libs/common/app.php';
require_once dirname(__FILE__) . '/../../libs/common/functions.php';
require_once dirname(__FILE__) . '/../../system/libs/msgcenter.php';

use core\service\contract\ContractViewerService;
use core\service\DealService;
use core\service\ContractSignService;

use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if (isset($argv[1]) && ($argv[1] != 0)) {
    $dealId = intval($argv[1]);
} else {
    echo "参数1错误!\n";
    exit();
}

if (isset($argv[2]) && ($argv[2] != 0)) {
    $contractId = intval($argv[2]);
} else {
    echo  "参数2错误!\n";
    exit();
}


\FP::import("libs.tcpdf.tcpdf");
\FP::import("libs.tcpdf.mkpdf");
$mkpdf = new \Mkpdf ();

echo $dealId."\n";
$dealService = new DealService();
$dealInfo = $dealService->getDeal($dealId);
if(empty($dealInfo)){
    echo "标的不存在!\n";
    exit();
}

// 获取渲染后的合同记录
$contractInfo = ContractViewerService::getOneFetchedContract($contractId, $dealId, 1);
if(empty($contractInfo)){
    echo "合同不存在!\n";
    exit();
}
$base_path = dirname(__FILE__) . '/../../log/logger/contract_export_' . date('Y-m-d', time());
if (!is_dir($base_path)) {
    @mkdir($base_path);
}
$pdf_path = sprintf("%s/%s/", $base_path, $dealId);
$file_name = $contractInfo['number'] . ".pdf";
if (!is_dir($pdf_path)) {
    @mkdir($pdf_path);
}
$file_path = $pdf_path . $file_name;

$signService = new ContractSignService();
$tmpPdfPath = $signService->createTmpPdf($contractInfo);
if( empty($tmpPdfPath) ){
    echo "生成临时pdf失败\n";
    exit();
}
$signedFileContent = $signService->doSign($tmpPdfPath,$dealInfo,$contractInfo['tpl_indentifier_info']['contractType']);
if( empty($signedFileContent) ){
    echo "打戳失败\n";
    exit();
}
//删除临时生成的pdf
@unlink($tmpPdfPath);
// 保存打戳pdf文件
file_put_contents($file_path,$signedFileContent);
Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $file_path . " success !")));

echo $file_path . ' success !' . "\n";

unset($contract_info, $file_path, $file_name,$tmpPdfPath);

