<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusDispatchConfig extends ModelBaseNoTime
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
    public $title;


    /**
     *
     * @var string
     */
    public $const_name;


    /**
     *
     * @var integer
     */
    public $is_group;


    /**
     *
     * @var integer
     */
    public $count;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $consume_type;


    /**
     *
     * @var integer
     */
    public $bonus_type;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $send_limit_day;


    /**
     *
     * @var integer
     */
    public $use_limit_day;


    /**
     *
     * @var integer
     */
    public $start_time;


    /**
     *
     * @var integer
     */
    public $end_time;


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
        $this->title = '';
        $this->constName = '';
        $this->isGroup = '0';
        $this->count = '0';
        $this->money = '0.00';
        $this->consumeType = '1';
        $this->bonusType = '0';
        $this->status = '1';
        $this->sendLimitDay = '0';
        $this->useLimitDay = '0';
        $this->startTime = '0';
        $this->endTime = '0';
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
            'title' => 'title',
            'const_name' => 'constName',
            'is_group' => 'isGroup',
            'count' => 'count',
            'money' => 'money',
            'consume_type' => 'consumeType',
            'bonus_type' => 'bonusType',
            'status' => 'status',
            'send_limit_day' => 'sendLimitDay',
            'use_limit_day' => 'useLimitDay',
            'start_time' => 'startTime',
            'end_time' => 'endTime',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_dispatch_config";
    }
}