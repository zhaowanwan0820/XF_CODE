<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealPrepay extends ModelBaseNoTime
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
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $prepay_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $remark;


    /**
     *
     * @var integer
     */
    public $remain_days;


    /**
     *
     * @var float
     */
    public $prepay_money;


    /**
     *
     * @var float
     */
    public $prepay_interest;


    /**
     *
     * @var float
     */
    public $prepay_compensation;


    /**
     *
     * @var float
     */
    public $remain_principal;


    /**
     *
     * @var float
     */
    public $loan_fee;


    /**
     *
     * @var float
     */
    public $consult_fee;


    /**
     *
     * @var float
     */
    public $guarantee_fee;


    /**
     *
     * @var float
     */
    public $pay_fee;


    /**
     *
     * @var float
     */
    public $management_fee;


    /**
     *
     * @var integer
     */
    public $repay_type;


    /**
     *
     * @var float
     */
    public $pay_fee_remain;


    /**
     *
     * @var integer
     */
    public $deal_type;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealId = '0';
        $this->userId = '0';
        $this->prepayTime = '0';
        $this->status = '0';
        $this->loanFee = '0.00';
        $this->consultFee = '0.00';
        $this->guaranteeFee = '0.00';
        $this->payFee = '0.00';
        $this->managementFee = '0.00';
        $this->repayType = '0';
        $this->dealType = '0';
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
            'user_id' => 'userId',
            'prepay_time' => 'prepayTime',
            'status' => 'status',
            'remark' => 'remark',
            'remain_days' => 'remainDays',
            'prepay_money' => 'prepayMoney',
            'prepay_interest' => 'prepayInterest',
            'prepay_compensation' => 'prepayCompensation',
            'remain_principal' => 'remainPrincipal',
            'loan_fee' => 'loanFee',
            'consult_fee' => 'consultFee',
            'guarantee_fee' => 'guaranteeFee',
            'pay_fee' => 'payFee',
            'management_fee' => 'managementFee',
            'repay_type' => 'repayType',
            'pay_fee_remain' => 'payFeeRemain',
            'deal_type' => 'dealType',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_prepay";
    }
}