<?php

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

use core\dao\DealProjectModel;
use core\service\UserService;
use core\service\DealService;
use core\service\DealRepayService;
use core\service\DealAgencyService;
use core\service\EarningService;
use libs\utils\Finance;
use core\dao\DealModel;
use core\dao\DealContractModel;
use core\dao\DealAgencyModel;
use core\dao\DealLoadModel;
use core\dao\DealSiteModel;
use core\dao\DealExtModel;
use core\dao\ContractModel;
use core\service\ContractRenderService;
use core\service\ContractNewService;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use NCFGroup\Protos\Contract\RequestGetTplsByDealId;

$time = strtotime('-1 day');
set_time_limit(0);
ini_set('memory_limit', '1024M');
//文件名和路径
$csv_name = "dt_data.txt";

$base_path = '/tmp';
$csv_path = sprintf("%s/%s", $base_path, $csv_name);
$pdf_path = sprintf("%s/%s/", $base_path, 'dtContractPdf');

if(!is_dir($base_path)){
    @mkdir($base_path);
}

if(!is_dir($pdf_path)){
    @mkdir($pdf_path);
}

$fpr = fopen($csv_path, 'r') or die("Unable to open [{$csv_path}]!");
while (!feof($fpr)) {
    $oneLine = fgets($fpr);
    $record = explode(',',$oneLine);
    if(count($record) == 9){
        $result = getDtbLoanTransfer($record[0],$record[5],$record[4],$record[1],$record[6],$record[7],time(),str_replace(PHP_EOL, '', $record[8]));
        $file_name = $record[7].".pdf";
        $file_path = $pdf_path.$file_name;
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $mkpdf = new \Mkpdf ();
        $mkpdf->mk($file_path, $result);
    }else{
        break;
    }
}

fclose($fpr);

/**
 * 多投债权转让协议
 */
