<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDeal extends ModelBaseNoTime
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
     * @var integer
     */
    public $pay_agency_id;


    /**
     *
     * @var integer
     */
    public $entrust_agency_id;


    /**
     *
     * @var integer
     */
    public $management_agency_id;


    /**
     *
     * @var integer
     */
    public $generation_recharge_id;


    /**
     *
     * @var integer
     */
    public $advance_agency_id;


    /**
     *
     * @var integer
     */
    public $user_id;


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
    public $sort;


    /**
     *
     * @var integer
     */
    public $type_id;


    /**
     *
     * @var integer
     */
    public $icon_type;


    /**
     *
     * @var string
     */
    public $icon;


    /**
     *
     * @var string
     */
    public $seo_title;


    /**
     *
     * @var string
     */
    public $seo_keyword;


    /**
     *
     * @var string
     */
    public $seo_description;


    /**
     *
     * @var integer
     */
    public $is_float_min_loan;


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
    public $day;


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
     * @var string
     */
    public $name_match;


    /**
     *
     * @var string
     */
    public $name_match_row;


    /**
     *
     * @var string
     */
    public $deal_cate_match;


    /**
     *
     * @var string
     */
    public $deal_cate_match_row;


    /**
     *
     * @var integer
     */
    public $deal_crowd;


    /**
     *
     * @var integer
     */
    public $bid_restrict;


    /**
     *
     * @var string
     */
    public $tag_match;


    /**
     *
     * @var string
     */
    public $tag_match_row;


    /**
     *
     * @var string
     */
    public $type_match;


    /**
     *
     * @var string
     */
    public $type_match_row;


    /**
     *
     * @var integer
     */
    public $is_recommend;


    /**
     *
     * @var integer
     */
    public $buy_count;


    /**
     *
     * @var float
     */
    public $load_money;


    /**
     *
     * @var float
     */
    public $repay_money;


    /**
     *
     * @var integer
     */
    public $start_time;


    /**
     *
     * @var integer
     */
    public $success_time;


    /**
     *
     * @var integer
     */
    public $repay_start_time;


    /**
     *
     * @var integer
     */
    public $last_repay_time;


    /**
     *
     * @var integer
     */
    public $next_repay_time;


    /**
     *
     * @var integer
     */
    public $bad_time;


    /**
     *
     * @var integer
     */
    public $deal_status;


    /**
     *
     * @var integer
     */
    public $enddate;


    /**
     *
     * @var integer
     */
    public $voffice;


    /**
     *
     * @var integer
     */
    public $vposition;


    /**
     *
     * @var string
     */
    public $services_fee;


    /**
     *
     * @var integer
     */
    public $publish_wait;


    /**
     *
     * @var integer
     */
    public $is_send_bad_msg;


    /**
     *
     * @var string
     */
    public $bad_msg;


    /**
     *
     * @var integer
     */
    public $send_half_msg_time;


    /**
     *
     * @var integer
     */
    public $send_three_msg_time;


    /**
     *
     * @var integer
     */
    public $is_send_half_msg;


    /**
     *
     * @var integer
     */
    public $is_has_loans;


    /**
     *
     * @var integer
     */
    public $is_during_repay;


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
     * @var integer
     */
    public $sub_count;


    /**
     *
     * @var integer
     */
    public $parent_id;


    /**
     *
     * @var integer
     */
    public $is_visible;


    /**
     *
     * @var integer
     */
    public $is_update;


    /**
     *
     * @var string
     */
    public $update_json;


    /**
     *
     * @var float
     */
    public $period_rate;


    /**
     *
     * @var float
     */
    public $point_percent;


    /**
     *
     * @var integer
     */
    public $is_sub_deal_loaded;


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
    public $pay_fee_rate;


    /**
     *
     * @var float
     */
    public $management_fee_rate;


    /**
     *
     * @var float
     */
    public $income_fee_rate;


    /**
     *
     * @var float
     */
    public $income_total_rate;


    /**
     *
     * @var float
     */
    public $advisor_fee_rate;


    /**
     *
     * @var string
     */
    public $manage_fee_text;


    /**
     *
     * @var string
     */
    public $contract_tpl_type;


    /**
     *
     * @var integer
     */
    public $is_simple_interest;


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
     * @var float
     */
    public $overdue_rate;


    /**
     *
     * @var integer
     */
    public $overdue_day;


    /**
     *
     * @var string
     */
    public $note;


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
     * @var integer
     */
    public $project_id;


    /**
     *
     * @var string
     */
    public $approve_number;


    /**
     *
     * @var integer
     */
    public $deal_type;


    /**
     *
     * @var integer
     */
    public $is_doing;


    /**
     *
     * @var float
     */
    public $packing_rate;


    /**
     *
     * @var float
     */
    public $annual_payment_rate;


    /**
     *
     * @var integer
     */
    public $report_status;


    /**
     *
     * @var integer
     */
    public $report_type;


    /**
     *
     * @var integer
     */
    public $site_id;


    /**
     *
     * @var integer
     */
    public $jys_id;


    /**
     *
     * @var string
     */
    public $jys_record_number;



    public $canal_agency_id;
    public $canal_fee_rate;

    public $consult_fee_period_rate;

    public $product_class_type;
    public $loan_user_customer_type;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->advisoryId = '0';
        $this->payAgencyId = '0';
        $this->entrustAgencyId = '0';
        $this->managementAgencyId = '0';
        $this->generationRechargeId = '0';
        $this->advanceAgencyId = '0';
        $this->minLoanMoney = '10000';
        $this->maxLoanMoney = '0';
        $this->dealCrowd = '0';
        $this->bidRestrict = '0';
        $this->isDuringRepay = '0';
        $this->subCount = '0';
        $this->parentId = '-1';
        $this->isVisible = '1';
        $this->isUpdate = '0';
        $this->periodRate = '0.00000';
        $this->pointPercent = '0.0000000000';
        $this->isSubDealLoaded = '0';
        $this->loanFeeRate = '0.00000';
        $this->consultFeeRate = '0.00000';
        $this->guaranteeFeeRate = '0.00000';
        $this->manageFeeRate = '0.00000';
        $this->payFeeRate = '0.00000';
        $this->managementFeeRate = '0.00000';
        $this->incomeFeeRate = '0.00000';
        $this->incomeTotalRate = '0.00000';
        $this->advisorFeeRate = '0.00000';
        $this->contractTplType = 'DF';
        $this->isSimpleInterest = '1';
        $this->prepayRate = '0.00000';
        $this->compensationDays = '0.00';
        $this->loanCompensationDays = '0.00';
        $this->prepayPenaltyDays = '0';
        $this->prepayDaysLimit = '0';
        $this->overdueRate = '1.00000';
        $this->overdueDay = '0';
        $this->coupon = '';
        $this->couponType = '0';
        $this->minLoanTotalCount = '0';
        $this->minLoanTotalAmount = '0.00';
        $this->minLoanTotalLimitRelation = '0';
        $this->dealTagName = '';
        $this->dealTagDesc = '';
        $this->projectId = '0';
        $this->dealType = '0';
        $this->packingRate = '0.00000';
        $this->annualPaymentRate = '0.00000';
        $this->reportStatus = '0';
        $this->reportType = '0';
        $this->siteId = '0';
        $this->jysId = '0';
        $this->jysRecordNumber = '';
        $this->canalAgencyId = '0';
        $this->canalFeeRate = '0.00000';
        $this->consultFeePeriodRate = '0.00000000';
        $this->productClassType = 0;
        $this->loanUserCustomerType = 0;
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
            'pay_agency_id' => 'payAgencyId',
            'entrust_agency_id' => 'entrustAgencyId',
            'management_agency_id' => 'managementAgencyId',
            'generation_recharge_id' => 'generationRechargeId',
            'advance_agency_id' => 'advanceAgencyId',
            'user_id' => 'userId',
            'manager' => 'manager',
            'manager_mobile' => 'managerMobile',
            'description' => 'description',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'sort' => 'sort',
            'type_id' => 'typeId',
            'icon_type' => 'iconType',
            'icon' => 'icon',
            'seo_title' => 'seoTitle',
            'seo_keyword' => 'seoKeyword',
            'seo_description' => 'seoDescription',
            'is_float_min_loan' => 'isFloatMinLoan',
            'borrow_amount' => 'borrowAmount',
            'min_loan_money' => 'minLoanMoney',
            'max_loan_money' => 'maxLoanMoney',
            'repay_time' => 'repayTime',
            'rate' => 'rate',
            'day' => 'day',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'name_match' => 'nameMatch',
            'name_match_row' => 'nameMatchRow',
            'deal_cate_match' => 'dealCateMatch',
            'deal_cate_match_row' => 'dealCateMatchRow',
            'deal_crowd' => 'dealCrowd',
            'bid_restrict' => 'bidRestrict',
            'tag_match' => 'tagMatch',
            'tag_match_row' => 'tagMatchRow',
            'type_match' => 'typeMatch',
            'type_match_row' => 'typeMatchRow',
            'is_recommend' => 'isRecommend',
            'buy_count' => 'buyCount',
            'load_money' => 'loadMoney',
            'repay_money' => 'repayMoney',
            'start_time' => 'startTime',
            'success_time' => 'successTime',
            'repay_start_time' => 'repayStartTime',
            'last_repay_time' => 'lastRepayTime',
            'next_repay_time' => 'nextRepayTime',
            'bad_time' => 'badTime',
            'deal_status' => 'dealStatus',
            'enddate' => 'enddate',
            'voffice' => 'voffice',
            'vposition' => 'vposition',
            'services_fee' => 'servicesFee',
            'publish_wait' => 'publishWait',
            'is_send_bad_msg' => 'isSendBadMsg',
            'bad_msg' => 'badMsg',
            'send_half_msg_time' => 'sendHalfMsgTime',
            'send_three_msg_time' => 'sendThreeMsgTime',
            'is_send_half_msg' => 'isSendHalfMsg',
            'is_has_loans' => 'isHasLoans',
            'is_during_repay' => 'isDuringRepay',
            'loantype' => 'loantype',
            'warrant' => 'warrant',
            'sub_count' => 'subCount',
            'parent_id' => 'parentId',
            'is_visible' => 'isVisible',
            'is_update' => 'isUpdate',
            'update_json' => 'updateJson',
            'period_rate' => 'periodRate',
            'point_percent' => 'pointPercent',
            'is_sub_deal_loaded' => 'isSubDealLoaded',
            'loan_fee_rate' => 'loanFeeRate',
            'consult_fee_rate' => 'consultFeeRate',
            'guarantee_fee_rate' => 'guaranteeFeeRate',
            'manage_fee_rate' => 'manageFeeRate',
            'pay_fee_rate' => 'payFeeRate',
            'management_fee_rate' => 'managementFeeRate',
            'income_fee_rate' => 'incomeFeeRate',
            'income_total_rate' => 'incomeTotalRate',
            'advisor_fee_rate' => 'advisorFeeRate',
            'manage_fee_text' => 'manageFeeText',
            'contract_tpl_type' => 'contractTplType',
            'is_simple_interest' => 'isSimpleInterest',
            'prepay_rate' => 'prepayRate',
            'compensation_days' => 'compensationDays',
            'loan_compensation_days' => 'loanCompensationDays',
            'prepay_penalty_days' => 'prepayPenaltyDays',
            'prepay_days_limit' => 'prepayDaysLimit',
            'overdue_rate' => 'overdueRate',
            'overdue_day' => 'overdueDay',
            'note' => 'note',
            'coupon' => 'coupon',
            'coupon_type' => 'couponType',
            'min_loan_total_count' => 'minLoanTotalCount',
            'min_loan_total_amount' => 'minLoanTotalAmount',
            'min_loan_total_limit_relation' => 'minLoanTotalLimitRelation',
            'deal_tag_name' => 'dealTagName',
            'deal_tag_desc' => 'dealTagDesc',
            'project_id' => 'projectId',
            'approve_number' => 'approveNumber',
            'deal_type' => 'dealType',
            'is_doing' => 'isDoing',
            'packing_rate' => 'packingRate',
            'annual_payment_rate' => 'annualPaymentRate',
            'report_status' => 'reportStatus',
            'report_type' => 'reportType',
            'site_id' => 'siteId',
            'jys_id' => 'jysId',
            'jys_record_number' => 'jysRecordNumber',
            'canal_agency_id' => 'canalAgencyId',
            'canal_fee_rate' => 'canalFeeRate',
            'consult_fee_period_rate' => 'consultFeePeriodRate',
            'product_class_type' => 'productClassType',
            'loan_user_customer_type' => 'loanUserCustomerType',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal";
    }
}
