<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pTaskFail extends ModelBaseNoTime
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
    public $event;


    /**
     *
     * @var string
     */
    public $eventtype;


    /**
     *
     * @var integer
     */
    public $trycnt;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->trycnt = '0';
        $this->executeTime = XDateTime::now();
        $this->createTime = '0000-00-00 00:00:00';
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
            'event' => 'event',
            'eventtype' => 'eventtype',
            'trycnt' => 'trycnt',
            'execute_time' => 'executeTime',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_task_fail";
    }
}