<?php
/**
 * ContractPre class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace app\models\service;

use app\models\dao\MsgTemplate;
use app\models\service\Earning;

/**
 * 合同预签 <正式下发和预签 合并之后，该文件会废弃>
 *
 * @author wenyanlei@ucfgroup.com
 **/
class ContractPre
{

    /**
     * 借款数据
     */
    private $_deal = array();
    private $_bidmoney = 0;

    public function __construct($deal_id, $principal) {
        if($deal_id){
            $this->_deal = get_deal($deal_id);
        }
        $this->_bidmoney = $principal;
    }

    /**
     * 借款预签合同
     */
    public function getLoanContractPre(){

        if(empty($this->_deal)){
            return false;
        }

        $deal = $this->_deal;

        $tpl_name = 'TPL_LOAN_CONTRACT';
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        $tpl = MsgTemplate::instance()->findBy("name='".$tpl_name."'");;
        $tpl_content = $tpl['content'];

        $borrow_user_info = get_deal_borrow_info($deal);
        $agency_info = get_agency_info($deal['agency_id']);

        $earning = new Earning();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $this->_bidmoney);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $this->_bidmoney;

        //$notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);
        $notice['number'] = '[]';

        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];
        $notice['loan_user_idno'] = $GLOBALS['user_info']['idno'];

        $notice['loan_money'] = $this->_bidmoney;
        $notice['loan_money_uppercase'] = get_amount($this->_bidmoney);
        $notice['loan_money_repay'] = $loan_money_repay;
        $notice['loan_money_repay_uppercase'] = get_amount($loan_money_repay);
        $notice['loan_money_earning'] = $loan_money_earning_format;
        $notice['loan_money_earning_uppercase'] = get_amount($loan_money_earning_format);

        $notice['repay_time'] = $deal['repay_time'];
        $notice['repay_time_unit'] = $deal['loantype'] == 5 ? $deal['repay_time'].'天' : $deal['repay_time'].'个月';

        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        $notice['start_time'] = '[]';
        $notice['end_time'] = '[]';
        $notice['borrow_sign_time'] = '乙方签署之日';

        $notice['rate'] = format_rate_for_show($deal['int_rate']);
        $notice['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];

        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_mobile'] = $agency_info['mobile'];
        $notice['agency_postcode'] = $agency_info['postcode'];
        $notice['agency_fax'] = $agency_info['fax'];

        //获取用户的银行卡信息
        $loan_bank_info = get_user_bank($GLOBALS['user_info']['id']);
        $borrow_bank_info = get_user_bank($deal['user_id']);

        //新加的变量处理  add by wenyanlei 2013-07-15
        $notice['loan_user_name'] = $GLOBALS['user_info']['user_name'];
        $notice['loan_user_number'] = numTo32($GLOBALS['user_info']['user_id']);
        $notice['loan_bank_user'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
        $notice['loan_bank_card'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
        $notice['loan_bank_name'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

        $notice['borrow_bank_user'] = $borrow_bank_info['card_name'];
        $notice['borrow_bank_card'] = $borrow_bank_info['bankcard'];
        $notice['borrow_bank_name'] = $borrow_bank_info['bankname'];

        $notice['use_info'] = $deal['use_info'];
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

        $notice['consult_fee_rate'] = format_rate_for_show($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_show(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));


        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");
        $notice['borrow_user_number'] = numTo32($deal['user_id']);

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $GLOBALS['tmpl']->assign("notice",$notice);

        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    /**
     * 保证预签合同
     */
    public function getGuaranteeContractPre(){

        if(empty($this->_deal)){
            return false;
        }
        $deal = $this->_deal;

        $tpl_name = 'TPL_WARRANT_CONTRACT';
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        $tpl = MsgTemplate::instance()->findBy("name='".$tpl_name."'");

        $tpl_content = $tpl['content'];

        $agency_info = get_agency_info($deal['agency_id']);
        $borrow_user_info = get_deal_borrow_info($deal);

        //$notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);
        $notice['number'] = '[]';
        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_mobile'] = $agency_info['mobile'];
        $notice['agency_postcode'] = $agency_info['postcode'];
        $notice['agency_fax'] = $agency_info['fax'];
        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];

        $loan_user_info = get_user_info($GLOBALS['user_info']['id'],true);
        $notice['loan_user_idno'] = !empty($loan_user_info['idno']) ? $loan_user_info['idno']:'';
        $notice['loan_user_address'] = !empty($loan_user_info['address']) ? $loan_user_info['address']:'';
        $notice['loan_user_mobile'] = $loan_user_info['mobile'];
        $notice['loan_user_postcode'] = !empty($loan_user_info['postcode']) ? $loan_user_info['postcode']:'';
        $notice['loan_user_email'] = $loan_user_info['email'];

        $notice['loan_money'] = $this->_bidmoney;
        $notice['loan_money_up'] = get_amount($this->_bidmoney);
        $notice['uppercase_borrow_money'] = get_amount($deal['borrow_amount']);

        //$notice['start_time'] = to_date(get_gmtime(),"Y年m月d日");
        //$notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        //$endtime = get_gmtime() + $deal['repay_time']*60*24;
        //$notice['end_time'] = to_date(strtotime($deal['repay_time']." month"),"Y年m月d日");
        $notice['sign_time'] = '合同签署之日';
        $notice['start_time'] = '[]';
        $notice['end_time'] = '[]';

        $notice['loan_contract_num'] = '[]';

        $notice['use_info'] = $deal['use_info'];
        $notice['house_address'] = $deal['house_address'];
        $notice['house_sn'] = $deal['house_sn'];

        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);

        $earning = new Earning();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);

        $loan_money_earning = $earning->getEarningMoney($deal['id'], $this->_bidmoney);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $this->_bidmoney;

        $notice['loan_money_repay'] = $loan_money_repay;
        $notice['loan_money_repay_uppercase'] = get_amount($loan_money_repay);
        $notice['loan_money_earning'] = $loan_money_earning_format;
        $notice['loan_money_earning_uppercase'] = get_amount($loan_money_earning_format);

        $notice['consult_fee_rate'] = format_rate_for_show($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_show(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");
        $notice['borrow_user_number'] = numTo32($deal['user_id']);


        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    /**
     * 出借人平台服务协议预签
     */
    public function getLenderContractPre(){

        if(empty($this->_deal)){
            return false;
        }

        $deal = $this->_deal;

        $tpl_name = 'TPL_LENDER_PROTOCAL';
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        $tpl = MsgTemplate::instance()->findBy("name='".$tpl_name."'");;
        $tpl_content = $tpl['content'];

        if(empty($GLOBALS['user_info'])) return false;

        $notice = array();
        $notice['loan_user_idno'] = $GLOBALS['user_info']['idno'];
        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];
        $notice['loan_address'] = $GLOBALS['user_info']['address'];
        $notice['loan_phone'] = $GLOBALS['user_info']['mobile'];
        $notice['loan_email'] = $GLOBALS['user_info']['email'];
        $notice['manage_fee_rate'] = format_rate_for_show($deal['manage_fee_rate']);
        $notice['manage_fee_text'] = $deal['manage_fee_text'];

        $notice['use_info'] = $deal['use_info'];
        $notice['house_address'] = $deal['house_address'];
        $notice['house_sn'] = $deal['house_sn'];
        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);

        $earning = new Earning();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);

        $notice['consult_fee_rate'] = format_rate_for_show($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_show(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");
        $notice['borrow_user_number'] = numTo32($deal['user_id']);


        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

    /**
     * 保证反担保预签
     */
    public function getWarrandiceContractPre(){

        if(empty($this->_deal)){
            return false;
        }

        $deal = $this->_deal;

        $tpl_name = 'TPL_WARRANDICE_CONTRACT';
        if($deal['contract_tpl_type'] != 'DF'){
            $tpl_name .= '_'.$deal['contract_tpl_type'];
        }

        $tpl = MsgTemplate::instance()->findBy("name='".$tpl_name."'");;
        $tpl_content = $tpl['content'];

        $agency_info = get_agency_info($deal['agency_id']);
        $borrow_user_info = get_deal_borrow_info($deal);
        $guarantor_info = get_user_info($GLOBALS['user_info']['id'], true);

        $notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);

        $notice['guarantor_name'] = $guarantor_info['user_name'];
        $notice['guarantor_address'] = !empty($guarantor_info['address']) ? $guarantor_info['address']:'';
        $notice['guarantor_mobile'] = $guarantor_info['mobile'];

        $notice['guarantor_email'] = $guarantor_info['email'];
        $notice['guarantor_idno'] = !empty($guarantor_info['idno']) ? $guarantor_info['idno'] : '';

        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_mobile'] = $agency_info['mobile'];

        //保证人确认页面还没有出借人信息，所以置为空
        $notice['loan_real_name'] = '';
        $notice['loan_user_idno'] = '';
        $notice['loan_contract_num'] = '';
        $notice['borrow_user_number'] = numTo32($deal['user_id']);

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");

        $GLOBALS['tmpl']->assign("notice",$notice);
        return $GLOBALS['tmpl']->fetch("str:".$tpl_content);
    }

}