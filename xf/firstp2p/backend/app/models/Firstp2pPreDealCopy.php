<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPreDealCopy extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $sub_name;


    /**
     *
     * @var integer
     */
    public $cate_id;


    /**
     *
     * @var integer
     */
    public $agency_id;


    /**
     *
     * @var integer
     */
    public $advisory_id;


    /**
     *
     * @var string
     */
    public $auser;


    /**
     *
     * @var string
     */
    public $use_info;


    /**
     *
     * @var string
     */
    public $checker;


    /**
     *
     * @var string
     */
    public $manager;


    /**
     *
     * @var string
     */
    public $manager_mobile;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $type_id;


    /**
     *
     * @var float
     */
    public $borrow_amount;


    /**
     *
     * @var float
     */
    public $min_loan_money;


    /**
     *
     * @var float
     */
    public $max_loan_money;


    /**
     *
     * @var integer
     */
    public $repay_time;


    /**
     *
     * @var float
     */
    public $rate;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $enddate;


    /**
     *
     * @var string
     */
    public $services_fee;


    /**
     *
     * @var integer
     */
    public $loantype;


    /**
     *
     * @var integer
     */
    public $warrant;


    /**
     *
     * @var float
     */
    public $period_rate;


    /**
     *
     * @var float
     */
    public $loan_fee_rate;


    /**
     *
     * @var float
     */
    public $consult_fee_rate;


    /**
     *
     * @var float
     */
    public $guarantee_fee_rate;


    /**
     *
     * @var float
     */
    public $manage_fee_rate;


    /**
     *
     * @var float
     */
    public $income_fee_rate;


    /**
     *
     * @var float
     */
    public $advisor_fee_rate;


    /**
     *
     * @var string
     */
    public $contract_tpl_type;


    /**
     *
     * @var float
     */
    public $prepay_rate;


    /**
     *
     * @var float
     */
    public $compensation_days;


    /**
     *
     * @var float
     */
    public $loan_compensation_days;


    /**
     *
     * @var integer
     */
    public $prepay_penalty_days;


    /**
     *
     * @var integer
     */
    public $prepay_days_limit;


    /**
     *
     * @var string
     */
    public $house;


    /**
     *
     * @var string
     */
    public $house_id;


    /**
     *
     * @var integer
     */
    public $pic;


    /**
     *
     * @var string
     */
    public $note;


    /**
     *
     * @var integer
     */
    public $deal_crowd;


    /**
     *
     * @var string
     */
    public $deal_tag_name;


    /**
     *
     * @var string
     */
    public $deal_tag_desc;


    /**
     *
     * @var string
     */
    public $coupon;


    /**
     *
     * @var integer
     */
    public $coupon_type;


    /**
     *
     * @var integer
     */
    public $min_loan_total_count;


    /**
     *
     * @var float
     */
    public $min_loan_total_amount;


    /**
     *
     * @var integer
     */
    public $min_loan_total_limit_relation;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->advisoryId = '0';
        $this->checker = '';
        $this->minLoanMoney = '10000';
        $this->maxLoanMoney = '0';
        $this->status = '0';
        $this->periodRate = '0.00000';
        $this->loanFeeRate = '0.00000';
        $this->consultFeeRate = '0.00000';
        $this->guaranteeFeeRate = '0.00000';
        $this->manageFeeRate = '0.00000';
        $this->incomeFeeRate = '0.00000';
        $this->advisorFeeRate = '0.00000';
        $this->contractTplType = 'DF';
        $this->prepayRate = '0.00000';
        $this->compensationDays = '0.00';
        $this->loanCompensationDays = '0.00';
        $this->prepayPenaltyDays = '0';
        $this->prepayDaysLimit = '0';
        $this->pic = '0';
        $this->note = '';
        $this->dealCrowd = '0';
        $this->dealTagName = '';
        $this->dealTagDesc = '';
        $this->coupon = '';
        $this->couponType = '0';
        $this->minLoanTotalCount = '0';
        $this->minLoanTotalAmount = '0.00';
        $this->minLoanTotalLimitRelation = '0';
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
            'id' => 'id',
            'name' => 'name',
            'sub_name' => 'subName',
            'cate_id' => 'cateId',
            'agency_id' => 'agencyId',
            'advisory_id' => 'advisoryId',
            'auser' => 'auser',
            'use_info' => 'useInfo',
            'checker' => 'checker',
            'manager' => 'manager',
            'manager_mobile' => 'managerMobile',
            'description' => 'description',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'type_id' => 'typeId',
            'borrow_amount' => 'borrowAmount',
            'min_loan_money' => 'minLoanMoney',
            'max_loan_money' => 'maxLoanMoney',
            'repay_time' => 'repayTime',
            'rate' => 'rate',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'status' => 'status',
            'enddate' => 'enddate',
            'services_fee' => 'servicesFee',
            'loantype' => 'loantype',
            'warrant' => 'warrant',
            'period_rate' => 'periodRate',
            'loan_fee_rate' => 'loanFeeRate',
            'consult_fee_rate' => 'consultFeeRate',
            'guarantee_fee_rate' => 'guaranteeFeeRate',
            'manage_fee_rate' => 'manageFeeRate',
            'income_fee_rate' => 'incomeFeeRate',
            'advisor_fee_rate' => 'advisorFeeRate',
            'contract_tpl_type' => 'contractTplType',
            'prepay_rate' => 'prepayRate',
            'compensation_days' => 'compensationDays',
            'loan_compensation_days' => 'loanCompensationDays',
            'prepay_penalty_days' => 'prepayPenaltyDays',
            'prepay_days_limit' => 'prepayDaysLimit',
            'house' => 'house',
            'house_id' => 'houseId',
            'pic' => 'pic',
            'note' => 'note',
            'deal_crowd' => 'dealCrowd',
            'deal_tag_name' => 'dealTagName',
            'deal_tag_desc' => 'dealTagDesc',
            'coupon' => 'coupon',
            'coupon_type' => 'couponType',
            'min_loan_total_count' => 'minLoanTotalCount',
            'min_loan_total_amount' => 'minLoanTotalAmount',
            'min_loan_total_limit_relation' => 'minLoanTotalLimitRelation',
        );
    }

    public function getSource()
    {
        return "firstp2p_pre_deal_copy";
    }
}