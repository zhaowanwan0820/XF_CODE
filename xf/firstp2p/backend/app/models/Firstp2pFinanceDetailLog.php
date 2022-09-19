<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pFinanceDetailLog extends ModelBaseNoTime
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
    public $outOrderId;


    /**
     *
     * @var integer
     */
    public $payerId;


    /**
     *
     * @var integer
     */
    public $receiverId;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $repaymentAmount;


    /**
     *
     * @var string
     */
    public $curType;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var string
     */
    public $cate;


    /**
     *
     * @var string
     */
    public $reason;


    /**
     *
     * @var integer
     */
    public $next_req_time;


    /**
     *
     * @var integer
     */
    public $req_times;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->outOrderId = '';
        $this->payerId = '0';
        $this->receiverId = '0';
        $this->repaymentAmount = '0';
        $this->curType = 'CNY';
        $this->createTime = '0';
        $this->cate = '';
        $this->reason = '';
        $this->nextReqTime = '0';
        $this->reqTimes = '0';
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
            'outOrderId' => 'outOrderId',
            'payerId' => 'payerId',
            'receiverId' => 'receiverId',
            'status' => 'status',
            'repaymentAmount' => 'repaymentAmount',
            'curType' => 'curType',
            'create_time' => 'createTime',
            'cate' => 'cate',
            'reason' => 'reason',
            'next_req_time' => 'nextReqTime',
            'req_times' => 'reqTimes',
        );
    }

    public function getSource()
    {
        return "firstp2p_finance_detail_log";
    }
}