<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pOpLog extends ModelBaseNoTime
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
    public $op_name;


    /**
     *
     * @var string
     */
    public $op_content;


    /**
     *
     * @var integer
     */
    public $op_status;


    /**
     *
     * @var integer
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
        $this->opStatus = '0';
        $this->updateTime = XDateTime::now();
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
            'op_name' => 'opName',
            'op_content' => 'opContent',
            'op_status' => 'opStatus',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_op_log";
    }
}