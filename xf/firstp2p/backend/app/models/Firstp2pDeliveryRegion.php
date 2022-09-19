<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDeliveryRegion extends ModelBaseNoTime
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
    public $pid;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var integer
     */
    public $region_level;


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
            'pid' => 'pid',
            'name' => 'name',
            'region_level' => 'regionLevel',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_delivery_region";
    }
}