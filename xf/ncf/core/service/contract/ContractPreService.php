<?php
/**
 * ContractPreService class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\service\contract;

use core\service\user\UserService;
use core\service\user\BankService;
use core\service\deal\DealService;
use core\service\deal\DealAgencyService;
use core\service\deal\EarningService;
use core\service\contract\ContractRenderService;
use core\service\contract\ContractNewService;
use core\service\contract\TplService;
use core\service\contract\ContractService;
use core\service\contract\ContractUtilsService;
use core\service\duotou\DuotouService;
use core\service\repay\DealRepayService;

use core\dao\project\DealProjectModel;
use core\dao\deal\DealModel;
use core\dao\contract\DealContractModel;
use core\dao\deal\DealAgencyModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealSiteModel;
use core\dao\deal\DealExtModel;
use core\dao\contract\ContractModel;

use core\enum\contract\ContractTplIdentifierEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractEnum;
use core\enum\duotou\CommonEnum;
use libs\utils\Finance;
use libs\utils\Logger;



/**
 * 合同预签
 *
 * @author wenyanlei@ucfgroup.com
 **/
class ContractPreService
{
    /**
     * 借款预签合同
     */
    public function getLoanContractPre($deal_id, $user_id, $money,$number=null,$create_time=0){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);
        if($deal_id <= 0 || $user_id <= 0){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail','deal_id user_id 参数错误')));
            return false;
        }
        $deal_service = new DealService();
        $deal = $this->get_deal_info($deal_id);
        $is_dt = $deal_service->isDealDT($deal_id);
        $user_info = $this->get_user_data($user_id);

