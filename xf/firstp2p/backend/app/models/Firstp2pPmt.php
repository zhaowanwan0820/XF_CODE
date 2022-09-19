<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPmt extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var float
     */
    public $rate;


    /**
     *
     * @var integer
     */
    public $repay_time;


    /**
     *
     * @var integer
     */
    public $loantype;


    /**
     *
     * @var integer
     */
    public $repay_interval;


    /**
     *
     * @var float
     */
    public $repay_fee_rate;


    /**
     *
     * @var integer
     */
    public $repay_num;


    /**
     *
     * @var float
     */
    public $borrow_sum;


    /**
     *
     * @var float
     */
    public $borrow_amount;


    /**
     *
     * @var float
     */
    public $borrow_rate;


    /**
     *
     * @var float
     */
    public $fv;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var float
     */
    public $pmt;


    /**
     *
     * @var float
     */
    public $manage_fee_rate;


    /**
     *
     * @var float
     */
    public $interest;


    /**
     *
     * @var float
     */
    public $manage_fee;


    /**
     *
     * @var float
     */
    public $manage_rate;


    /**
     *
     * @var float
     */
    public $income_fee;


    /**
     *
     * @var float
     */
    public $real_repay_fee_rate;


    /**
     *
     * @var float
     */
    public $income_fee_rate;


    /**
     *
     * @var float
     */
    public $period_income_rate;


    /**
     *
     * @var float
     */
    public $simple_interest;


    /**
     *
     * @var float
     */
    public $compound_interest;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealId = '0';
        $this->rate = '0.0000000000';
        $this->repayTime = '0';
        $this->loantype = '0';
        $this->repayInterval = '0';
        $this->repayFeeRate = '0.0000000000';
        $this->repayNum = '0';
        $this->borrowSum = '0.0000000000';
        $this->borrowAmount = '0.0000000000';
        $this->borrowRate = '0.0000000000';
        $this->fv = '0.0000000000';
        $this->type = '0';
        $this->pmt = '0.0000000000';
        $this->manageFeeRate = '0.0000000000';
        $this->interest = '0.0000000000';
        $this->manageFee = '0.0000000000';
        $this->manageRate = '0.0000000000';
        $this->incomeFee = '0.0000000000';
        $this->realRepayFeeRate = '0.0000000000';
        $this->incomeFeeRate = '0.0000000000';
        $this->periodIncomeRate = '0.0000000000';
        $this->simpleInterest = '0.0000000000';
        $this->compoundInterest = '0.0000000000';
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
            'deal_id' => 'dealId',
            'rate' => 'rate',
            'repay_time' => 'repayTime',
            'loantype' => 'loantype',
            'repay_interval' => 'repayInterval',
            'repay_fee_rate' => 'repayFeeRate',
            'repay_num' => 'repayNum',
            'borrow_sum' => 'borrowSum',
            'borrow_amount' => 'borrowAmount',
            'borrow_rate' => 'borrowRate',
            'fv' => 'fv',
            'type' => 'type',
            'pmt' => 'pmt',
            'manage_fee_rate' => 'manageFeeRate',
            'interest' => 'interest',
            'manage_fee' => 'manageFee',
            'manage_rate' => 'manageRate',
            'income_fee' => 'incomeFee',
            'real_repay_fee_rate' => 'realRepayFeeRate',
            'income_fee_rate' => 'incomeFeeRate',
            'period_income_rate' => 'periodIncomeRate',
            'simple_interest' => 'simpleInterest',
            'compound_interest' => 'compoundInterest',
        );
    }

    public function getSource()
    {
        return "firstp2p_pmt";
    }
}