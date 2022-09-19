<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pJobs extends ModelBaseNoTime
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
    public $function;


    /**
     *
     * @var string
     */
    public $params;


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
    public $start_time;


    /**
     *
     * @var integer
     */
    public $finish_time;


    /**
     *
     * @var integer
     */
    public $retry_cnt;


    /**
     *
     * @var float
     */
    public $job_cost;


    /**
     *
     * @var integer
     */
    public $begin_time;


    /**
     *
     * @var string
     */
    public $err_msg;


    /**
     *
     * @var integer
     */
    public $priority;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->status = '0';
        $this->retryCnt = '3';
        $this->jobCost = '0.000000';
        $this->beginTime = '0';
        $this->priority = '0';
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
            'function' => 'function',
            'params' => 'params',
            'status' => 'status',
            'create_time' => 'createTime',
            'start_time' => 'startTime',
            'finish_time' => 'finishTime',
            'retry_cnt' => 'retryCnt',
            'job_cost' => 'jobCost',
            'begin_time' => 'beginTime',
            'err_msg' => 'errMsg',
            'priority' => 'priority',
        );
    }

    public function getSource()
    {
        return "firstp2p_jobs";
    }
}