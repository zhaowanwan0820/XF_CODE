<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCreditAssignment extends ModelBaseNoTime
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
    public $deal_loan_id;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $buy_count;


    /**
     *
     * @var float
     */
    public $loan_money;


    /**
     *
     * @var integer
     */
    public $real_end_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $end_time;


    /**
     *
     * @var float
     */
    public $point_percent;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->buyCount = '0';
        $this->loanMoney = '0.00';
        $this->status = '0';
        $this->pointPercent = '0.0000000000';
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
            'deal_loan_id' => 'dealLoanId',
            'money' => 'money',
            'user_id' => 'userId',
            'create_time' => 'createTime',
            'buy_count' => 'buyCount',
            'loan_money' => 'loanMoney',
            'real_end_time' => 'realEndTime',
            'status' => 'status',
            'end_time' => 'endTime',
            'point_percent' => 'pointPercent',
        );
    }

    public function getSource()
    {
        return "firstp2p_credit_assignment";
    }
}