        if(empty($user_info) || empty($deal)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'fail','deal user 没有查找记录')));
            return false;
        }

        $tpl_name = ContractTplIdentifierEnum::LOAN_CONT;
        $tpl_content = '';
        if (is_numeric($deal['contract_tpl_type'])) {
            $tpls  = TplService::getTplsByDealId($deal_id,0,ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
            if(!empty($tpls)){
                foreach($tpls as $tplOne){
                    if(strstr($tplOne['name'],ContractTplIdentifierEnum::LOAN_CONT)){
                        $tpl_content = $tplOne['content'];
                    }
                }
            }else{
                $tpl_content = '';
            }
        }else{
            $tpl_content = '';
        }

        Logger::info("getLoanContractPre_check cost time: get tpl end ".microtime(true));


        Logger::info("getLoanContractPre_check cost time: render start ".microtime(true));
        $contractRenderService = new ContractRenderService();
        $dealUserInfos = $contractRenderService->getDealUserInfos($deal,$user_id);
        $user_info = $dealUserInfos['userInfos'][$user_id];
        $loanInfo = $dealUserInfos['loanInfo']; // 甲方 - 借出方
        $entrustInfo = $dealUserInfos['entrustInfo'];  // 委托方
        $borrowInfo = $dealUserInfos['borrowInfo']; // 乙方 - 借款方
        $platformInfo = $dealUserInfos['platformInfo']; // 丙方 - 平台方
        $advisoryInfo = $dealUserInfos['advisoryInfo']; // 丁方 - 资产管理方
        $agencyInfo =$dealUserInfos['agencyInfo']; // 戊方 - 保证方
        $canalInfo = $dealUserInfos['canalInfo']; // 渠道方
        $borrow_user_info = $dealUserInfos['borrowUserInfoNotice']; // 乙方 - 借款方
        $borrow_bank_info =  $dealUserInfos['userInfos'][$deal['user_id']]['card']; // 乙方 - 借款方  - 银行卡
        $loan_bank_info =  $dealUserInfos['userInfos'][$user_id]['card'];
        $loan_legal_person =  $dealUserInfos['userInfos'][$user_id]['company'];

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $money);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $money;
        $deal_ext_model = new DealExtModel;
        $deal_ext = $deal_ext_model->getDealExtByDealId($deal['id']);
        $base_deal_num = $deal_ext['leasing_contract_num']?$deal_ext['leasing_contract_num']:'';
        $lessee_real_name = $deal_ext['lessee_real_name']?$deal_ext['lessee_real_name']:'';
        $leasing_contract_title = $deal_ext['leasing_contract_title'];
        switch($deal_ext['loan_application_type']){
            case '1':
                $loan_application_type = '企业经营';
                break;
            case '2':
                $loan_application_type = '短期周转';
                break;
            case '3':
                $loan_application_type = '日常消费';
                break;
            default: $loan_application_type = '其他';
        }
        $use_info = empty($deal_ext['use_info'])?$loan_application_type:$deal_ext['use_info'];

        switch($deal_ext['contract_transfer_type']){
            case '1':
                $contract_transfer_type = '债权';
                break;
            case '2':
                $contract_transfer_type = '资产收益权';
                break;
            default: $contract_transfer_type = '';
        }

        switch($deal_ext['loan_fee_rate_type']){
            case '1':
            case '5':
                $loan_fee_rate_type = 'A';
                break;
            case '2':
            case '6':
                $loan_fee_rate_type = 'B';
                break;
            case '3':
            case '4':
            case '7':
                $loan_fee_rate_type = 'C';
                break;
            default: $loan_fee_rate_type = '';
        }

        switch($deal_ext['consult_fee_rate_type']){
            case '1':
                $consult_fee_rate_type = 'A';
                break;
            case '2':
                $consult_fee_rate_type = 'B';
                break;
            case '3':
                $consult_fee_rate_type = 'C';
                break;
            default: $consult_fee_rate_type = '';
        }

        switch($deal_ext['guarantee_fee_rate_type']){
            case '1':
                $guarantee_fee_rate_type = 'A';
                break;
            case '2':
                $guarantee_fee_rate_type = 'B';
                break;
            case '3':
                $guarantee_fee_rate_type = 'C';
                break;
            default: $guarantee_fee_rate_type = '';
        }

        switch($deal_ext['pay_fee_rate_type']){
            case '1':
                $pay_fee_rate_type = 'A';
                break;
            case '2':
                $pay_fee_rate_type = 'B';
                break;
            case '3':
                $pay_fee_rate_type = 'C';
                break;
            default: $pay_fee_rate_type = '';
        }

        if ($deal['loantype'] == 5) {
            $deal_repay_time = $deal['repay_time'] . "天";
        } else {
            $deal_repay_time = $deal['repay_time'] . "个月";
        }

        // 其他模板参数
        $loan_type_mark = $contractRenderService->getLoanTypeMark($deal['loantype']);
        $repay_time_unit = $contractRenderService->getRepayTimeUnit($deal['type_id'], $deal['loantype'], $deal['repay_time'], $deal_ext['first_repay_interest_day']);

        // ---------------- 甲方 - 借出方 ----------------
        $notice['loan_name_info'] = $loanInfo['loan_name_info'];
        $notice['loan_username_info'] = $loanInfo['loan_username_info'];
        $notice['loan_credentials_info'] = $loanInfo['loan_credentials_info'];
        $notice['loan_bank_user_info'] = $loanInfo['loan_bank_user_info'];
        $notice['loan_bank_no_info'] = $loanInfo['loan_bank_no_info'];
        $notice['loan_bank_name_info'] = $loanInfo['loan_bank_name_info'];
        $notice['loan_name_info_transfer'] = $loanInfo['loan_name_info_transfer'];
        $notice['loan_username_info_transfer'] = $loanInfo['loan_username_info_transfer'];
        $notice['loan_credentials_info_transfer'] = $loanInfo['loan_credentials_info_transfer'];
        $notice['loan_bank_user_info_transfer'] = $loanInfo['loan_bank_user_info_transfer'];
        $notice['loan_bank_no_info_transfer'] = $loanInfo['loan_bank_no_info_transfer'];
        $notice['loan_bank_name_info_transfer'] = $loanInfo['loan_bank_name_info_transfer'];
        $notice['loan_major_name'] = $loanInfo['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loanInfo['loan_major_condentials_no'];
        $notice['loan_user_number'] = $loanInfo['loan_user_number'];

        // ---------------- 乙方 - 借款方 ----------------
        if($deal['deal_type'] == 0){
            $notice['borrow_name'] = '***出借成功后才可查看';
            $notice['borrow_license'] = '***出借成功后才可查看';
            $notice['borrow_agency_realname'] = '***出借成功后才可查看';
            $notice['borrow_agency_idno'] = '***出借成功后才可查看';
        }else{
            $notice['borrow_name'] = '***投资成功后才可查看';
            $notice['borrow_license'] = '***投资成功后才可查看';
            $notice['borrow_agency_realname'] = '***投资成功后才可查看';
            $notice['borrow_agency_idno'] = '***投资成功后才可查看';
        }

        $notice['borrow_user_number'] = $borrowInfo['borrow_user_number'];
        // ---------------- 丙方 - 平台方 ----------------
        $notice['platform_show_name'] = '网信理财';
        $notice['platform_domain'] = 'www.firstp2p.com';
        $notice['platform_realname'] = $platformInfo['platform_realname'];
        $notice['platform_address'] = $platformInfo['platform_address'];
        // 合同
        $notice['platform_name'] = $platformInfo['platform_name'];
        $notice['platform_agency_user_number'] = $platformInfo['platform_agency_user_number'];
        $notice['platform_license'] = $platformInfo['platform_license'];
        $notice['platform_agency_realname'] = $platformInfo['platform_agency_realname'];
        $notice['platform_agency_idno'] = $platformInfo['platform_agency_idno'];
        $notice['platform_agency_username'] = $platformInfo['platform_agency_username'];

        // ---------------- 丁方 - 资产管理方 ----------------
        $notice['advisory_name'] = $advisoryInfo['advisory_name'];
        $notice['advisory_agent_user_number'] = $advisoryInfo['advisory_agent_user_number'];
        $notice['advisory_license'] = $advisoryInfo['advisory_license'];
        $notice['advisory_agent_real_name'] = $advisoryInfo['advisory_agent_real_name'];
        $notice['advisory_agent_user_idno'] = $advisoryInfo['advisory_agent_user_idno'];
        $notice['advisory_address'] = $advisoryInfo['advisory_address'];
        $notice['advisory_realname'] = $advisoryInfo['advisory_realname'];
        $notice['advisory_agent_user_name'] = $advisoryInfo['advisory_agent_user_name'];

        // ---------------- 戊方 - 保证方 ----------------
        $notice['agency_name'] = $agencyInfo['agency_name'];
        $notice['agency_agent_user_number'] = $agencyInfo['agency_agent_user_number'];
        $notice['agency_license'] = $agencyInfo['agency_license'];
        $notice['agency_agent_real_name'] = $agencyInfo['agency_agent_real_name'];
        $notice['agency_agent_user_idno'] = $agencyInfo['agency_agent_user_idno'];
        // other
        $notice['agency_agent_user_name'] = $agencyInfo['agency_agent_user_name'];
        $notice['agency_user_realname'] = $agencyInfo['agency_user_realname'];
        $notice['agency_address'] = $agencyInfo['agency_address'];
        $notice['agency_mobile'] = $agencyInfo['agency_mobile'];
        $notice['agency_postcode'] = $agencyInfo['agency_postcode'];
        $notice['agency_fax'] = $agencyInfo['agency_fax'];
        $notice['agency_platform_realname'] = $agencyInfo['agency_platform_realname'];
        // ---------------- over - 保证方 -------------------

        //----------------- 受托方-----------------------
        $notice['entrust_name'] = $entrustInfo['agency_name'];
        $notice['entrust_agent_user_number'] = $entrustInfo['agency_agent_user_number'];
        $notice['entrust_license'] = $entrustInfo['agency_license'];
        $notice['entrust_agent_real_name'] = $entrustInfo['agency_agent_real_name'];
        $notice['entrust_agent_user_idno'] = $entrustInfo['agency_agent_user_idno'];
        $notice['entrust_address'] = $entrustInfo['agency_address'];
        $notice['entrust_agent_user_name'] = $entrustInfo['agency_agent_user_name'];

        // ---------------- 渠道方 ---------------------
        $notice['canal_name'] = $canalInfo['agency_name'];
        $notice['canal_agent_user_number'] = $canalInfo['agency_agent_user_number'];
        $notice['canal_license'] = $canalInfo['agency_license'];
        $notice['canal_agent_real_name'] = $canalInfo['agency_agent_real_name'];
        $notice['canal_agent_user_idno'] = $canalInfo['agency_agent_user_idno'];
        $notice['canal_address'] = $canalInfo['agency_address'];
        $notice['canal_agent_user_name'] = $canalInfo['agency_agent_user_name'];

        $notice['number'] = $number == ''?'[]':$number;

        $notice['contract_transfer_type'] = $contract_transfer_type;

        $notice['base_deal_num'] = $base_deal_num;
        $notice['lessee_real_name'] = $lessee_real_name;
        $notice['loan_application_type'] = $loan_application_type;
        $notice['loan_type_mark'] = $loan_type_mark;
        $notice['use_info'] = $use_info;

        $notice['loan_fee_rate_type'] = $loan_fee_rate_type;
        $notice['consult_fee_rate_type'] = $consult_fee_rate_type;
        $notice['guarantee_fee_rate_type'] = $guarantee_fee_rate_type;
        $notice['pay_fee_rate_type'] = $pay_fee_rate_type;
        $notice['leasing_contract_title'] = $leasing_contract_title;

        $notice['loan_real_name'] = $user_info['real_name'];
        $notice['loan_user_idno'] = $user_info['idno'];
        $notice['loan_address'] = $user_info['address'];
        $notice['loan_legal_person'] = $loan_legal_person['legal_person'];

        $notice['loan_money'] = $money;
        $notice['loan_money_uppercase'] = get_amount($money);
        $notice['loan_money_repay'] = $loan_money_repay;
        $notice['loan_money_repay_uppercase'] = get_amount($loan_money_repay);
        $notice['loan_money_earning'] = $loan_money_earning_format;
        $notice['loan_money_earning_uppercase'] = get_amount($loan_money_earning_format);
        $notice['loan_user_mobile'] = $user_info['mobile'];

        $notice['repay_time'] = $deal['repay_time'];
        $notice['deal_repay_time'] = $deal_repay_time;
        $notice['repay_time_unit'] = $repay_time_unit;

        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        $notice['start_time'] = '[]';
        $notice['end_time'] = '[]';
        $notice['borrow_sign_time'] = '合同签署之日';
        $notice['advisory_sign_time'] = '合同签署之日';
        $notice['agency_sign_time'] = '合同签署之日';
        $notice['entrust_sign_time'] = '合同签署之日';


        $notice['rate'] = format_rate_for_cont($deal['int_rate']);
        $notice['guarantee_fee_rate'] = format_rate_for_cont($deal['guarantee_fee_rate']);
        $notice['pay_fee_rate'] = format_rate_for_cont($deal['pay_fee_rate']);
        $notice['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];

        $notice['leasing_money'] = format_rate_for_cont($deal['borrow_amount']);
        $notice['leasing_money_uppercase'] = get_amount($deal['borrow_amount']);

        $notice['loan_user_name'] = $user_info['user_name'];
        $notice['loan_bank_user'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
        $notice['loan_bank_card'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
        $notice['loan_bank_name'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

        $notice['borrow_bank_user'] = $borrow_bank_info['card_name'];
        $notice['borrow_bank_card'] = $borrow_bank_info['bankcard'];
        $notice['borrow_bank_name'] = $borrow_bank_info['bankname'];

        $notice['house_address'] = $deal['house_address'];
        $notice['house_sn'] = $deal['house_sn'];

        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);

        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));

        $notice['borrow_money'] = $deal['borrow_amount'];
        $notice['uppercase_borrow_money'] = get_amount($deal['borrow_amount']);
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);

        $notice['consult_fee_rate'] = format_rate_for_cont($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));

        $notice['prepayment_day_restrict'] = $deal['prepay_days_limit'];
        $notice['prepayment_penalty_ratio'] = format_rate_for_cont($deal['prepay_rate']);
        $notice['overdue_break_days'] = $deal['overdue_break_days'];

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

        $notice['prepay_penalty_days'] = $deal['prepay_penalty_days'];//提前还款罚息天数
        $notice['redemption_period'] = isset($deal['redemption_period']) ? $deal['redemption_period'] : '';
        $notice['lock_period'] = isset($deal['lock_period']) ? $deal['lock_period'] : '';
        $notice['rate_day'] = isset($deal['rate_day']) ? format_rate_for_db($deal['rate_day']) : '';

        $notice['overdue_ratio'] = format_rate_for_cont($deal['overdue_rate']);

        $notice['first_repay_interest_day'] = to_date($deal_ext['first_repay_interest_day'], "Y年m月d日"); // 第一期还款日

        //fature 4477
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['min_loan_money_uppercase'] = get_amount($deal['min_loan_money']);
        $notice['project_borrow_amount'] = intval($deal['project_info']['borrow_amount']);
        $notice['project_borrow_amount_uppercase'] = get_amount($deal['project_info']['borrow_amount']);

        //签署时间相关
        $dealContractModel = new DealContractModel();
        $deal_contract = $dealContractModel->findAll("deal_id = '".$deal['id']."'",true);

        foreach($deal_contract as $k=>$v){
            //借款人
            if($v['user_id'] === $deal['user_id']){
                if($v['sign_time'] <> 0){
                    $notice['borrow_sign_time'] = date('Y年m月d日',$v['sign_time']);
                }
            }
            //担保公司
            elseif($v['agency_id'] == $deal['agency_id']){
                if($v['sign_time'] <> 0){
                    $notice['agency_sign_time'] = date('Y年m月d日',$v['sign_time']);
                }
            }
            //资产管理
            elseif($v['agency_id'] == $deal['advisory_id']){
                if($v['sign_time'] <> 0){
                    $notice['advisory_sign_time'] = date('Y年m月d日',$v['sign_time']);
                }
            }

            if($create_time > 0){
                $notice['sign_time'] = date('Y年m月d日',$create_time);
            }
        }

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        if(!$is_dt) {
            //JIRA 3290 START

            if($deal['deal_type'] == 0){
                // 标准企业权益转让合同（固定域名),通知贷企业借款合同
                $notice['company_name'] = "***出借成功后才可查看";

                // 标准企业权益转让合同（固定域名),通知贷企业借款合同
                $notice['company_license'] = "***出借成功后才可查看";

                // 标准企业权益转让合同（固定域名),标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同,通知贷个人借款合同
                $notice['borrow_real_name'] = "***出借成功后才可查看";

                //通知贷个人借款合同
                $notice['borrow_user_name'] = "***出借成功后才可查看";

                $notice['borrow_name'] = "***出借成功后才可查看";

                // 标准企业权益转让合同（固定域名),标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同
                $notice['borrow_user_number'] = "***出借成功后才可查看";

                // 标准企业权益转让合同（固定域名),,标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同,通知贷个人借款合同
                $notice['borrow_user_idno'] = "***出借成功后才可查看";

                // 标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷个人借款合同
                $notice['borrow_bank_user'] = "***出借成功后才可查看";

                // 标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷个人借款合同
                $notice['borrow_bank_card'] = "***出借成功后才可查看";

                // 标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）
                $notice['borrow_bank_name'] = "***出借成功后才可查看";

                //通知贷企业借款合同
                $notice['company_address_current'] = "***出借成功后才可查看";

                //通知贷企业借款合同
                $notice['company_legal_person'] = "***出借成功后才可查看";
            }else{
                // 标准企业权益转让合同（固定域名),通知贷企业借款合同
                $notice['company_name'] = "***投资成功后才可查看";

                // 标准企业权益转让合同（固定域名),通知贷企业借款合同
                $notice['company_license'] = "***投资成功后才可查看";

                // 标准企业权益转让合同（固定域名),标准个人借款合同（固定域名）,标准个人权益转让合同（固定域名）,标准个人借款合同（首山专用）,通知贷企业借款合同,通知贷个人借款合同
                $notice['borrow_real_name'] = "***投资成功后才可查看";

                //通知贷个人借款合同
                $notice['borrow_user_name'] = "***投资成功后才可查看";

                $notice['borrow_name'] = "***投资成功后才可查看";

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
            }

            Logger::info("getLoanContractPre_check cost time: render end " . microtime(true));
        }
        Logger::info("getLoanContractPre_check cost time: end ".microtime(true));
        return ContractUtilsService::fetchContent($notice,$tpl_content);
    }


    /**
     * 借款预签合同
     * 智多新-借款合同
     */
    public function getDtbContractPre($deal_id, $user_id, $money=0, $num=null, $create_time=null){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal = array('user_id' => 0,'agency_id'=>0,'entrust_agency_id'=>0,'canal_agency_id'=>0,'advisory_id'=>0,'id'=>0,'contract_tpl_type'=>0);
        $deal_site_id = 0;
        // 获取平台方和投资人信息
        $contractRenderService = new ContractRenderService();
        $dealUserInfos = $contractRenderService->getDealUserInfos($deal,$user_id,$deal_site_id);
        // 甲方 - 借出方
        $loan_info = $dealUserInfos['loanInfo'];
        $loan_legal_person =  $dealUserInfos['userInfos'][$user_id]['company'];
        // 丙方 - 平台方
        $platformInfo = $dealUserInfos['platformInfo'];
        $deal_agency =  $dealUserInfos['agencyInfos']['platformInfo'];
        $platform_agency_user = $dealUserInfos['userInfos'][$deal_agency['agency_user_id']];

        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project', 'getProjectInfoById', array('project_id'=>$deal_id)));
        $projectInfo = $response['data'];

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

        $notice['rate'] = format_rate_for_cont($projectInfo['rateDay']);
        $notice['fee_rate'] = format_rate_for_cont($projectInfo['feeRate']);
        $notice['fee_days'] = $projectInfo['feeDays'];
        //咨询顾问费率
        $notice['consult_fee_rate'] = format_rate_for_cont(bcsub(CommonEnum::P2P_RATE_YEAR ,$projectInfo['rateYear'],5));

        $notice['loan_real_name'] = $loan_info['loan_name_info'];
        $notice['loan_user_name'] = $loan_info['loan_username_info'];
        $notice['loan_user_idno'] = $loan_info['loan_credentials_info'];
        $notice['loan_user_number'] = $loan_info['loan_user_number'];
        $notice['loan_bank_user'] = $loan_info['loan_bank_user_info'];
        $notice['loan_bank_card'] = $loan_info['loan_bank_no_info'];
        $notice['loan_bank_name'] = $loan_info['loan_bank_name_info'];
        $notice['loan_major_name'] = $loan_info['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loan_info['loan_major_condentials_no'];

        $notice['loan_money'] = $money;
        $notice['uppercase_loan_money'] = get_amount($money);


        if(isset($create_time) && ($create_time != null)){
            $notice['sign_time'] = date('Y年m月d日',$create_time);
        }else{
            $notice['sign_time'] = date('Y年m月d日',time());
        }

        $notice['number'] = $num;
        return $this->getFetchedContract($notice,$deal_id,ContractServiceEnum::TYPE_DT,ContractTplIdentifierEnum::DTB_CONT);
    }

    /**
     * 获取渲染后的合同
     * @param array $notice 用于渲染合同模板的变量
     * @param integer $serviceId 智多新，为智多鑫项目id
     * @param integer $serviceType  1：智多新
     * @param string $tplPrefix  合同模板 前缀（用于区分）
     * @return string 渲染后的合同字符串
     */
    public function getFetchedContract($notice,$serviceId,$serviceType,$tplPrefix){
        if(empty($notice)){
            $notice = [
                'number'			        =>"",
                'transfer_real_name'		=>"",
                'transfer_user_number'		=>"",
                'transfer_idno'			    =>"",
                'loan_real_name'		    =>"",
                'loan_user_number'		    =>"",
                'loan_user_idno'		    =>"",
                'leasing_contract_num'		=>"【&nbsp;】",
                'transfer_money'		    =>"【&nbsp;】",
                'transfer_money_uppercase'	=>"【&nbsp;】",
                'sign_time'			        =>"",
                // 金额和编号显示为中括号
            ];
        }
        if(empty($serviceId) || empty($serviceType) || empty($tplPrefix)){
            return '';
        }
        $response = TplService::getTplByName(intval($serviceId), $tplPrefix, $serviceType);
        if(empty($response)){
            return '';
        }
        return ContractUtilsService::fetchContent($notice,$response[0]['content']);
    }

    /**
     * 智多新 投资顾问协议
     * @param int $deal_id 智多鑫项目id 1004 用于获取合同模板
     * @param int $user_id 受让方用户id
     * @param int $money 投资金额
     * @param string $num 合同编号
     * @param int $create_time 签署时间(该受让方用户加入智多新的时间)
     * @return string 渲染好的合同
     */
    public function getDtbContractInvest($deal_id, $user_id, $money=0, $num=null, $create_time=null){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal = array('user_id' => 0);
        $deal_site_id = 0;
        // 获取平台方和投资人信息
        $contractRenderService = new ContractRenderService();
        $dealUserInfos = $contractRenderService->getDealUserInfos($deal,$user_id,$deal_site_id);
        // 甲方 - 借出方
        $loan_info = $dealUserInfos['loanInfo'];
        $loan_legal_person =  $dealUserInfos['userInfos'][$user_id]['company'];
        // 丙方 - 平台方
        $platformInfo = $dealUserInfos['platformInfo'];
        $deal_agency =  $dealUserInfos['agencyInfos']['platformInfo'];
        $platform_agency_user = isset($dealUserInfos['userInfos'][$deal_agency['agency_user_id']]) ? $dealUserInfos['userInfos'][$deal_agency['agency_user_id']] : array();

        $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\Project','getProjectInfoById', array('project_id' =>  $deal_id)));
        $projectInfo = $response['data'];

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

        $notice['rate'] = format_rate_for_cont($projectInfo['rateDay']);
        $notice['fee_rate'] = format_rate_for_cont($projectInfo['feeRate']);
        $notice['fee_days'] = $projectInfo['feeDays'];
        //咨询顾问费率
        $notice['consult_fee_rate'] = format_rate_for_cont(bcsub(CommonEnum::P2P_RATE_YEAR ,$projectInfo['rateYear'],5));


        $notice['loan_real_name'] = $loan_info['loan_name_info'];
        $notice['loan_user_name'] = $loan_info['loan_username_info'];
        $notice['loan_user_idno'] = $loan_info['loan_credentials_info'];
        $notice['loan_user_number'] = $loan_info['loan_user_number'];
        $notice['loan_bank_user'] = $loan_info['loan_bank_user_info'];
        $notice['loan_bank_card'] = $loan_info['loan_bank_no_info'];
        $notice['loan_bank_name'] = $loan_info['loan_bank_name_info'];
        $notice['loan_major_name'] = $loan_info['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loan_info['loan_major_condentials_no'];

        $notice['loan_money'] = $money;
        $notice['uppercase_loan_money'] = get_amount($money);


        if(isset($create_time) && ($create_time != null)){
            $notice['sign_time'] = date('Y年m月d日',$create_time);
        }else{
            $notice['sign_time'] = date('Y年m月d日',time());
        }

        $notice['number'] = $num;

        // 通过投资时间来获取对应合同版本
        $tpl_prefix = ContractTplIdentifierEnum::DTB_CONT;
        $time = !empty($create_time) ?  intval($create_time) : time();
        $response = TplService::getTplByTime(intval($deal_id),$time,$tpl_prefix,ContractServiceEnum::TYPE_DT);

        return ContractUtilsService::fetchContent($notice,$response[0]['content']);
    }

    /**
     * 智多新 债权转让协议
     * @param int $deal_id 智多鑫项目id 1004 用于获取合同模板
     * @param int $user_id 受让方用户id
     * @param int $transfer_uid 转让方用户id
     * @param int $p2p_deal_id 底层p2p标的id
     * @param int $money 债转金额
     * @param string $num 合同编号
     * @param int $create_time 签署时间
     * @param int $dtRecordId 债转记录id
     * @param int $dtLoanId 加入智多鑫 投资记录id ($dtRecordId和$dtLoanId可以知道 firstp2p_deal_load的id)
     * @return string 渲染好的合同
     */
    public function getDtbLoanTransfer($deal_id, $user_id,$transfer_uid,$p2p_deal_id, $money=0, $num=null, $create_time, $dtRecordId = 0,$dtLoanId = 0){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        $deal_load_model = new DealLoadModel();
        $leasingContractNumber = '';
        //  通过 $dtRecordId 获取唯一一条多投记录
        if(($dtRecordId > 0)&&($dtLoanId > 0)){
            $vars = array(
                'id' => $dtRecordId,
                'loanId' => $dtLoanId,
            );
            $dtResponse = DuotouService::callByObject(array('NCFGroup\Duotou\Services\LoanMappingContract', 'getP2pLoadIds', $vars));
            if(($dtResponse['errCode'] == 0) && (count($dtResponse['data']) > 0)){
                foreach($dtResponse['data'] as $p2pLoadId){
                    $deal_load = $deal_load_model->find($p2pLoadId);
                    $contract = ContractService::getContractByLoadId($p2p_deal_id,$p2pLoadId,$deal_load['user_id'],0,false);
                    if(isset($contract[0]['number'])){
                        $numbers[] = $contract[0]['number'];
                    }
                }
                $leasingContractNumber = implode(', ',$numbers);
            }
        }

        $dt_agency_id = app_conf('AGENCY_ID_DT_PRINCIPAL');

        $p2p_deal = $this->get_deal_info($p2p_deal_id);
        $contractRenderService = new ContractRenderService();
        $deal_site_id = 0;
        $dealUserInfos = $contractRenderService->getDealUserInfos($p2p_deal,$user_id,$deal_site_id);

        $transUserInfo = UserService::getUserInfoForContractByUserId($transfer_uid);
        $transUserInfo[$transfer_uid]['user']['id'] = $transfer_uid;
        $transfer_user_info = ContractRenderService::getLoanInfo($transUserInfo[$transfer_uid]); // 乙方 - 借款方 -  债权方 - $transfer_uid

        $loan_info = $dealUserInfos['loanInfo']; // 甲方 - 借出方
        $loan_legal_person =  $dealUserInfos['userInfos'][$user_id]['company'];
        $entrustInfo = $dealUserInfos['entrustInfo'];  // 委托方
        $advisoryInfo = $dealUserInfos['advisoryInfo']; // 丁方 - 资产管理方
        $advisory_info = $dealUserInfos['agencyInfos']['advisoryInfo']; // 丁方 - 资产管理方
        $advisory_user = $dealUserInfos['userInfos'][$advisory_info['user_id']]['user'];// 丁方 - 资产管理方
        $agencyInfo =$dealUserInfos['agencyInfo']; // 戊方 - 保证方
        $agency_info = $dealUserInfos['agencyInfos']['agencyInfo']; // 戊方 - 保证方
        $canalInfo = $dealUserInfos['canalInfo']; // 渠道方
        $platformInfo = $dealUserInfos['platformInfo']; // 丙方 - 平台方
        // 丙方 - 平台方
        $deal_agency = $dealUserInfos['agencyInfos']['platformInfo'];
        if($deal_agency['agency_user_id'] > 0){
            $platform_agency_user = $advisory_user = $dealUserInfos['userInfos'][$deal_agency['agency_user_id']]['user'];
        }

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

        //暂时使用主站平台名称，后续使用独立icp
        $notice['platform_show_name'] = '网信理财';
        $notice['platform_domain'] = 'www.firstp2p.com';

        //平台方
        $notice['platform_name'] = $deal_agency['name'];
        $notice['platform_license'] = $deal_agency['license'];
        $notice['platform_address'] = $deal_agency['address'];
        $notice['platform_realname'] = $deal_agency['realname'];
        $notice['platform_agency_realname'] = isset($platform_agency_user['real_name']) ? $platform_agency_user['real_name'] : '';
        $notice['platform_agency_username'] = isset($platform_agency_user['user_name']) ? $platform_agency_user['user_name'] : '';
        $notice['platform_agency_idno'] = isset($platform_agency_user['idno']) ? $platform_agency_user['idno'] : '';

        //DT-299 企业户显示
        $notice['loan_real_name'] = $loan_info['loan_name_info'];
        $notice['loan_user_name'] = $loan_info['loan_username_info'];
        $notice['loan_user_idno'] = $loan_info['loan_credentials_info'];
        $notice['loan_user_number'] = $loan_info['loan_user_number'];
        $notice['loan_bank_user'] = $loan_info['loan_bank_user_info'];
        $notice['loan_bank_card'] = $loan_info['loan_bank_no_info'];
        $notice['loan_bank_name'] = $loan_info['loan_bank_name_info'];
        $notice['loan_major_name'] = $loan_info['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loan_info['loan_major_condentials_no'];


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

        // leasing_money，leasing_money_uppercase要使用的，最老一版合同有使用，最新一版合同没有使用
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
        $notice['transfer_days'] = floor(abs($t1-$t2)/86400);  //合同有使用

        // 通过投资时间来获取对应合同版本
        $tpl_prefix = ContractTplIdentifierEnum::DTB_TRANSFER;
        $time = !empty($create_time) ?  intval($create_time) : time();
        $response = TplService::getTplByTime(intval($deal_id),$time,$tpl_prefix,ContractServiceEnum::TYPE_DT);

        return ContractUtilsService::fetchContent($notice,$response[0]['content']);
    }

    /**
     * 获取渲染后的随心约预约协议
     * @param int $user_id,
     * @param int $money  元
     * @param int $invest_deadline,
     * @param int $invest_deadline_unit,
     * @param float $invest_rate 百分比
     * @param int $start_time
     * @param string $number 合同编号
     * @return string $result 合同
     */
    public static function getReservationContract($user_id,$money,$invest_deadline,$invest_deadline_unit,$invest_rate,$start_time = 0,$number=''){
        try{
            if(empty($user_id)){
                throw new \Exception('user_id为空');
            }
            $loanUserInfo = UserService::getUserInfoForContractByUserId($user_id);
            $loanUserInfo = isset($loanUserInfo[$user_id]) ? $loanUserInfo[$user_id] : array();
            if(empty($loanUserInfo)){
                throw new \Exception($user_id.' 查不到该用户');
            }
            $loanInfo = ContractRenderService::getLoanInfo($loanUserInfo);
            $notice = array();
            $notice['number'] = $number;
            $notice['loan_name_info'] = $loanInfo['loan_name_info'];
            $notice['loan_credentials_info'] = $loanInfo['loan_credentials_info'];
            $notice['sign_time'] = date('Y年m月d日',$start_time);
            $notice['loan_money'] = $money;
            $notice['deal_repay_time'] = '';
            if(!empty($invest_deadline_unit) && !empty($invest_deadline)){
                $notice['deal_repay_time'] = ($invest_deadline_unit == 2) ? $invest_deadline . '个月' : $invest_deadline .'天';
            }
            $notice['rate'] = format_rate_for_cont($invest_rate);
            // 通过投资时间来获取对应合同版本
            $tpl_prefix = ContractTplIdentifierEnum::RESERVATION_CONT;
            $time = !empty($start_time) ?  intval($start_time) : time();
            $response = TplService::getTplByTime(ContractServiceEnum::RESERVATION_PROJECT_ID,$time,$tpl_prefix,ContractServiceEnum::TYPE_RESERVATION);
            if(!isset($response[0]['content'])){
                throw new \Exception('合同模板获取失败');
            }
            return ContractUtilsService::fetchContent($notice,$response[0]['content']);
        }catch(\Exception $e){
            Logger::error(implode(' | ',array(__FILE__,__FUNCTION__,__LINE__,'错误原因:'.$e->getMessage())));
            return '';
        }
    }


    /**
     * 获取user信息
     */
    private function get_user_data($user_id){
        $user_id = intval($user_id);
        if($user_id <= 0){
            return array();
        }
        static $user_info = array();
        if(!isset($user_info[$user_id])){
            $user_info[$user_id] = UserService::getUserById($user_id,' * ');
        }
        return $user_info[$user_id];
    }

    /**
     * 获取标的信息
     */
    private function get_deal_info($deal_id){
        $deal_id = intval($deal_id);
        if($deal_id <= 0){
            return array();
        }
        static $deal_info = array();
        if(!isset($deal_info[$deal_id])){
            $deal_service = new DealService();
            $deal_project_model = new DealProjectModel();
            $deal_info[$deal_id] = $deal_service->getDeal($deal_id, true);
            if(empty($deal_info[$deal_id])){
                return array();
            }
            $deal_info[$deal_id]['project_info'] = $deal_project_model->find($deal_info[$deal_id]['project_id']);
        }
        return $deal_info[$deal_id];
    }

    /**
     * 获取合同模板
     */
    public function getDealContPreTemplate($deal_id,$deal_type = '')
    {
        $deal = $this->get_deal_info($deal_id);
        $cont_new_service = new ContractNewService();
        if($deal_type == 'duotou'){
            $dtb_cont['contract_title'] = "智多新投资协议";
            $cont_pre = array(
                'dtb_cont' => $dtb_cont,
                'buyback_cont' => 0,
            );
            return $cont_pre;
        } elseif ($cont_new_service->isAttachmentContract($deal['contract_tpl_type'])) {
            $cont_list = $cont_new_service->getContractAttachmentByDealLoad($deal_id);
            return array('cont_list' => $cont_list, 'is_attachment' => true);
        }

        if(empty($deal)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'deal不能为空')));
            return false;
        }
        if(!is_numeric($deal['contract_tpl_type'])){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,'contract_tpl_type必须为数字')));
            return false;
        }

        $deal_service = new DealService();

        $prefix = ContractTplIdentifierEnum::LOAN_CONT.'_V2';
        $loan_response = TplService::getTplByName(intval($deal_id),$prefix,ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
        $loan_cont = $loan_response[0];
        $loan_cont['contract_title'] = !empty($loan_cont['contractTitle']) ? $loan_cont['contractTitle'] : '借款合同';

        $warrant_prefix = ContractTplIdentifierEnum::WARRANT_CONT;
        $warrant_response = TplService::getTplByName(intval($deal_id),$warrant_prefix,ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
        $warrant_cont = isset($warrant_response[0]) ? $warrant_response[0] : array();//todo 确定这个保证合同是否需要使用
        $warrant_cont['contract_title'] = !empty($warrant_cont['contractTitle']) ? $warrant_cont['contractTitle'] : '保证合同';

        $lender_prefix = ContractTplIdentifierEnum::LENDER_CONT;
        $lender_response = TplService::getTplByName(intval($deal_id),$lender_prefix ,ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
        $lender_cont = isset($lender_response[0]) ? $lender_response[0] : array();//todo 确定这个出借人咨询服务协议是否需要使用
        $lender_cont['contract_title'] = !empty($lender_cont['contract_title']) ? $lender_cont['contractTitle'] : '出借人咨询服务协议';

        $cont_pre = array(
            'loan_cont' => $loan_cont,
            'warrant_cont' => $warrant_cont,
            'lender_cont' => $lender_cont,
        );

        return $cont_pre;
    }

    /**
     * 根据用户id获取银行卡信息
     * @author wenyanlei  2013-7-15
     * @param  $userid    用户id
     * @return array
     */
    private function get_user_bank($user_id = 0){
        if($user_id <= 0) {
            return array();
        }

        $bankcard_info = BankService::getNewCardByUserId($user_id);
        $loan_bank_info =  BankService::getBankInfoByBankId($bankcard_info['bank_id']);
        $bankcard_info['bankname'] = $loan_bank_info['name'];
        return $bankcard_info;
    }
}
