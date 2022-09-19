<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDepositInterestRecord extends ModelBaseNoTime
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
    public $user_id;


    /**
     *
     * @var integer
     */
    public $time;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $rate;


    /**
     *
     * @var float
     */
    public $interest;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $confirm_time;


    /**
     *
     * @var integer
     */
    public $deal_loan_id;


    /**
     *
     * @var integer
     */
    public $admin_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->time = '0';
        $this->money = '0.00';
        $this->interest = '0.00';
        $this->status = '0';
        $this->createTime = '0';
        $this->confirmTime = '0';
        $this->dealLoanId = '0';
        $this->adminId = '0';
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
            'user_id' => 'userId',
            'time' => 'time',
            'money' => 'money',
            'rate' => 'rate',
            'interest' => 'interest',
            'type' => 'type',
            'status' => 'status',
            'create_time' => 'createTime',
            'confirm_time' => 'confirmTime',
            'deal_loan_id' => 'dealLoanId',
            'admin_id' => 'adminId',
        );
    }

    public function getSource()
    {
        return "firstp2p_deposit_interest_record";
    }
}