<?php
namespace core\event;

use core\event\BaseEvent;
use NCFGroup\Task\Events\AsyncEvent;
use core\service\DealService;
use core\service\DealAgencyService;
use core\service\EarningService;
use core\service\ContractPreService;
use core\service\MsgCategoryService;
use core\dao\OpLogModel;


/**
 * SendContractEvent
 * 生成合同
 *
 * @uses AsyncEvent
 * @package default
 */
class SendContractEvent extends BaseEvent
{
    private $_deal_id;
    private $_load_id;
    private $_is_full;
    private $_op_log_id;
    private $_update_time;

    public function __construct(
        $deal_id,
        $load_id,
        $is_full,
        $op_log_id = false,
        $update_time = false
    ) {
        $this->_deal_id = $deal_id;
        $this->_load_id = $load_id;
        $this->_is_full = $is_full;
        $this->_op_log_id = $op_log_id;
        $this->_update_time = $update_time;
    }

    public function execute() {
        return true;
        \FP::import("libs.common.app");
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($this->_deal_id);

        if(empty($deal) || empty($deal['contract_tpl_type'])){
            return true;//该类标不需要生成合同
            throw new \Exception("合同生成失败，DEAL不存在或类型错误，deal_id:{$this->_deal_id}");
            return false;
        }
        require_once(APP_ROOT_PATH."system/libs/send_contract.php");
        $contractModule = new \sendContract();  //引入合同操作类

        $notice_contrace = array();
        $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal); //借款人 或公司信息

        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
        $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//咨询公司信息

        $loan_user_list = $GLOBALS['db']->getAll("SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time FROM ".DB_PREFIX."deal_load as d,".DB_PREFIX."user as u WHERE d.id = '".$this->_load_id."' AND d.user_id = u.id");
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
        if($this->_is_full === true){
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
            if($contractPreService->getAssetsContTpl($this->_deal_id)){
                $contractModule->push_buyback_notification($deal, $loan_user_list, $borrow_user_info, NULL);
                $contractModule->push_buyback_notification($deal, $loan_user_list, $borrow_user_info, 1);
            }

            /*
            * 新的合同生成逻辑开始
            */
            //借款合同-（出借人,借款人,保证方,资产管理方）
            $contractModule->push_loan_contract_v2($deal, $loan_user_list, $borrow_user_info, $agency_info, $advisory_info);
        }

        $model = new OpLogModel();
        if($this->_op_log_id === false){
            $row = $model->get_row_by_opname_content($model->get_opname_by_content($this->_deal_id, OpLogModel::OPNAME_DEAL_CONTRACT), $this->_load_id);
            if($row){
                $this->_op_log_id = $row['id'];
            }
        }
        //入库

        if($contractModule->save($this->_op_log_id,$this->_update_time) === true){
            return true;
        }else{
            throw new \Exception("合同生成失败，deal_id:{$this->_deal_id}, load_id:{$this->_load_id}");
            return false;
        }
    }

    public function alertMails()
    {
        return array('yangqing@ucfgroup.com');
    }
}

