<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusTask extends ModelBaseNoTime
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
    public $name;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $times;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $count;


    /**
     *
     * @var integer
     */
    public $consume_type;


    /**
     *
     * @var integer
     */
    public $send_way;


    /**
     *
     * @var string
     */
    public $send_condition;


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
    public $is_sms;


    /**
     *
     * @var integer
     */
    public $sms_temp_id;


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
    public $start_time;


    /**
     *
     * @var integer
     */
    public $continue_times;


    /**
     *
     * @var string
     */
    public $source;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->type = '0';
        $this->times = '0';
        $this->money = '0.00';
        $this->count = '0';
        $this->consumeType = '0';
        $this->sendWay = '1';
        $this->sendCondition = '';
        $this->sendLimitDay = '0';
        $this->useLimitDay = '0';
        $this->isSms = '0';
        $this->smsTempId = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->startTime = '0';
        $this->continueTimes = '0';
        $this->source = '';
        $this->isEffect = '1';
        $this->status = '0';
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
            'name' => 'name',
            'type' => 'type',
            'times' => 'times',
            'money' => 'money',
            'count' => 'count',
            'consume_type' => 'consumeType',
            'send_way' => 'sendWay',
            'send_condition' => 'sendCondition',
            'send_limit_day' => 'sendLimitDay',
            'use_limit_day' => 'useLimitDay',
            'is_sms' => 'isSms',
            'sms_temp_id' => 'smsTempId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'start_time' => 'startTime',
            'continue_times' => 'continueTimes',
            'source' => 'source',
            'is_effect' => 'isEffect',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_task";
    }
}