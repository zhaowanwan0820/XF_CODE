<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealRepay extends ModelBaseNoTime
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
     * @var float
     */
    public $repay_money;


    /**
     *
     * @var float
     */
    public $manage_money;


    /**
     *
     * @var float
     */
    public $impose_money;


    /**
     *
     * @var integer
     */
    public $repay_time;


    /**
     *
     * @var integer
     */
    public $true_repay_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var float
     */
    public $principal;


    /**
     *
     * @var float
     */
    public $interest;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->repayMoney = '0.00';
        $this->manageMoney = '0.00';
        $this->imposeMoney = '0.00';
        $this->principal = '0.00';
        $this->interest = '0.00';
        $this->loanFee = '0.00';
        $this->consultFee = '0.00';
        $this->guaranteeFee = '0.00';
        $this->payFee = '0.00';
        $this->managementFee = '0.00';
        $this->createTime = '0';
        $this->updateTime = '0';
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
            'repay_money' => 'repayMoney',
            'manage_money' => 'manageMoney',
            'impose_money' => 'imposeMoney',
            'repay_time' => 'repayTime',
            'true_repay_time' => 'trueRepayTime',
            'status' => 'status',
            'principal' => 'principal',
            'interest' => 'interest',
            'loan_fee' => 'loanFee',
            'consult_fee' => 'consultFee',
            'guarantee_fee' => 'guaranteeFee',
            'pay_fee' => 'payFee',
            'management_fee' => 'managementFee',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'deal_type' => 'dealType',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_repay";
    }
}