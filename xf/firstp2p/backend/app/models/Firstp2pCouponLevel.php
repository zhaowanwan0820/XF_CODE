<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponLevel extends ModelBaseNoTime
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
    public $group_id;


    /**
     *
     * @var string
     */
    public $level;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $valid_days;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->groupId = '1';
        $this->level = '';
        $this->money = '0.00';
        $this->validDays = '0.0000';
        $this->isEffect = '1';
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
            'group_id' => 'groupId',
            'level' => 'level',
            'money' => 'money',
            'valid_days' => 'validDays',
            'is_effect' => 'isEffect',
            'update_time' => 'updateTime',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_level";
    }
}