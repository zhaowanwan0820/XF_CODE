<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealLoanRepay extends ModelBaseNoTime
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
    public $deal_repay_id;


    /**
     *
     * @var integer
     */
    public $deal_loan_id;


    /**
     *
     * @var integer
     */
    public $loan_user_id;


    /**
     *
     * @var integer
     */
    public $borrow_user_id;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $time;


    /**
     *
     * @var integer
     */
    public $real_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $deal_type;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->status = '0';
        $this->dealType = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
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
            'deal_repay_id' => 'dealRepayId',
            'deal_loan_id' => 'dealLoanId',
            'loan_user_id' => 'loanUserId',
            'borrow_user_id' => 'borrowUserId',
            'money' => 'money',
            'type' => 'type',
            'time' => 'time',
            'real_time' => 'realTime',
            'status' => 'status',
            'deal_type' => 'dealType',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_loan_repay";
    }
}