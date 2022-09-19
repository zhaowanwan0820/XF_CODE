<?php
/**
 * ContractPreService class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\service;

use core\dao\DealProjectModel;
use core\dao\MsgTemplateModel;
use core\service\UserService;
use core\service\DealService;
use core\service\DealAgencyService;
use core\service\contract\ContractUtilsService;
use core\service\contract\TplService;
use core\service\EarningService;
use libs\utils\Finance;
use core\dao\DealModel;
use core\dao\DealContractModel;
use core\dao\DealAgencyModel;
use core\dao\DealLoadModel;
use core\dao\DealSiteModel;
use core\dao\DealExtModel;
use core\dao\ContractModel;
use core\dao\UserModel;
use core\service\ContractRenderService;
use core\service\ContractNewService;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use NCFGroup\Protos\Contract\RequestGetTplsByDealId;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;

\FP::import("libs.common.app");

/**
 * 合同预签
 *
 * @author wenyanlei@ucfgroup.com
 **/
class ContractPreService
{
    const LOAN_CONT = 'TPL_LOAN_CONTRACT';
    const WARRANT_CONT = 'TPL_WARRANT_CONTRACT';
    const LENDER_CONT = 'TPL_LENDER_PROTOCAL';
    const BUYBACK_CONT = 'TPL_BUYBACK_NOTIFICATION';
    const DTB_CONT = 'TPL_DTB_INVEST_PROTOCAL';
    const DTB_TRANSFER = 'TPL_DTB_LOAN_TRANSFER';
    const ENTRUST_CONT = "TPL_ENTRUST_ZX_CONTRACT";
    const GOLD_LOAN = "TPL_GOLD_LOAN_PROTOCAL";

    //交易所
    const SUBSCRIBE_CONT = 'TPL_SUBSCRIBE_PROCOTAL'; //交易所-认购协议
    const PERCEPTION_CONT = 'TPL_PERCEPTION_OF_RISK'; //交易所--风险认知书
    const RAISE_CONT = 'TPL_RAISE_DESCRIPTION'; //交易所-募集说明书
    const QUALIFIED_CONT = 'TPL_QUALIFIED_INVESTOR_STANDARD'; //交易所-合格投资者标准

    /**
     * 借款预签合同
     */
    public function getLoanContractPre($deal_id, $user_id, $money,$number=null,$create_time=0){

        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);
        $tpl_prefix = self::LOAN_CONT;

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal_service = new DealService();
        /* $deal = $deal_service->getDeal($deal_id); */
        $deal = $this->get_deal_info($deal_id);
        $is_dt = $deal_service->isDealDT($deal_id);

        /* $user_service = new UserService();
         $user_info = $user_service->getUser($user_id); */
        $user_info = $this->get_user_data($user_id);

        if(empty($user_info) || empty($deal)){
            return false;
        }

        $user_company_service = new UserCompanyService();
        $loan_legal_person = $user_company_service->getCompanyLegalInfo($user_id);

        $tpl_name = self::LOAN_CONT;
        if($deal['contract_tpl_type'] != 'DF'){
            if(is_numeric($deal['contract_tpl_type'])) {
                $request = new \NCFGroup\Protos\Contract\RequestGetTplsByDealId();
                $request->setDealId($deal_id);
                $request->setType(0);
                $request->setSourceType($deal['deal_type']);
                $response = $GLOBALS['contractRpc']->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Tpl",
                    'method' => "getTplsByDealId",
                    'args' => $request,
                ));