function getDtbLoanTransfer($deal_id, $user_id,$transfer_uid,$p2p_deal_id, $money=0, $num=null, $create_time, $dealLoadIds){
    $deal_id = intval($deal_id);
    $user_id = intval($user_id);
    $money = floatval($money);

    $user_service = new UserService();

    $deal_load_model = new DealLoadModel();

    $leasingContractNumber = '';

    $changeArr = array(1922390,1922391,1922394,1922396,1922397,1922398,1922400,1922401,1922404,1922405,1922406,1922407,1922408,1922410,1922411,1922412,1922414,1922416,1922423,1922688,1922695,1922696,1922697,1922699,1922700,1922702,1922703,1922704,1922705,1922706,1922707,1922708,1922786,1922788,1922789,1950388,1950587,1950835,1951247,1951391,1951573,1951636,1951651,1951692,1951695,1951774,1951925,1952138,1952348,1952352);
    $fixArr = array(1003656,1092381,1194227,1194291,1194332,1194340,1196579,1198111,1198132,1208592,1208629,1208749,1208864,1209157,1209192,1209466,1211708,1211719,1211763,1219416,1219417,1219431,1219448,1219478,1220717,1238473,1238598,1238706,1299020,1317782,1317789,1318331,1319379,1335002,1335121,1335746,1335784,1335957,1351390,1352405,1396101,1396138,1400909,1611084,1611196,1611240,1611260,1611310,1611388,1611389,1627621,1627890,1644635,1644636,1644638,1644639,1644640,1644641,1644642,1644647,1663297,1663338,1663414);

    $dealLoads = explode('_',$dealLoadIds);
    foreach($dealLoads as $p2pLoadId){
        $deal_load = $deal_load_model->find($p2pLoadId);

        if(in_array($p2p_deal_id,$changeArr)){
            $ctype = '16';
        }else{
            $ctype = '01';
        }

        if($p2p_deal_id >= 1000000){
            if(in_array($p2p_deal_id,$fixArr)){
                $numbers[] = ltrim(str_pad($p2p_deal_id,8,"0",STR_PAD_LEFT).str_pad('1',2,"0",STR_PAD_LEFT).$ctype.str_pad($deal_load['user_id'],8,"0",STR_PAD_LEFT).str_pad($p2pLoadId,10,"0",STR_PAD_LEFT),"0");
            }else{
                $numbers[] = str_pad($p2p_deal_id,8,"0",STR_PAD_LEFT).str_pad('1',2,"0",STR_PAD_LEFT).$ctype.str_pad($deal_load['user_id'],8,"0",STR_PAD_LEFT).str_pad($p2pLoadId,10,"0",STR_PAD_LEFT);
            }
        }else{
            $numbers[] = str_pad($p2p_deal_id,6,"0",STR_PAD_LEFT).str_pad('1',2,"0",STR_PAD_LEFT).$ctype.str_pad($deal_load['user_id'],8,"0",STR_PAD_LEFT).str_pad($p2pLoadId,10,"0",STR_PAD_LEFT);
        }
    }
    $leasingContractNumber = implode(', ',$numbers);

    $dt_agency_id = app_conf('AGENCY_ID_DT_PRINCIPAL');

    /* $deal = $deal_service->getDeal($deal_id); */
    $p2p_deal = get_deal_info($p2p_deal_id);

    $deal_agency_servie = new DealAgencyService;
    $advisory_info = $deal_agency_servie->getDealAgency($p2p_deal['advisory_id']);

    $advisory_user = $user_service->getUser($advisory_info['user_id']);

    $agency_info = $deal_agency_servie->getDealAgency($p2p_deal['agency_id']);
    // 标准企业权益转让合同（固定域名),通知贷企业借款合同
    $notice['company_name'] = "***投资成功后才可查看";

    // 标准企业权益转让合同（固定域名),通知贷企业借款合同
    $notice['company_license'] = "***投资成功后才可查看";

    // 标准企业权益转让合同（固定域名),标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同,通知贷个人借款合同
    $notice['borrow_real_name'] = "***投资成功后才可查看";

    //通知贷个人借款合同
    $notice['borrow_user_name'] = "***投资成功后才可查看";

    // 标准企业权益转让合同（固定域名),标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同
    $notice['borrow_user_number'] = "***投资成功后才可查看";

    // 标准企业权益转让合同（固定域名),,标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同,通知贷个人借款合同
    $notice['borrow_user_idno'] = "***投资成功后才可查看";

    // 标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷个人借款合同
    $notice['borrow_bank_user'] = "***投资成功后才可查看";

    // 标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷个人借款合同
    $notice['borrow_bank_card'] = "***投资成功后才可查看";

    // 标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）
    $notice['borrow_bank_name'] = "***投资成功后才可查看";

    //通知贷企业借款合同
    $notice['company_address_current'] = "***投资成功后才可查看";

    //通知贷企业借款合同
    $notice['company_legal_person'] = "***投资成功后才可查看";

    if($deal_id <= 0 || $user_id <= 0){
        return false;
    }

    $user_info = get_user_data($user_id);

    $dealagency_service = new DealAgencyService();
    $deal_site_id = 0;
    $deal_agency = $dealagency_service->getDealAgencyBySiteId($deal_site_id);


    if($deal_agency['agency_user_id'] > 0){
        $platform_agency_user = $user_service->getUser($deal_agency['agency_user_id']);
    }

    $transfer_user = $user_service->getUser($transfer_uid);

    $loan_bank_info = get_user_bank($user_id);
    $transfer_bank_info = get_user_bank($transfer_uid);

    //暂时使用主站平台名称，后续使用独立icp
    $notice['platform_show_name'] = '网信理财';
    $notice['platform_domain'] = 'www.firstp2p.com';

    //平台方
    $notice['platform_name'] = $deal_agency['name'];
    $notice['platform_license'] = $deal_agency['license'];
    $notice['platform_address'] = $deal_agency['address'];
    $notice['platform_realname'] = $deal_agency['realname'];
    $notice['platform_agency_realname'] = $platform_agency_user['real_name'];
    $notice['platform_agency_username'] = $platform_agency_user['user_name'];
    $notice['platform_agency_idno'] = $platform_agency_user['idno'];

    //DT-299 企业户显示

    $contractRenderService = new ContractRenderService();
    $loan_info = $contractRenderService->getLoanInfo($user_info,$loan_bank_info);
    $notice['loan_real_name'] = $loan_info['loan_name_info'];
    $notice['loan_user_name'] = $loan_info['loan_username_info'];
    $notice['loan_user_idno'] = $loan_info['loan_credentials_info'];
    $notice['loan_user_number'] = $loan_info['loan_user_number'];
    $notice['loan_bank_user'] = $loan_info['loan_bank_user_info'];
    $notice['loan_bank_card'] = $loan_info['loan_bank_no_info'];
    $notice['loan_bank_name'] = $loan_info['loan_bank_name_info'];
    $notice['loan_major_name'] = $loan_info['loan_major_name'];
    $notice['loan_major_condentials_no'] = $loan_info['loan_major_condentials_no'];

    $transfer_user_info = $contractRenderService->getLoanInfo($transfer_user,$transfer_bank_info);
    $notice['transfer_real_name'] = $transfer_user_info['loan_name_info'];
    $notice['transfer_user_name'] = $transfer_user_info['loan_username_info'];
    $notice['transfer_idno'] = $transfer_user_info['loan_credentials_info'];
    $notice['transfer_user_number'] = $transfer_user_info['loan_user_number'];
    $notice['transfer_bank_user'] = $transfer_user_info['loan_bank_user_info'];
    $notice['transfer_bank_card'] = $transfer_user_info['loan_bank_no_info'];
    $notice['transfer_bank_name'] = $transfer_user_info['loan_bank_name_info'];
    $notice['transfer_major_name'] = $transfer_user_info['loan_major_name'];
    $notice['transfer_major_condentials_no'] = $transfer_user_info['loan_major_condentials_no'];


    $notice['advisory_name'] = $advisory_info['name'];
    $notice['advisory_agent_user_name'] = $advisory_user['user_name'];
    $notice['advisory_license'] = $advisory_info['license'];
    $notice['advisory_user_number'] = numTo32($advisory_info['user_id'],0);

    $notice['agency_name'] = $agency_info['name'];

    $notice['leasing_contract_num'] = $leasingContractNumber?$leasingContractNumber:'';
    $notice['transfer_money'] = $money;
    $notice['transfer_money_uppercase'] = get_amount($money);

    $notice['leasing_money'] = $deal_load['money'];
    $notice['leasing_money_uppercase'] = get_amount($deal_load['money']);

    $notice['sign_time'] = date('Y年m月d日',$create_time);

    $notice['number'] = $num;

    $notice['base_deal_num'] = $p2p_deal['leasing_contract_num'] ? $p2p_deal['leasing_contract_num'] : '';
    //fature 4477
    $notice['min_loan_money'] = $p2p_deal['min_loan_money'];
    $notice['min_loan_money_uppercase'] = get_amount($p2p_deal['min_loan_money']);
    $notice['project_borrow_amount'] = intval($p2p_deal['project_info']['borrow_amount']);
    $notice['project_borrow_amount_uppercase'] = get_amount($p2p_deal['project_info']['borrow_amount']);

    // p2p标最后一期还款时间
    $dealRepayService = new DealRepayService();
    $finalRepayTime = $dealRepayService->getFinalRepayTimeByDealId($p2p_deal_id);
    $t1 = strtotime(to_date($finalRepayTime,'Y-m-d 00:00:00'));//p2p 晚8小时需要加上
    $t2 = strtotime(date('Y-m-d 00:00:00',$create_time));
    $notice['transfer_days'] = floor(abs($t1-$t2)/86400);

    $tpl_prefix = 'TPL_DTB_LOAN_TRANSFER';

    $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
    $request->setDeal_id(intval($deal_id));
    $request->setType(1);
    $request->setTpl_prefix($tpl_prefix);

    $rpc = new Rpc('contractRpc');

    $response = $rpc->go("\NCFGroup\Contract\Services\Tpl","getTplByName",$request);

    $tpl_content = $response->data[0]['content'];
    $GLOBALS['tmpl']->assign("notice",$notice);

    return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
}

function get_deal_info($deal_id){
    $deal_id = intval($deal_id);
    if($deal_id <= 0){
        return array();
    }
    static $deal_info = array();
    if(!isset($deal_info[$deal_id])){
        $deal_service = new DealService();
        $deal_project_model = new DealProjectModel();
        $deal_info[$deal_id] = $deal_service->getDeal($deal_id, true);
        $deal_info[$deal_id]['project_info'] = $deal_project_model->find($deal_info[$deal_id]['project_id']);
    }
    return $deal_info[$deal_id];
}

function get_user_data($user_id){
    $user_id = intval($user_id);
    if($user_id <= 0){
        return array();
    }
    static $user_info = array();
    if(!isset($user_info[$user_id])){
        $user_service = new UserService();
        $user_info[$user_id] = $user_service->getUserViaSlave($user_id);
    }
    return $user_info[$user_id];
}