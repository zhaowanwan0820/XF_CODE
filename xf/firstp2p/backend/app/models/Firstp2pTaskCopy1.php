<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pTaskCopy1 extends ModelBaseNoTime
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
    public $executing;


    /**
     *
     * @var string
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $nowtry;


    /**
     *
     * @var integer
     */
    public $maxtry;


    /**
     *
     * @var string
     */
    public $event;


    /**
     *
     * @var string
     */
    public $eventtype;


    /**
     *
     * @var string
     */
    public $priority;


    /**
     *
     * @var date
     */
    public $execute_time;


    /**
     *
     * @var date
     */
    public $create_time;


    /**
     *
     * @var date
     */
    public $update_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->executing = 'no';
        $this->nowtry = '0';
        $this->executeTime = '0000-00-00 00:00:00';
        $this->createTime = '0000-00-00 00:00:00';
        $this->updateTime = '0000-00-00 00:00:00';
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
            'executing' => 'executing',
            'status' => 'status',
            'nowtry' => 'nowtry',
            'maxtry' => 'maxtry',
            'event' => 'event',
            'eventtype' => 'eventtype',
            'priority' => 'priority',
            'execute_time' => 'executeTime',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_task_copy1";
    }
}