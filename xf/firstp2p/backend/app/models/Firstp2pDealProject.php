<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealProject extends ModelBaseNoTime
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
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $risk_bearing;


    /**
     *
     * @var float
     */
    public $borrow_amount;


    /**
     *
     * @var integer
     */
    public $loantype;


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
     * @var float
     */
    public $money_borrowed;


    /**
     *
     * @var float
     */
    public $money_loaned;


    /**
     *
     * @var string
     */
    public $intro;


    /**
     *
     * @var string
     */
    public $approve_number;


    /**
     *
     * @var string
     */
    public $credit;


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
    public $deal_type;


    /**
     *
     * @var string
     */
    public $project_info_url;


    /**
     *
     * @var string
     */
    public $project_extrainfo_url;


    /**
     *
     * @var integer
     */
    public $borrow_fee_type;


    /**
     *
     * @var integer
     */
    public $loan_money_type;


    /**
     *
     * @var string
     */
    public $card_name;


    /**
     *
     * @var integer
     */
    public $card_type;


    /**
     *
     * @var string
     */
    public $bankcard;


    /**
     *
     * @var string
     */
    public $bankzone;


    /**
     *
     * @var integer
     */
    public $bank_id;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $entrust_sign;


    /**
     *
     * @var integer
     */
    public $entrust_agency_sign;


    /**
     *
     * @var integer
     */
    public $entrust_advisory_sign;


    /**
     *
     * @var string
     */
    public $product_class;


    /**
     *
     * @var string
     */
    public $product_name;


    /**
     *
     * @var string
     */
    public $product_mix_1;


    /**
     *
     * @var string
     */
    public $product_mix_2;


    /**
     *
     * @var string
     */
    public $product_mix_3;


    /**
     *
     * @var integer
     */
    public $fixed_value_date;


    /**
     *
     * @var integer
     */
    public $business_status;


    /**
     *
     * @var string
     */
    public $assets_desc;

    /**
     *
     * @var string
     */
    public $post_loan_message;

    /**
     * @var int
     */
    public $clearing_type;


    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->userId = '0';
        $this->riskBearing = '0';
        $this->borrowAmount = '0.00';
        $this->loantype = '0';
        $this->repayTime = '0';
        $this->moneyBorrowed = '0.00';
        $this->moneyLoaned = '0.00';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->dealType = '0';
        $this->projectInfoUrl = '';
        $this->projectExtrainfoUrl = '';
        $this->borrowFeeType = '0';
        $this->loanMoneyType = '0';
        $this->cardName = '';
        $this->cardType = '0';
        $this->bankzone = '';
        $this->bankId = '0';
        $this->entrustAgencySign = '0';
        $this->entrustAdvisorySign = '0';
        $this->productMix1 = '';
        $this->productMix2 = '';
        $this->productMix3 = '';
        $this->fixedValueDate = '0';
        $this->businessStatus = '0';
        $this->assetsDesc = '';
        $this->postLoanMessage = '';
        $this->clearingType = '0';
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
            'user_id' => 'userId',
            'risk_bearing' => 'riskBearing',
            'borrow_amount' => 'borrowAmount',
            'loantype' => 'loantype',
            'repay_time' => 'repayTime',
            'rate' => 'rate',
            'money_borrowed' => 'moneyBorrowed',
            'money_loaned' => 'moneyLoaned',
            'intro' => 'intro',
            'approve_number' => 'approveNumber',
            'credit' => 'credit',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'deal_type' => 'dealType',
            'project_info_url' => 'projectInfoUrl',
            'project_extrainfo_url' => 'projectExtrainfoUrl',
            'borrow_fee_type' => 'borrowFeeType',
            'loan_money_type' => 'loanMoneyType',
            'card_name' => 'cardName',
            'card_type' => 'cardType',
            'bankcard' => 'bankcard',
            'bankzone' => 'bankzone',
            'bank_id' => 'bankId',
            'status' => 'status',
            'entrust_sign' => 'entrustSign',
            'entrust_agency_sign' => 'entrustAgencySign',
            'entrust_advisory_sign' => 'entrustAdvisorySign',
            'product_class' => 'productClass',
            'product_name' => 'productName',
            'product_mix_1' => 'productMix1',
            'product_mix_2' => 'productMix2',
            'product_mix_3' => 'productMix3',
            'fixed_value_date' => 'fixedValueDate',
            'business_status' => 'businessStatus',
            'assets_desc' => 'assetsDesc',
            'post_loan_message' => 'postLoanMessage',
            'clearing_type' => 'clearingType',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_project";
    }
}
