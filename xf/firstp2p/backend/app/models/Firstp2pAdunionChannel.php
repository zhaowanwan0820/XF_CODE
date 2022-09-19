<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAdunionChannel extends ModelBaseNoTime
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
    public $type;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var integer
     */
    public $pub_id;


    /**
     *
     * @var string
     */
    public $link_coupon;


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


    /**
     *
     * @var integer
     */
    public $is_delete;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->linkCoupon = '';
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
            'type' => 'type',
            'name' => 'name',
            'pub_id' => 'pubId',
            'link_coupon' => 'linkCoupon',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'is_delete' => 'isDelete',
        );
    }

    public function getSource()
    {
        return "firstp2p_adunion_channel";
    }
}