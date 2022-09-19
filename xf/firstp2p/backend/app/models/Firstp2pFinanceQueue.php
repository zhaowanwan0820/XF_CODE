<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pFinanceQueue extends ModelBaseNoTime
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
    public $create_time;


    /**
     *
     * @var integer
     */
    public $priority;


    /**
     *
     * @var string
     */
    public $content;


    /**
     *
     * @var string
     */
    public $sign;


    /**
     *
     * @var string
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
    public $req_status;


    /**
     *
     * @var integer
     */
    public $req_time;


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
        $this->createTime = '0';
        $this->priority = '0';
        $this->sign = '';
        $this->type = '';
        $this->status = '0';
        $this->reqStatus = '0';
        $this->reqTime = '0';
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
            'create_time' => 'createTime',
            'priority' => 'priority',
            'content' => 'content',
            'sign' => 'sign',
            'type' => 'type',
            'status' => 'status',
            'req_status' => 'reqStatus',
            'req_time' => 'reqTime',
            'next_req_time' => 'nextReqTime',
            'req_times' => 'reqTimes',
        );
    }

    public function getSource()
    {
        return "firstp2p_finance_queue";
    }
}