                if($response->errorCode == 0){
                    $tpls = $response->list['data'];

                    foreach($tpls as $tplOne){
                        if(strstr($tplOne['name'],self::LOAN_CONT)){
                            $tpl_content = $tplOne['content'];
                        }
                    }
                }else{
                    $tpl_content = '';
                }
            }else{
                if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                    $tpl_name .= '_V2_'.$deal['contract_tpl_type'];
                    $contract_version = 2;
                }else{
                    $tpl_name .= '_'.$deal['contract_tpl_type'];
                    $contract_version = 1;
                }
                //$tpl = MsgTemplateModel::instance()->findBy("name='".$tpl_name."'");
                $tpl = MsgTemplateModel::instance()->getTemplateByName($tpl_name);
                $tpl_content = $tpl['content'];
            }
        }
        Logger::info("getLoanContractPre_check cost time: get tpl end ".microtime(true));

        $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
        Logger::info("getLoanContractPre_check cost time: render start ".microtime(true));

        $dealagency_service = new DealAgencyService();

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

        //获取用户的银行卡信息
        $loan_bank_info = get_user_bank($user_id);
        $borrow_bank_info = get_user_bank($deal['user_id']);

        // JIRA#3260-企业账户二期 <fanjingwen@>
        $contractRenderService = new ContractRenderService();
        $loanInfo = $contractRenderService->getLoanInfo($user_info, $loan_bank_info); // 甲方 - 借出方
        $borrowInfo = $contractRenderService->getBorrowInfo($deal['user_id']);// 乙方 - 借款方
        $platformInfo = $contractRenderService->getPlatformInfo($deal['id']); // 丙方 - 平台方
        $advisoryInfo = $contractRenderService->getAdvisoryInfo($deal['advisory_id']); // 丁方 - 资产管理方
        $agencyInfo = $contractRenderService->getAgencyInfo($deal['agency_id']); // 戊方 - 保证方
        $entrustInfo = $contractRenderService->getAgencyInfo($deal['entrust_agency_id']);

        // JIRA#5243-新增渠道方
        $canalInfo = $contractRenderService->getAgencyInfo($deal['canal_agency_id']);

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
        $notice['agency_user_realname'] = $agencyInfo['realname'];
        $notice['agency_address'] = $agencyInfo['address'];
        $notice['agency_mobile'] = $agencyInfo['mobile'];
        $notice['agency_postcode'] = $agencyInfo['postcode'];
        $notice['agency_fax'] = $agencyInfo['fax'];
        $notice['agency_platform_realname'] = $agencyInfo['agency_platform_realname'];
        // ---------------- over - 保证方 -------------------

        //----------------- 受托方-----------------------
        $notice['entrust_name'] = $entrustInfo['agency_name'];
        $notice['entrust_agent_user_number'] = $entrustInfo['agency_agent_user_number'];
        $notice['entrust_license'] = $entrustInfo['agency_license'];
        $notice['entrust_agent_real_name'] = $entrustInfo['agency_agent_real_name'];
        $notice['entrust_agent_user_idno'] = $entrustInfo['agency_agent_user_idno'];
        $notice['entrust_address'] = $entrustInfo['agency_address'];
        $notice['entrust_realname'] = $entrustInfo['agency_realname'];
        $notice['entrust_agent_user_name'] = $entrustInfo['agency_agent_user_name'];

        // ---------------- 渠道方 ---------------------
        $notice['canal_name'] = $canalInfo['agency_name'];
        $notice['canal_agent_user_number'] = $canalInfo['agency_agent_user_number'];
        $notice['canal_license'] = $canalInfo['agency_license'];
        $notice['canal_agent_real_name'] = $canalInfo['agency_agent_real_name'];
        $notice['canal_agent_user_idno'] = $canalInfo['agency_agent_user_idno'];
        $notice['canal_address'] = $canalInfo['agency_address'];
        $notice['canal_realname'] = $canalInfo['agency_realname'];
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

        //JIRA 3290 END

        $GLOBALS['tmpl']->assign("notice",$notice);
        Logger::info("getLoanContractPre_check cost time: end ".microtime(true));
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    public function getEntrustContractPre($deal_id, $user_id,$money){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);

        $tpl_prefix = self::ENTRUST_CONT;

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal_service = new DealService();
        $deal = $this->get_deal_info($deal_id);
        $user_info = $this->get_user_data($user_id);

        if(empty($user_info) || empty($deal)){
            return false;
        }

        $user_company_service = new UserCompanyService();
        $loan_legal_person = $user_company_service->getCompanyLegalInfo($user_id);

        $tpl_name = self::ENTRUST_CONT;


        $request = new \NCFGroup\Protos\Contract\RequestGetTplsByDealId();
        $request->setDealId($deal_id);
        $request->setType(0);
        $request->setSourceType($deal['deal_type']);
        $response = $GLOBALS['contractRpc']->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "getTplsByDealId",
            'args' => $request,
        ));

        if($response->errorCode == 0){
            $tpls = $response->list['data'];

            foreach($tpls as $tplOne){
                if(strstr($tplOne['name'],self::ENTRUST_CONT)){
                    $tpl_content = $tplOne['content'];
                }
            }
        }else{
            $tpl_content = '';
        }

        $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
        $dealagency_service = new DealAgencyService();

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

        //获取用户的银行卡信息
        $loan_bank_info = get_user_bank($user_id);
        $borrow_bank_info = get_user_bank($deal['user_id']);

        // JIRA#3260-企业账户二期 <fanjingwen@>
        $contractRenderService = new ContractRenderService();
        $loanInfo = $contractRenderService->getLoanInfo($user_info, $loan_bank_info); // 甲方 - 借出方
        $borrowInfo = $contractRenderService->getBorrowInfo($deal['user_id']);// 乙方 - 借款方
        $platformInfo = $contractRenderService->getPlatformInfo($deal['id']); // 丙方 - 平台方
        $advisoryInfo = $contractRenderService->getAdvisoryInfo($deal['advisory_id']); // 丁方 - 资产管理方
        $agencyInfo = $contractRenderService->getAgencyInfo($deal['agency_id']); // 戊方 - 保证方
        $entrustInfo = $contractRenderService->getAgencyInfo($deal['entrust_agency_id']);

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

        // ---------------- 借款方 ----------------
        $notice['borrow_name'] = '***投资成功后才可查看';
        $notice['borrow_license'] = '***投资成功后才可查看';
        $notice['borrow_agency_realname'] = '***投资成功后才可查看';
        $notice['borrow_agency_idno'] = '***投资成功后才可查看';
        $notice['borrow_user_number'] = $borrowInfo['borrow_user_number'];

        // ---------------- 受托方 ----------------
        $notice['entrust_name'] = '***投资成功后才可查看';
        $notice['entrust_license'] = '***投资成功后才可查看';
        $notice['entrust_agent_real_name'] = '***投资成功后才可查看';
        $notice['entrust_agent_user_number'] = '***投资成功后才可查看';
        $notice['entrust_agent_user_idno'] = '***投资成功后才可查看';


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
        $notice['agency_user_realname'] = $agencyInfo['realname'];
        $notice['agency_address'] = $agencyInfo['address'];
        $notice['agency_mobile'] = $agencyInfo['mobile'];
        $notice['agency_postcode'] = $agencyInfo['postcode'];
        $notice['agency_fax'] = $agencyInfo['fax'];
        $notice['agency_platform_realname'] = $agencyInfo['agency_platform_realname'];

        //----------------- 受托方-----------------------
        $notice['entrust_name'] = $entrustInfo['agency_name'];
        $notice['entrust_agent_user_number'] = $entrustInfo['agency_agent_user_number'];
        $notice['entrust_license'] = $entrustInfo['agency_license'];
        $notice['entrust_agent_real_name'] = $entrustInfo['agency_agent_real_name'];
        $notice['entrust_agent_user_idno'] = $entrustInfo['agency_agent_user_idno'];
        $notice['entrust_address'] = $entrustInfo['agency_address'];
        $notice['entrust_realname'] = $entrustInfo['agency_realname'];
        $notice['entrust_agent_user_name'] = $entrustInfo['agency_agent_user_name'];

        // ---------------- 渠道方 ---------------------
        $notice['canal_name'] = $canalInfo['agency_name'];
        $notice['canal_agent_user_number'] = $canalInfo['agency_agent_user_number'];
        $notice['canal_license'] = $canalInfo['agency_license'];
        $notice['canal_agent_real_name'] = $canalInfo['agency_agent_real_name'];
        $notice['canal_agent_user_idno'] = $canalInfo['agency_agent_user_idno'];
        $notice['canal_address'] = $canalInfo['agency_address'];
        $notice['canal_realname'] = $canalInfo['agency_realname'];
        $notice['canal_agent_user_name'] = $canalInfo['agency_agent_user_name'];

        $notice['contract_transfer_type'] = $contract_transfer_type;

        $notice['base_deal_num'] = $base_deal_num;
        $notice['lessee_real_name'] = $lessee_real_name;
        $notice['loan_application_type'] = $loan_application_type;
        $notice['use_info'] = $use_info;
        $notice['loan_type_mark'] = $loan_type_mark;

        $notice['loan_fee_rate_type'] = $loan_fee_rate_type;
        $notice['consult_fee_rate_type'] = $consult_fee_rate_type;
        $notice['guarantee_fee_rate_type'] = $guarantee_fee_rate_type;
        $notice['pay_fee_rate_type'] = $pay_fee_rate_type;
        $notice['leasing_contract_title'] = $leasing_contract_title;

        $notice['loan_real_name'] = $user_info['real_name'];
        $notice['loan_user_idno'] = $user_info['idno'];
        $notice['loan_address'] = $user_info['address'];
        $notice['loan_legal_person'] = $loan_legal_person['legal_person'];

        $notice['loan_money'] = empty($money)?0:$money;
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

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

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

        //fature 4477
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['min_loan_money_uppercase'] = get_amount($deal['min_loan_money']);
        $notice['project_borrow_amount'] = intval($deal['project_info']['borrow_amount']);
        $notice['project_borrow_amount_uppercase'] = get_amount($deal['project_info']['borrow_amount']);


        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }


    /**
     * 借款预签合同
     */
    public function getDtbContractPre($deal_id, $user_id, $money=0, $num=null, $create_time=null){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $user_info = $this->get_user_data($user_id);

        $dealagency_service = new DealAgencyService();
        $deal_site_id = 0;
        $deal_agency = $dealagency_service->getDealAgencyBySiteId($deal_site_id);

        $user_service = new UserService();
        if($deal_agency['agency_user_id'] > 0){
            $platform_agency_user = $user_service->getUser($deal_agency['agency_user_id']);
        }

        $loan_bank_info = get_user_bank($user_id);


        $rpc = new Rpc('duotouRpc');
        $projectRequest = new \NCFGroup\Protos\Duotou\RequestCommon();
        $projectRequest->setVars(array('project_id' =>  $deal_id));
        $response = $rpc->go('NCFGroup\Duotou\Services\Project','getProjectInfoById',$projectRequest);
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

        $notice['loan_money'] = $money;
        $notice['uppercase_loan_money'] = get_amount($money);


        if(isset($create_time) && ($create_time != null)){
            $notice['sign_time'] = date('Y年m月d日',$create_time);
        }else{
            $notice['sign_time'] = date('Y年m月d日',time());
        }

        $notice['number'] = $num;

        $tpl_prefix = self::DTB_CONT;
        $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
        $request->setDeal_id(intval($deal_id));
        $request->setType(1);
        $request->setTpl_prefix($tpl_prefix);
        $response = $GLOBALS['contractRpc']->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "getTplByName",
            'args' => $request,
        ));

        $tpl_content = $response->data[0]['content'];
        $GLOBALS['tmpl']->assign("notice",$notice);

        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    /**
     * 借款预签合同
     */
    public function getDtbContractInvest($deal_id, $user_id, $money=0, $num=null, $create_time=null){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $user_info = $this->get_user_data($user_id);

        $dealagency_service = new DealAgencyService();
        $deal_site_id = 0;
        $deal_agency = $dealagency_service->getDealAgencyBySiteId($deal_site_id);

        $user_service = new UserService();
        if($deal_agency['agency_user_id'] > 0){
            $platform_agency_user = $user_service->getUser($deal_agency['agency_user_id']);
        }

        $loan_bank_info = get_user_bank($user_id);


        $rpc = new Rpc('duotouRpc');
        $projectRequest = new \NCFGroup\Protos\Duotou\RequestCommon();
        $projectRequest->setVars(array('project_id' =>  $deal_id));
        $response = $rpc->go('NCFGroup\Duotou\Services\Project','getProjectInfoById',$projectRequest);
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

        $notice[''] = format_rate_for_cont($projectInfo['rateDay']);
        $notice['fee_rate'] = format_rate_for_cont($projectInfo['feeRate']);//1.000
        $notice['fee_days'] = $projectInfo['feeDays'];
        //咨询顾问费率
        $notice['consult_fee_rate'] = format_rate_for_cont(bcsub(CommonEnum::P2P_RATE_YEAR ,$projectInfo['rateYear'],5));

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

        $notice['loan_money'] = $money;
        $notice['uppercase_loan_money'] = get_amount($money);


        if(isset($create_time) && ($create_time != null)){
            $notice['sign_time'] = date('Y年m月d日',$create_time);
        }else{
            $notice['sign_time'] = date('Y年m月d日',time());
        }

        $notice['number'] = $num;

        // 通过投资时间来获取对应合同版本
        $tpl_prefix = self::DTB_CONT;
        $request = new \NCFGroup\Protos\Contract\RequestGetTplByTime();
        $request->setDealId(intval($deal_id));
        $request->setType(1);
        $request->setTplPrefix($tpl_prefix);
        !empty($create_time) ?  $request->setTime(intval($create_time)) : $request->setTime(time());
        $response = $GLOBALS['contractRpc']->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "getTplByTime",
            'args' => $request,
        ));

        $tpl_content = $response->data[0]['content'];
        $GLOBALS['tmpl']->assign("notice",$notice);

        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }



    /**
     * 多投债权转让协议
     */
    public function getDtbLoanTransfer($deal_id, $user_id,$transfer_uid,$p2p_deal_id, $money=0, $num=null, $create_time, $dtRecordId = 0,$dtLoanId = 0){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        $user_service = new UserService();

        $deal_load_model = new DealLoadModel();

        $leasingContractNumber = '';

        if(($dtRecordId > 0)&&($dtLoanId > 0)){
            $rpc = new Rpc('duotouRpc');
            $request = new \NCFGroup\Protos\Duotou\RequestCommon();
            $vars = array(
                'id' => $dtRecordId,
                'loanId' => $dtLoanId,
            );
            $request->setVars($vars);
            $dtResponse = $rpc->go('NCFGroup\Duotou\Services\LoanMappingContract', 'getP2pLoadIds', $request, 3, 10);
            if(($dtResponse['errCode'] == 0) && (count($dtResponse['data']) > 0)){
                foreach($dtResponse['data'] as $p2pLoadId){
                    $deal_load = $deal_load_model->find($p2pLoadId);
                    $numbers[] = ltrim(str_pad($p2p_deal_id,8,"0",STR_PAD_LEFT).'01'.str_pad('1',2,"0",STR_PAD_LEFT).str_pad($deal_load['user_id'],8,"0",STR_PAD_LEFT).str_pad($p2pLoadId,10,"0",STR_PAD_LEFT),'0');
                }
                $leasingContractNumber = implode(', ',$numbers);
            }
        }

        $dt_agency_id = app_conf('AGENCY_ID_DT_PRINCIPAL');

        /* $deal = $deal_service->getDeal($deal_id); */
        $p2p_deal = $this->get_deal_info($p2p_deal_id);

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

        $user_info = $this->get_user_data($user_id);

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

        $tpl_prefix = self::DTB_TRANSFER;
        // 通过投资时间来获取对应合同版本
        $request = new \NCFGroup\Protos\Contract\RequestGetTplByTime();
        $request->setDealId(intval($deal_id));
        $request->setType(1);
        $request->setTplPrefix($tpl_prefix);
        !empty($create_time) ?  $request->setTime(intval($create_time)) : $request->setTime(time());
        $response = $GLOBALS['contractRpc']->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "getTplByTime",
            'args' => $request,
        ));

        $tpl_content = $response->data[0]['content'];
        $GLOBALS['tmpl']->assign("notice",$notice);

        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
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
            $loanUser = UserModel::instance()->find($user_id); //投资人
            if(empty($loanUser)){
                throw new \Exception($user_id.' 查不到该用户');
            }
            $loan_bank_info = get_user_bank($user_id);
            $loanInfo = \core\service\contract\ContractRenderService::getLoanInfo($loanUser, $loan_bank_info);// 甲方 - 借出方
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

            $response = TplService::getTplByTime(ContractServiceEnum::RESERVATION_PROJECT_ID,$time,$tpl_prefix,ContractServiceEnum::TYPE_RESERVATION_SUPER);
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
     * 保证预签合同
     */
    public function getGuaranteeContractPre($deal_id, $user_id, $money){


        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal_service = new DealService();
        /* $deal = $deal_service->getDeal($deal_id); */
        $deal = $this->get_deal_info($deal_id);

        /* $user_service = new UserService();
        $user_info = $user_service->getUser($user_id); */
        $user_info = $this->get_user_data($user_id);

        if(empty($user_info) || empty($deal)){
            return false;
        }

        $tpl_name = self::WARRANT_CONT;
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }
        if(is_numeric($deal['contract_tpl_type'])) {
            $request = new \NCFGroup\Protos\Contract\RequestGetTplsByDealId();
            $request->setDealId($deal_id);
            $request->setType(0);
            $request->setSourceType($deal['deal_type']);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByDealId",
                'args' => $request,
            ));

            if($response->errorCode == 0){
                $tpls = $response->list['data'];

                foreach($tpls as $tplOne){
                    if(strstr($tplOne['name'],self::WARRANT_CONT)){
                        $tpl_content = $tplOne['content'];
                    }
                }
            }else{
                $tpl_content = '';
            }
        }else{
            $tpl = MsgTemplateModel::instance()->getTemplateByName($tpl_name);
            $tpl_content = $tpl['content'];
        }


        //$tpl = MsgTemplateModel::instance()->findBy("name='".$tpl_name."'");


        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
        $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);

        $deal_ext_model = new DealExtModel();
        $deal_ext = $deal_ext_model->getDealExtByDealId($deal['id']);
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

        $loan_bank_info = get_user_bank($user_id);

        $loan_user_service = new UserService($user_id);

        if($loan_user_service->isEnterprise()){
            $enterprise_info = $loan_user_service->getEnterpriseInfo(true);
            $loan_name_info = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info = '企业会员用户名 : '.$user_info['user_name'];
            $loan_credentials_info = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_address_info = "企业注册地址 ：".$enterprise_info['registration_address'];
            $loan_tel_info = "";
            $loan_email_info = "";

            $loan_name_info_transfer = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info_transfer = '企业会员用户名 : '.$user_info['user_name'];
            $loan_credentials_info_transfer = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_address_info_transfer = "企业注册地址 ：".$enterprise_info['registration_address'];
            $loan_tel_info_transfer = "";
        }else{
            $loan_name_info = '乙方 : '.$user_info['real_name'];
            $loan_username_info = '网信理财网站用户名 : '.$user_info['user_name'];
            $loan_credentials_info = '身份证号/营业执照号 : '.$user_info['idno'];
            $loan_address_info = "企业注册地址 : ".$user_info['address'];
            $loan_tel_info = "电话 : ".$user_info['mobile'];
            $loan_email_info = "电子邮箱 : ".$user_info['email'];;

            $loan_name_info_transfer = '乙方（资产收益权受让方） : '.$user_info['real_name'];
            $loan_username_info_transfer = '网信理财网站用户名 : '.$user_info['user_name'];
            $loan_credentials_info_transfer = '身份证号/营业执照号 : '.$user_info['idno'];
            $loan_address_info_transfer = "企业注册地址 : ".$user_info['address'];
            $loan_tel_info_transfer = "电话 : ".$user_info['mobile'];

        }

        $notice['number'] = '[]';
        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_mobile'] = $agency_info['mobile'];
        $notice['agency_postcode'] = $agency_info['postcode'];
        $notice['agency_fax'] = $agency_info['fax'];
        $notice['loan_real_name'] = $user_info['real_name'];

        $notice['loan_user_idno'] = !empty($user_info['idno']) ? $user_info['idno']:'';
        $notice['loan_user_address'] = !empty($user_info['address']) ? $user_info['address']:'';
        $notice['loan_user_mobile'] = $user_info['mobile'];
        $notice['loan_user_postcode'] = !empty($user_info['postcode']) ? $user_info['postcode']:'';
        $notice['loan_user_email'] = $user_info['email'];

        $notice['loan_money'] = $money;
        $notice['loan_money_up'] = get_amount($money);
        $notice['uppercase_borrow_money'] = get_amount($deal['borrow_amount']);

        $notice['sign_time'] = '合同签署之日';
        $notice['start_time'] = '[]';
        $notice['end_time'] = '[]';
        $notice['loan_contract_num'] = '[]';

        $notice['use_info'] = $use_info;
        $notice['house_address'] = $deal['house_address'];
        $notice['house_sn'] = $deal['house_sn'];

        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);

        $earning = new EarningService();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);

        $loan_money_earning = $earning->getEarningMoney($deal['id'], $money);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $money;

        $notice['loan_money_repay'] = $loan_money_repay;
        $notice['loan_money_repay_uppercase'] = get_amount($loan_money_repay);
        $notice['loan_money_earning'] = $loan_money_earning_format;
        $notice['loan_money_earning_uppercase'] = get_amount($loan_money_earning_format);

        $notice['consult_fee_rate'] = format_rate_for_cont($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));

        $notice['agency_license'] = $agency_info['license'];
        $notice['agency_agent_real_name'] = $agency_info['agency_agent_real_name'];
        $notice['agency_agent_user_name'] = $agency_info['agency_agent_user_name'];
        $notice['agency_agent_user_idno'] = $agency_info['agency_agent_user_idno'];
        $notice['overdue_break_days'] = $deal['overdue_break_days'];
        $notice['overdue_compensation_time'] = $deal['overdue_day'];
        $notice['prepayment_penalty_ratio'] = format_rate_for_cont($deal['prepay_rate']);

        $notice['loan_user_name'] = $user_info['user_name'];
        $notice['loan_bank_user'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
        $notice['loan_bank_card'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
        $notice['loan_bank_name'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';


        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

        $notice['prepay_penalty_days'] = $deal['prepay_penalty_days'];//提前还款罚息天数

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        //企业用户相关
        $notice['loan_name_info'] = $loan_name_info;
        $notice['loan_username_info'] = $loan_username_info;
        $notice['loan_credentials_info'] = $loan_credentials_info;
        $notice['loan_address_info'] = $loan_address_info;
        $notice['loan_tel_info'] = $loan_tel_info;
        $notice['loan_email_info'] = $loan_email_info;

        $notice['loan_name_info_transfer'] = $loan_name_info_transfer;
        $notice['loan_username_info_transfer'] = $loan_username_info_transfer;
        $notice['loan_credentials_info_transfer'] = $loan_credentials_info_transfer;
        $notice['loan_address_info_transfer'] = $loan_address_info_transfer;
        $notice['loan_tel_info_transfer'] = $loan_tel_info_transfer;

        //fature 4477
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['min_loan_money_uppercase'] = get_amount($deal['min_loan_money']);
        $notice['project_borrow_amount'] = intval($deal['project_info']['borrow_amount']);
        $notice['project_borrow_amount_uppercase'] = get_amount($deal['project_info']['borrow_amount']);

        //JIRA 3290 START

        //通知贷企业借款合同
        $notice['company_name'] = "***投资成功后才可查看";

        //通知贷个人借款合同
        $notice['borrow_real_name'] = "***投资成功后才可查看";

        //JIRA3290 END

        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    /**
     * 出借人平台服务协议预签
     */
    public function getLenderContractPre($deal_id, $user_id, $money){

        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal_service = new DealService();
        /* $deal = $deal_service->getDeal($deal_id); */
        $deal = $this->get_deal_info($deal_id);

        /* $user_service = new UserService();
        $user_info = $user_service->getUser($user_id); */
        $user_info = $this->get_user_data($user_id);

        if(empty($user_info) || empty($deal)){
            return false;
        }

        $tpl_name = self::LENDER_CONT;
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        if(is_numeric($deal['contract_tpl_type'])) {
            $request = new \NCFGroup\Protos\Contract\RequestGetTplsByDealId();
            $request->setDealId($deal_id);
            $request->setType(0);
            $request->setSourceType($deal['deal_type']);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByDealId",
                'args' => $request,
            ));

            if($response->errorCode == 0){
                $tpls = $response->list['data'];

                foreach($tpls as $tplOne){
                    if(strstr($tplOne['name'],self::LENDER_CONT)){
                        $tpl_content = $tplOne['content'];
                    }
                }
            }else{
                $tpl_content = '';
            }
        }else{
            $tpl = MsgTemplateModel::instance()->getTemplateByName($tpl_name);
            $tpl_content = $tpl['content'];
        }

        $deal_ext_model = new DealExtModel();
        $deal_ext = $deal_ext_model->getDealExtByDealId($deal['id']);
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

        $loan_user_service = new UserService($user_info['id']);
        if($loan_user_service->isEnterprise()){
            $enterprise_info = $loan_user_service->getEnterpriseInfo(true);
            $loan_name_info = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info = '企业会员用户名 : '.$user_info['user_name'];
            $loan_credentials_info = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_bank_user_info = '平台绑定银行开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info = '平台绑定银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info = '绑定银行账号开户行名称 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            $loan_name_info_transfer = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info_transfer = '企业会员用户名 : '.$user_info['user_name'];
            $loan_credentials_info_transfer = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_bank_user_info_transfer = '平台绑定银行开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info_transfer = '平台绑定银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info_transfer = '绑定银行账号开户行名称 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        }else{
            //借款合同
            $loan_name_info = '乙方（出借方） : '.$user_info['real_name'];
            $loan_username_info = '网信理财网站用户名 : '.$user_info['user_name'];
            $loan_credentials_info = '身份证号/营业执照号 : '.$user_info['idno'];
            $loan_bank_user_info = '开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info = '银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info = '开户行 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            $loan_name_info_transfer = '乙方（资产收益权受让方） : '.$user_info['real_name'];
            $loan_username_info_transfer = '网信理财网站用户名 : '.$user_info['user_name'];
            $loan_credentials_info_transfer = '身份证号/营业执照号 : '.$user_info['idno'];
            $loan_bank_user_info_transfer = '开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info_transfer = '银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info_transfer = '开户行 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        }

        $notice = array();
        $notice['loan_user_idno'] = $user_info['idno'];
        $notice['loan_real_name'] = $user_info['real_name'];
        $notice['loan_address'] = $user_info['address'];
        $notice['loan_phone'] = $user_info['mobile'];
        $notice['loan_email'] = $user_info['email'];
        $notice['loan_user_number'] = numTo32($user_info['id']);
        $notice['borrow_user_number'] = numTo32($deal['user_id']);
        $notice['manage_fee_rate'] = format_rate_for_cont($deal['manage_fee_rate']);
        $notice['manage_fee_text'] = $deal['manage_fee_text'];

        $notice['use_info'] = $use_info;
        $notice['house_address'] = $deal['house_address'];
        $notice['house_sn'] = $deal['house_sn'];
        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);

        $earning = new EarningService();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);

        $notice['consult_fee_rate'] = format_rate_for_cont($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));

        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        $notice['loan_user_name'] = $user_info['user_name'];
        $notice['manage_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['manage_fee_rate'], $deal['repay_time']));
        $notice['manage_fee_rate_part_prepayment'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['prepay_manage_fee_rate'], $deal['repay_time']));

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

        $notice['prepay_penalty_days'] = $deal['prepay_penalty_days'];//提前还款罚息天数

        //企业用户相关
        $notice['loan_name_info'] = $loan_name_info;
        $notice['loan_username_info'] = $loan_username_info;
        $notice['loan_credentials_info'] = $loan_credentials_info;
        $notice['loan_bank_user_info'] = $loan_bank_user_info;
        $notice['loan_bank_no_info'] = $loan_bank_no_info;
        $notice['loan_bank_name_info'] = $loan_bank_name_info;

        $notice['loan_name_info_transfer'] = $loan_name_info_transfer;
        $notice['loan_username_info_transfer'] = $loan_username_info_transfer;
        $notice['loan_credentials_info_transfer'] = $loan_credentials_info_transfer;
        $notice['loan_bank_user_info_transfer'] = $loan_bank_user_info_transfer;
        $notice['loan_bank_no_info_transfer'] = $loan_bank_no_info_transfer;
        $notice['loan_bank_name_info_transfer'] = $loan_bank_name_info_transfer;

        //fature 4477
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['min_loan_money_uppercase'] = get_amount($deal['min_loan_money']);
        $notice['project_borrow_amount'] = intval($deal['project_info']['borrow_amount']);
        $notice['project_borrow_amount_uppercase'] = get_amount($deal['project_info']['borrow_amount']);

        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    /*
     * 交易所合同
     */
    public function getExchangeContractPre($dealId,$userId,$money,$type){
        switch($type){
            case 1:
                $contractType = self::SUBSCRIBE_CONT; //交易所-认购协议
                break;
            case 2:
                $contractType = self::PERCEPTION_CONT; //交易所--风险认知书
                break;
            case 3:
                $contractType = self::RAISE_CONT; //交易所-募集说明书
                break;
            case 4:
                $contractType = self::QUALIFIED_CONT; //交易所-合格投资者标准
                break;
            default:
                return false;
        }

        $dealId = intval($dealId);
        $userId = intval($userId);
        $money = floatval($money);

        if($dealId <= 0 || $userId <= 0){
            return false;
        }

        $deal_service = new DealService();
        $deal = $this->get_deal_info($dealId);
        $userInfo = $this->get_user_data($userId);

        if(empty($userInfo) || empty($deal)){
            return false;
        }

        if($deal['contract_tpl_type'] != 'DF'){
            if(is_numeric($deal['contract_tpl_type'])) {
                $request = new \NCFGroup\Protos\Contract\RequestGetTplsByDealId();
                $request->setDealId($dealId);
                $request->setType(0);
                $request->setSourceType($deal['deal_type']);
                $response = $GLOBALS['contractRpc']->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Tpl",
                    'method' => "getTplsByDealId",
                    'args' => $request,
                ));

                if($response->errorCode == 0){
                    $tpls = $response->list['data'];

                    foreach($tpls as $tplOne){
                        if(strstr($tplOne['name'],$contractType)){
                            $tplContent = $tplOne['content'];
                        }
                    }
                }else{
                    $tplContent = '';
                }
            }
        }

        $contractRenderService = new \core\service\contract\ContractRenderService();
        $notice = $contractRenderService->getNoticeInfo($dealId,$userId,$money);
        $borrowUserInfo = $deal_service->getDealUserCompanyInfo($deal);
        $notice = array_merge($notice, $borrowUserInfo);

        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tplContent);
    }

    private function get_user_data($user_id){
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
            $deal_info[$deal_id]['project_info'] = $deal_project_model->find($deal_info[$deal_id]['project_id']);
        }
        return $deal_info[$deal_id];
    }

    /**
     * 资产收益权回购通知 预签
     * @param int $deal_id
     * @param int $user_id
     * @param floot $money
     * @return boolean
     */
    public function getAssetsContractPre($deal_id, $user_id, $money){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $money = floatval($money);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }

        $deal_service = new DealService();
        $deal = $this->get_deal_info($deal_id);
        $user_info = $this->get_user_data($user_id);

        if(empty($user_info) || empty($deal)){
            return false;
        }

        $tpl_name = self::BUYBACK_CONT;
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        $tpl = MsgTemplateModel::instance()->getTemplateByName($tpl_name);
        if(empty($tpl)){
            return false;
        }
        $tpl_content = $tpl['content'];

        $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);

        $notice = array();
        $notice['number'] = '[]';
        $notice['loan_contract_num'] = '[]';
        $notice['company_name'] = $borrow_user_info['company_name'];
        $notice['loan_real_name'] = $user_info['real_name'];
        $notice['loan_user_idno'] = $user_info['idno'];
        $notice['buyback_time'] = ' 年 月 日';

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $money);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $money;

        $notice['loan_money_repay'] = $loan_money_repay;
        $notice['loan_money_repay_uppercase'] = get_amount($loan_money_repay);
        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");

        $notice['prepay_penalty_days'] = $deal['prepay_penalty_days'];//提前还款罚息天数

        //fature 4477
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['min_loan_money_uppercase'] = get_amount($deal['min_loan_money']);
        $notice['project_borrow_amount'] = intval($deal['project_info']['borrow_amount']);
        $notice['project_borrow_amount_uppercase'] = get_amount($deal['project_info']['borrow_amount']);

        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }
    /**
     * 黄金预签合同 system\libs updateContractNew push_gold_loan_transfer
     */

    public function getGdbContractPre($deal_id, $user_id, $buyAmount=0,$buyPrice=0, $num=null, $create_time=null){
        $deal_id = intval($deal_id);
        $user_id = intval($user_id);
        $buyAmount = floatval($buyAmount);
        $buyPrice = floatval($buyPrice);

        if($deal_id <= 0 || $user_id <= 0){
            return false;
        }
        $user_info = $this->get_user_data($user_id);
        $user_service = new UserService();
        $loan_bank_info = get_user_bank($user_id);
        $rpc = new Rpc('goldRpc');
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$deal_id));
        $response = $rpc->go("\NCFGroup\Gold\Services\Deal","getDealById",$request);
        $deal = $response['data'];
        if($response && ($response->errorCode != 0)) {
            throw new \Exception('RPC gold is fail!');
        }

        $contractRenderService = new ContractRenderService();
        $borrowInfo = $contractRenderService->getBorrowInfo($deal['user_id']);  // 乙方 - 借款方
        $notice['borrow_name'] = $borrowInfo['borrow_name'];
        $notice['borrow_user_number'] = $borrowInfo['borrow_user_number'];
        $notice['borrow_license'] = $borrowInfo['borrow_license'];
        $notice['borrow_real_name'] = $borrowInfo['borrow_agency_realname'];
        $notice['borrow_agency_idno'] = $borrowInfo['borrow_agency_idno'];

        //暂时使用主站平台名称，后续使用独立icp
        $notice['platform_show_name'] = '网信理财';
        $notice['platform_domain'] = 'www.firstp2p.com';


        $notice['rate'] = format_rate_for_cont($projectInfo['rateDay']);
        $notice['fee_rate'] = format_rate_for_cont($projectInfo['feeRate']);
        $notice['fee_days'] = $projectInfo['feeDays'];
        //咨询顾问费率
        $notice['consult_fee_rate'] = format_rate_for_cont(bcsub(CommonEnum::P2P_RATE_YEAR ,$projectInfo['rateYear'],5));

        //DT-299 企业户显示

        $contractRenderService = new ContractRenderService();
        $loan_info = $contractRenderService->getLoanInfo($user_info,$loan_bank_info);
        $notice['loan_name_info'] = $loan_info['loan_name_info'];
        $notice['loan_user_name'] = $loan_info['loan_username_info'];
        $notice['loan_user_idno'] = $loan_info['loan_credentials_info'];
        $notice['loan_user_number'] = $loan_info['loan_user_number'];
        $notice['loan_bank_user'] = $loan_info['loan_bank_user_info'];
        $notice['loan_bank_card'] = $loan_info['loan_bank_no_info'];
        $notice['loan_bank_name'] = $loan_info['loan_bank_name_info'];
        $notice['loan_major_name'] = $loan_info['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loan_info['loan_major_condentials_no'];
        $notice['loan_credentials_info'] = $loan_info['loan_credentials_info'];


        if(isset($create_time) && ($create_time != null)){
            $notice['sign_time'] = date('Y年m月d日',$create_time);
        }else{
            $notice['sign_time'] = date('Y年m月d日',time());
        }

        $notice['number'] = $num;

        if($deal['loantype'] == 5){
            $days = $deal['repay_time'];
            $repay_time = $deal['repay_time'].'天';
        }else{
            $days = $deal['repay_time']*30;
            $repay_time = $deal['repay_time'].'个月';
        }

        $goldBuyInfo = array(
                'buy_amount' => number_format($buyAmount,3),
                'loan_money' => number_format($buyPrice,2),
                'buy_price' => number_format($buyPrice,2),
                'buyer_fee' => number_format($deal['buyer_fee'],2),
                'money' => number_format(floorfix($buyAmount*$buyPrice,2),2),
                'money_uppercase' => get_amount( $buyAmount*$buyPrice),
                'fee' => number_format(floorfix($deal['buyer_fee']*$buyAmount,2),2),
                'repay_time' => $repay_time,
                'fee_uppercase' => get_amount($deal['buyer_fee']*$buyAmount),
                'interest' => number_format(floorfix($deal['rate']*$days*$buyAmount/36000,3,6),3),
                'loan_fee_rate' => $deal['loan_fee_rate'],
                'tech_fee_rate' => $deal['tech_fee_rate']
        );
        $notice = array_merge($notice,$goldBuyInfo);

        $request = new RequestGetTplsByDealId();
        $request->setDealId($deal_id);
        $request->setType(2);
        $request->setSourceType(100);

        $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByDealId",
                'args' => $request,
        ));
        if (0 == $response->getErrorCode()) {
            foreach($response->list['data'] as $one_tpl) {
                if ($one_tpl['tpl_identifier_info']['isSeenWhenBid'] != true) { // 如果取投资时可见的标，则跳过不是这个标识的模板
                    continue;
                }else{
                    $gold_cont = $one_tpl;
                    break;
                }
            }
        }
        if(empty($gold_cont)){
            throw new \Exception('获取黄金合同失败');
        }
        $tpl_content = $gold_cont = $one_tpl['content'];
        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }


    public function getAssetsContTpl($deal_id){
        $deal = $this->get_deal_info($deal_id);
        if(empty($deal)){
            return false;
        }

        $tpl_name = self::BUYBACK_CONT;
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        $tpl = MsgTemplateModel::instance()->getTemplateByName($tpl_name);
        if(empty($tpl)){
            return false;
        }
        return true;
    }

    public function getDealContPreTemplate($deal_id,$deal_type = '')
    {
        $deal = $this->get_deal_info($deal_id);
        $cont_new_service = new ContractNewService();
        if( $deal_type == 'gold'){
            // 获取合同模板 list
            $request = new RequestGetTplsByDealId();
            $request->setDealId($deal_id);
            $request->setType(2);
            $request->setSourceType(100);

            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByDealId",
                'args' => $request,
            ));
            if (0 == $response->getErrorCode()) {
                foreach($response->list['data'] as $one_tpl) {
                    if ($one_tpl['tpl_identifier_info']['isSeenWhenBid'] != true) { // 如果取投资时可见的标，则跳过不是这个标识的模板
                        continue;
                    }else{
                        $gold_cont = $one_tpl;
                        break;
                    }
                }
            }
        }elseif($deal_type == 'duotou'){
            $dtb_cont['contract_title'] = "智多新投资协议";
            $cont_pre = array(
                'dtb_cont' => $dtb_cont,
                'buyback_cont' => 0,
            );
            return $cont_pre;
        } elseif ($cont_new_service->isAttachmentContract($deal['contract_tpl_type'])) {
            $cont_list = ContractModel::instance()->getContractAttachmentByDealLoad($deal);
            return array('cont_list' => $cont_list, 'is_attachment' => true);
        }

        if(empty($deal) && empty($deal_type)){
            return false;
        }

        $deal_service = new DealService();
        $deal_info = $deal_service->getDeal($deal_id);
        $is_lease = $deal_service->isDealLeaseByType($deal['type_id']);
        $tpl_tag = $deal['contract_tpl_type'] == 'DF' ? '' : '_'.$deal['contract_tpl_type'];

        $template_model = MsgTemplateModel::instance();
        $field = 'id,contract_title';
        if(is_numeric($deal['contract_tpl_type'])) {
            $prefix = self::LOAN_CONT.'_V2';
            $entrustPrefix = self::ENTRUST_CONT.'_V2';
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            $loan_cont = $response->data[0];
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($entrustPrefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));
            $entrust_cont = $response->data[0];

        }elseif(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
            $loan_cont = $template_model->getTemplateByName(self::LOAN_CONT.'_V2'.$tpl_tag, $field);
        }else{
            $loan_cont = $template_model->getTemplateByName(self::LOAN_CONT.$tpl_tag, $field);
        }

        if(empty($loan_cont['contract_title'])){
            $loan_cont['contract_title'] = $loan_cont['contractTitle'] ? $loan_cont['contractTitle'] : '借款合同';
        }
        if(empty($entrust_cont['contract_title'])){
            $entrust_cont['contract_title'] = $entrust_cont['contractTitle'] ? $entrust_cont['contractTitle'] : '委托专享合同';
        }

        if(is_numeric($deal['contract_tpl_type'])) {
            $tpl_prefix = self::WARRANT_CONT;
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($tpl_prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            if(!is_array($response->data)){
                $request->setSourceType($deal_info['deal_type']);
                $request->setTpl_prefix($tpl_prefix);
                $response = $GLOBALS['contractRpc']->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Tpl",
                    'method' => "getTplByName",
                    'args' => $request,
                ));
            }
            $warrant_cont = $response->data[0];
        }else{
            $warrant_cont = $template_model->getTemplateByName(self::WARRANT_CONT.$tpl_tag, $field);
        }

        if(empty($warrant_cont['contract_title'])){
            if(isset($warrant_cont['contractTitle'])){
                $warrant_cont['contract_title'] = $warrant_cont['contractTitle'];
            }else{
                $warrant_cont['contract_title'] = '保证合同';
            }

        }

        if(is_numeric($deal['contract_tpl_type'])) {
            $tpl_prefix = self::LENDER_CONT;
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($tpl_prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            if(!is_array($response->data)){
                $request->setSourceType($deal_info['deal_type']);
                $request->setTpl_prefix($tpl_prefix);
                $response = $GLOBALS['contractRpc']->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Tpl",
                    'method' => "getTplByName",
                    'args' => $request,
                ));
            }
            $lender_cont = $response->data[0];
        }else{
            $lender_cont = $template_model->getTemplateByName(LENDER_CONT.$tpl_tag, $field);
        }

        if(empty($lender_cont['contract_title'])){
            if(isset($lender_cont['contractTitle'])){
                $lender_cont['contract_title'] = $lender_cont['contractTitle'];
            }else{
                $lender_cont['contract_title'] = $is_lease ? '资产受让方咨询服务协议' : '出借人咨询服务协议';
            }

        }



        $buyback_cont = $template_model->getTemplateByName(self::BUYBACK_CONT.$tpl_tag, $field);

        if(isset($buyback_cont['id']) && empty($buyback_cont['contract_title'])){
            $buyback_cont['contract_title'] = '资产收益权回购通知';
        }

        $cont_pre = array(
            'loan_cont' => $loan_cont,
            'warrant_cont' => $warrant_cont,
            'lender_cont' => $lender_cont,
            'buyback_cont' => $buyback_cont,
            'entrust_cont' => $entrust_cont,
            'gold_cont' => $gold_cont,
        );

        //交易所
        if(DealModel::DEAL_TYPE_EXCHANGE == 2){
            $tpl_prefix = self::SUBSCRIBE_CONT;
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($tpl_prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            $subscribe_cont = $response->data[0];
            if(empty($subscribe_cont['contract_title'])){
                if(isset($subscribe_cont['contractTitle'])){
                    $subscribe_cont['contract_title'] = $subscribe_cont['contractTitle'];
                }else{
                    $subscribe_cont['contract_title'] = '认购协议';
                }

            }

            $tpl_prefix = self::PERCEPTION_CONT;
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($tpl_prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            $perception_cont = $response->data[0];
            if(empty($perception_cont['contract_title'])){
                if(isset($perception_cont['contractTitle'])){
                    $perception_cont['contract_title'] = $perception_cont['contractTitle'];
                }else{
                    $perception_cont['contract_title'] = '风险认知书';
                }

            }

            $tpl_prefix = self::RAISE_CONT;
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($tpl_prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            $raise_cont = $response->data[0];
            if(empty($raise_cont['contract_title'])){
                if(isset($raise_cont['contractTitle'])){
                    $raise_cont['contract_title'] = $raise_cont['contractTitle'];
                }else{
                    $raise_cont['contract_title'] = '募集说明书';
                }
            }

            $tpl_prefix = self::QUALIFIED_CONT;
            $request = new \NCFGroup\Protos\Contract\RequestGetTplByName();
            $request->setDeal_id(intval($deal_id));
            $request->setType(0);
            $request->setSourceType($deal_info['deal_type']);
            $request->setTpl_prefix($tpl_prefix);
            $response = $GLOBALS['contractRpc']->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplByName",
                'args' => $request,
            ));

            $qualified_cont = $response->data[0];
            if(empty($qualified_cont['contract_title'])){
                if(isset($qualified_cont['contractTitle'])){
                    $qualified_cont['contract_title'] = $qualified_cont['contractTitle'];
                }else{
                    $qualified_cont['contract_title'] = '合格投资者标准';
                }
            }

            $cont_pre['subscribe_cont'] = $subscribe_cont;
            $cont_pre['perception_cont'] = $perception_cont;
            $cont_pre['raise_cont'] = $raise_cont;
            $cont_pre['qualified_cont'] = $qualified_cont;

        }

        return $cont_pre;
    }
}
