<?php
/**
 * 还款计划表邮件于当日 17:30 分发送，发送当日至前一日期间批次状态变为【还款中】的批次对应的还款计划表
 * php repay_plan_send.php
 */
require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../system/utils/logger.php';

use core\dao\ExchangeModel;
use core\dao\DealAgencyModel;
use core\service\UserService;

set_time_limit(0);
ini_set('memory_limit', '1024M');

//生成csv
function genCsv($exportData, $filePath) {
    $dataArr =[];
    foreach($exportData as $row){
        foreach ($row as $key => $value) {
            $row[$key] = iconv('UTF-8', 'GBK', $value);
        }
        $dataArr[] = implode(",", $row);
    }
    $dataStr = implode("\n", $dataArr)."\n";
    $res = file_put_contents($filePath, $dataStr, FILE_APPEND);
    return $res;
}

//生成还款计划表
function exportRepayPlan($batchInfo, $dir){

    $exchangeModel = new ExchangeModel();
    // 获取项目信息
    $projectInfo = $exchangeModel->getProjectInfoById($batchInfo['pro_id']);

    // 获取发行人名称
    $userService = new UserService();
    $userInfo = $userService->getUser($projectInfo['fx_uid']);

    //机构信息
    $dealAgencyModel = new DealAgencyModel();
    $ids = sprintf("%d,%d,%d,%d,%d",$projectInfo['business_manage_id'],$projectInfo['invest_adviser_id'],$projectInfo['consult_id'],$projectInfo['guarantee_id'], $projectInfo['jys_id']);
    $agencyRes = $dealAgencyModel->getDealAgencyByIds($ids);
    $agencyList = array();
    foreach($agencyRes as $item){
        $agencyList[$item['id']] = $item;
    }

    $condition = sprintf('batch_id=%d', $batchInfo['id']);
    $repayList = $exchangeModel->getRepayList($condition);

    $fileName = sprintf("%s(%d)还款计划表.csv", $projectInfo['jys_number'], $batchInfo['batch_number']);
    $filePath = sprintf("%s/%s", $dir, $fileName);
    $title = [
        '交易所备案编号', '批次编号', '发行人名称',
        '还款日', '待还金额', '待还本息',
        '业务管理方', '发行服务费', '投资顾问机构',
        '投资顾问费', '咨询机构', '咨询费',
        '担保机构', '担保费', '交易所', '挂牌费'
    ];
    $exportData[] = $title;
    foreach ($repayList as $item) {
        if ($item['principal'] <= 0 && $item['interest'] <= 0) {
            continue; // 第一条放款前收手续费不导出
        }
        $row = [
            $projectInfo['jys_number'], $batchInfo['id'], $userInfo['real_name'],
            date("Y-m-d", $item['repay_time']),  $item['repay_money'] / 100, ($item['principal'] + $item['interest']) / 100,
            $agencyList[$projectInfo['business_manage_id']]['name'], $item['publish_server_fee'] / 100, $agencyList[$projectInfo['invest_adviser_id']]['name'],
            $item['invest_adviser_fee']/100, $agencyList[$projectInfo['consult_id']]['name'], $item['consult_fee']/100,
            $agencyList[$projectInfo['guarantee_id']]['name'], $item['guarantee_fee']/100, $agencyList[$projectInfo['jys_id']]['name'], $item['hang_server_fee']/100
        ];
        $exportData[] = $row;
    }
    $exportData[] = ["\n注：发行人需于还款日前一工作日将还款资金支付至交易所账户"];
    @unlink($filePath);
    genCsv($exportData, $filePath);

    $file = array(
        'error' => 0,
        'name' => $fileName,
        'type' => 'application/csv',
        'size' => filesize($filePath),
        'tmp_name' => $filePath,
    );
    $fileParams = array(
        'file' => $file,
        'asAttachment' => 1,
        'asPrivate' => 1,
    );
    $fileRes = @uploadFile($fileParams);

    return array(
        'exchange_repay_plan_email' => $agencyList[$projectInfo['consult_id']]['exchange_repay_plan_email'],
        'name' => sprintf("%s(%d)", $projectInfo['jys_number'], $batchInfo['batch_number']),
        'aid' => $fileRes['aid'],
    );
}

$dir = APP_ROOT_PATH.'log/attachment';
if(!is_dir($dir)){
    mkdir($dir);
}

function getLastWorkIntervalNumber($holidays, $work_time, $diff_time){
     $work_time = $work_time - $diff_time;
     $n = 1;
     while(in_array(date("Y-m-d", $work_time), $holidays)){
         $work_time = $work_time - $diff_time;
         $n++;
     }

     return $n;
}

$holidays = dict::get('REDEEM_HOLIDAYS');
$now_time = mktime(0,0,0,date('m'),date('d'),date('y'));
//节假日不发送邮件
if(in_array(date("Y-m-d", $now_time), $holidays)){
   exit;
}

$msgcenter = new Msgcenter();
$fromEmailData = array('sender' => '网信-平台运营部','from' => 'platope@s1.firstp2p.com');

$etime = mktime(17,30,0,date('m'),date('d'),date('y'));
$stime = $etime - 86400;
//节假日后第一个工作日5:30发送该工作日至节假日前最后一个工作日5:30后操作的还款计划表
 if(in_array(date("Y-m-d", $now_time - 86400), $holidays)){
     $intervalNumber = getLastWorkIntervalNumber($holidays, $now_time, 86400);
     $stime = $etime - $intervalNumber * 86400;
 }

$exchangeModel = new ExchangeModel();
$condition = sprintf("deal_status = 2 AND UNIX_TIMESTAMP(utime) > %d AND UNIX_TIMESTAMP(utime) <= %d", $stime, $etime);
$batchList = $exchangeModel->getBatchList($condition);

foreach($batchList as $item){
    $resData = exportRepayPlan($item, $dir);
    $email_arr = explode(',', $resData['exchange_repay_plan_email']);
    $notice_title = "【还款计划表】".$resData['name'];
    foreach($email_arr as $email){
        if(is_email($email)){
            $msgcenter->setMsg($email, 0, array('name' => $resData['name']), 'TPL_OFFLINE_EXCHANGE_REPAY_PLAN', $notice_title, $resData['aid'], '', ['is_vfs' => 1, 'is_basename' => 1], $fromEmailData);
        }
    }
}
$msgcenter->save();//发送邮件

//删除文件

`find $dir -mtime +7 -name *.csv | xargs rm -rf`;
exit;
