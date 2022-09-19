<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pOpStatus extends ModelBaseNoTime
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
    public $op_type;


    /**
     *
     * @var integer
     */
    public $content_id;


    /**
     *
     * @var integer
     */
    public $trans_status;


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
        $this->contentId = '0';
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
            'op_type' => 'opType',
            'content_id' => 'contentId',
            'trans_status' => 'transStatus',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_op_status";
    }
}