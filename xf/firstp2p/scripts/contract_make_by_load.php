<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/11/18
 * Time: 上午4:31
 */
require_once dirname(__FILE__) . '/../app/init.php';

use core\service\DealLoadService;
use core\service\DealService;
use core\service\DealAgencyService;
use core\dao\OpStatusModel;
use core\service\ContractService;
use core\service\EarningService;
use core\service\ContractPreService;
use core\service\MsgCategoryService;
use core\dao\DealModel;
use core\dao\DealContractModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
$deal_load = new DealLoadService();
$op_status_dao = new OpStatusModel();
$deal_contract_dao = new DealContractModel();
$contract_service = new ContractService();
$del = $contract_service->delContByDeal($deal_id);
$i = 0;
echo "repair标的：".$deal_id."\n";
try {
    $deal_loads = $deal_load->getDealLoanListByDealId($deal_id);
    foreach ($deal_loads as $load) {
        if (is_object($load)) {
            $load_row = $load->getRow();
            if(!send_contract($deal_id,$load_row['id'],false)){
                echo "标的：".$deal_id."-投资:".$load_row['id']."合同生成失败 \n";
                exit();
            }
            $i++;
        }
    }
    if(!send_contract($deal_id,0,true)){
        echo "标的：".$deal_id."满标合同生成失败 \n";
        exit();
    }else{
        if($deal_contract_dao->delDealContByDealId($deal_id)){
            $deal = DealModel::instance()->find($deal_id);
            if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                $deal['contract_version'] = 2;
            }
            if($deal_contract_dao->create($deal)){
                echo $deal_id."-生成合同成功,共生成:".$i."条投资的合同记录! \n";
            }
        }else{
            echo "标的：".$deal_id."-删除签署记录失败! \n";
        }

    }
} catch (\Exception $e) {
    throw new \Exception($e->getMessage());
}

function send_contract($deal_id,$load_id,$is_full){
    \FP::import("libs.common.app");
    $deal_service = new DealService();
    $deal = $deal_service->getDeal($deal_id);

    if(empty($deal) || empty($deal['contract_tpl_type'])){
        return true;//该类标不需要生成合同
    }
    require_once(APP_ROOT_PATH."system/libs/send_contract.php");
    $contractModule = new \sendContract();  //引入合同操作类

    $notice_contrace = array();
    $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal); //借款人 或公司信息

    $dealagency_service = new DealAgencyService();
    $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
    $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//咨询公司信息

    $loan_user_list = $GLOBALS['db']->getAll("SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time FROM ".DB_PREFIX."deal_load as d,".DB_PREFIX."user as u WHERE d.id = '".$load_id."' AND d.user_id = u.id");
    $guarantor_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']); //获取保证人列表
    //扩展字段
    $earning = new EarningService();
    $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
    $borrow_user_info['repay_money'] = $all_repay_money;
    $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
    $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
    $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
    $borrow_user_info['leasing_money'] = $deal['leasing_money'];
    $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
    $borrow_user_info['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
    $borrow_user_info['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
    $borrow_user_info['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");
    ################   借款合同  ################
    if($is_full === true){
        //借款人平台服务协议
        $contractModule->push_borrower_protocal($deal, $borrow_user_info);

        //借款人咨询服务协议-(借款人,资产管理方)
        $contractModule->push_borrower_protocal_v2($deal, $borrow_user_info, $advisory_info);
        //新的合同生成逻辑结束
    }else{
        //出借人
        $contractModule->push_loan_contract($deal, $loan_user_list, $borrow_user_info, NULL);
        //出借人平台服务协议
        $contractModule->push_lender_protocal($deal, $loan_user_list, $borrow_user_info);
        //借款人
        $contractModule->push_loan_contract($deal, $loan_user_list, $borrow_user_info, $deal['user_id']);
        ################   委托担保合同 （借款人、担保公司）         ################
        //借款人
        $contractModule->push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $deal['user_id']);
        //担保公司
        $contractModule->push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $agency_info['id'],"agency");
        ################   保证人反担保（保证人、担保公司）         ################
        //保证人
        $contractModule->push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, "guarantor");
        //担保公司
        $contractModule->push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, "agency");
        ################   担保合同（担保公司、出借人）         ################
        //担保公司
        $contractModule->push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, $agency_info['id'],"agency");
        //出借人
        $contractModule->push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, NULL);

        ################   付款委托书（借款人）         ################
        if($deal['contract_tpl_type'] == 'HY'){
            $contractModule->push_payment_order($deal, $loan_user_list, $borrow_user_info);
        }
        ################   资产收益权回购通知（借款人、出借人）         ################
        $contractPreService = new ContractPreService();
        if($contractPreService->getAssetsContTpl($deal_id)){
            $contractModule->push_buyback_notification($deal, $loan_user_list, $borrow_user_info, NULL);
            $contractModule->push_buyback_notification($deal, $loan_user_list, $borrow_user_info, 1);
        }

        /*
        * 新的合同生成逻辑开始
        */
        //借款合同-（出借人,借款人,保证方,资产管理方）
        $contractModule->push_loan_contract_v2($deal, $loan_user_list, $borrow_user_info, $agency_info, $advisory_info);

    }
    if($contractModule->save(false,false) === true){
        return true;
    }else{
        return false;
    }
}