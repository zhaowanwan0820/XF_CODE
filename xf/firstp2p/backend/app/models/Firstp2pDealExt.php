<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealExt extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var string
     */
    public $use_info;


    /**
     *
     * @var string
     */
    public $house_address;


    /**
     *
     * @var string
     */
    public $house_sn;


    /**
     *
     * @var integer
     */
    public $publish_time;


    /**
     *
     * @var string
     */
    public $leasing_contract_num;


    /**
     *
     * @var string
     */
    public $lessee_real_name;


    /**
     *
     * @var float
     */
    public $leasing_money;


    /**
     *
     * @var float
     */
    public $income_base_rate;


    /**
     *
     * @var float
     */
    public $income_float_rate;


    /**
     *
     * @var float
     */
    public $income_subsidy_rate;


    /**
     *
     * @var integer
     */
    public $start_loan_time;


    /**
     *
     * @var float
     */
    public $prepay_manage_fee_rate;


    /**
     *
     * @var integer
     */
    public $overdue_break_days;


    /**
     *
     * @var integer
     */
    public $first_repay_interest_day;


    /**
     *
     * @var string
     */
    public $entrusted_loan_entrusted_contract_num;


    /**
     *
     * @var string
     */
    public $entrusted_loan_borrow_contract_num;


    /**
     *
     * @var integer
     */
    public $base_contract_repay_time;


    /**
     *
     * @var string
     */
    public $loan_fee_ext;


    /**
     *
     * @var string
     */
    public $consult_fee_ext;


    /**
     *
     * @var string
     */
    public $guarantee_fee_ext;


    /**
     *
     * @var string
     */
    public $pay_fee_ext;


    /**
     *
     * @var string
     */
    public $management_fee_ext;


    /**
     *
     * @var integer
     */
    public $must_coupon;


    /**
     *
     * @var integer
     */
    public $need_repay_notice;


    /**
     *
     * @var integer
     */
    public $coupon_pay_type;


    /**
     *
     * @var integer
     */
    public $is_auto_withdrawal;


    /**
     *
     * @var integer
     */
    public $is_bid_new;


    /**
     *
     * @var integer
     */
    public $contract_transfer_type;


    /**
     *
     * @var integer
     */
    public $loan_fee_rate_type;


    /**
     *
     * @var integer
     */
    public $consult_fee_rate_type;


    /**
     *
     * @var integer
     */
    public $pay_fee_rate_type;


    /**
     *
     * @var integer
     */
    public $management_fee_rate_type;


    /**
     *
     * @var string
     */
    public $leasing_contract_title;


    /**
     *
     * @var integer
     */
    public $loan_application_type;


    /**
     *
     * @var integer
     */
    public $guarantee_fee_rate_type;


    /**
     *
     * @var float
     */
    public $max_rate;


    /**
     *
     * @var integer
     */
    public $line_site_id;


    /**
     *
     * @var string
     */
    public $line_site_name;


    /**
     *
     * @var integer
     */
    public $loan_type;


    /**
     *
     * @var string
     */
    public $deal_name_prefix;


    /**
     *
     * @var integer
     */
    public $deal_specify_uid;

    /**
     *
     * @var float
     */
    public $discount_rate;


    /**
     *
     * @var string
     */
    public $canal_fee_ext;

    /**
     *
     * @var integer
     */
    public $canal_fee_rate_type;


    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->houseAddress = '';
        $this->houseSn = '';
        $this->publishTime = '0';
        $this->leasingContractNum = '';
        $this->lesseeRealName = '';
        $this->incomeBaseRate = '0.00000';
        $this->incomeFloatRate = '0.00000';
        $this->incomeSubsidyRate = '0.00000';
        $this->startLoanTime = '0';
        $this->prepayManageFeeRate = '0.00000';
        $this->overdueBreakDays = '0';
        $this->firstRepayInterestDay = '0';
        $this->entrustedLoanEntrustedContractNum = '';
        $this->entrustedLoanBorrowContractNum = '';
        $this->baseContractRepayTime = '0';
        $this->mustCoupon = '0';
        $this->needRepayNotice = '1';
        $this->couponPayType = '0';
        $this->isAutoWithdrawal = '1';
        $this->isBidNew = '0';
        $this->contractTransferType = '0';
        $this->loanFeeRateType = '0';
        $this->consultFeeRateType = '0';
        $this->payFeeRateType = '1';
        $this->managementFeeRateType = '0';
        $this->loanApplicationType = '0';
        $this->guaranteeFeeRateType = '0';
        $this->maxRate = '0.00000';
        $this->dealSpecifyUid = '0';
        $this->discountRate = '100.00000';
        $this->canalFeeExt = '';
        $this->canalFeeRateType = '0';
        //END DEFAULT_VALUE
    }

    public function initialize()
    {
        parent::initialize();
        $this->setReadConnectionService('firstp2p_r');
        $this->setWriteConnectionService('firstp2p');
    }

    public function columnMap()
    {
        return array(
            'deal_id' => 'dealId',
            'use_info' => 'useInfo',
            'house_address' => 'houseAddress',
            'house_sn' => 'houseSn',
            'publish_time' => 'publishTime',
            'leasing_contract_num' => 'leasingContractNum',
            'lessee_real_name' => 'lesseeRealName',
            'leasing_money' => 'leasingMoney',
            'income_base_rate' => 'incomeBaseRate',
            'income_float_rate' => 'incomeFloatRate',
            'income_subsidy_rate' => 'incomeSubsidyRate',
            'start_loan_time' => 'startLoanTime',
            'prepay_manage_fee_rate' => 'prepayManageFeeRate',
            'overdue_break_days' => 'overdueBreakDays',
            'first_repay_interest_day' => 'firstRepayInterestDay',
            'entrusted_loan_entrusted_contract_num' => 'entrustedLoanEntrustedContractNum',
            'entrusted_loan_borrow_contract_num' => 'entrustedLoanBorrowContractNum',
            'base_contract_repay_time' => 'baseContractRepayTime',
            'loan_fee_ext' => 'loanFeeExt',
            'consult_fee_ext' => 'consultFeeExt',
            'guarantee_fee_ext' => 'guaranteeFeeExt',
            'pay_fee_ext' => 'payFeeExt',
            'management_fee_ext' => 'managementFeeExt',
            'must_coupon' => 'mustCoupon',
            'need_repay_notice' => 'needRepayNotice',
            'coupon_pay_type' => 'couponPayType',
            'is_auto_withdrawal' => 'isAutoWithdrawal',
            'is_bid_new' => 'isBidNew',
            'contract_transfer_type' => 'contractTransferType',
            'loan_fee_rate_type' => 'loanFeeRateType',
            'consult_fee_rate_type' => 'consultFeeRateType',
            'pay_fee_rate_type' => 'payFeeRateType',
            'management_fee_rate_type' => 'managementFeeRateType',
            'leasing_contract_title' => 'leasingContractTitle',
            'loan_application_type' => 'loanApplicationType',
            'guarantee_fee_rate_type' => 'guaranteeFeeRateType',
            'max_rate' => 'maxRate',
            'line_site_id' => 'lineSiteId',
            'line_site_name' => 'lineSiteName',
            'loan_type' => 'loanType',
            'deal_name_prefix' => 'dealNamePrefix',
            'deal_specify_uid' => 'dealSpecifyUid',
            'discount_rate' => 'discountRate',
            'canal_fee_ext' => 'canalFeeExt',
            'canal_fee_rate_type' => 'canalFeeRateType',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_ext";
    }
}
