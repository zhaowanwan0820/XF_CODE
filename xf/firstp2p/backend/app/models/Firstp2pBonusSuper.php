<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusSuper extends ModelBaseNoTime
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
    public $group_count;


    /**
     *
     * @var integer
     */
    public $bonus_count;


    /**
     *
     * @var float
     */
    public $group_money;


    /**
     *
     * @var integer
     */
    public $consume_type;


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
    public $frequency;


    /**
     *
     * @var string
     */
    public $hour_section;


    /**
     *
     * @var float
     */
    public $trigger_money;


    /**
     *
     * @var string
     */
    public $retweet_title;


    /**
     *
     * @var string
     */
    public $retweet_icon;


    /**
     *
     * @var string
     */
    public $retweet_desc;


    /**
     *
     * @var string
     */
    public $temp_id;


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
        $this->groupCount = '0';
        $this->bonusCount = '0';
        $this->groupMoney = '0.00';
        $this->consumeType = '0';
        $this->sendLimitDay = '0';
        $this->useLimitDay = '0';
        $this->startTime = '0';
        $this->endTime = '0';
        $this->frequency = '0';
        $this->hourSection = '';
        $this->triggerMoney = '0.00';
        $this->retweetTitle = '';
        $this->retweetIcon = '';
        $this->retweetDesc = '';
        $this->tempId = '';
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
            'group_count' => 'groupCount',
            'bonus_count' => 'bonusCount',
            'group_money' => 'groupMoney',
            'consume_type' => 'consumeType',
            'send_limit_day' => 'sendLimitDay',
            'use_limit_day' => 'useLimitDay',
            'start_time' => 'startTime',
            'end_time' => 'endTime',
            'frequency' => 'frequency',
            'hour_section' => 'hourSection',
            'trigger_money' => 'triggerMoney',
            'retweet_title' => 'retweetTitle',
            'retweet_icon' => 'retweetIcon',
            'retweet_desc' => 'retweetDesc',
            'temp_id' => 'tempId',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_super";
    }
}