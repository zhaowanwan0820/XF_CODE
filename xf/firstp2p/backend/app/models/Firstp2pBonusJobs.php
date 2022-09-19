<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusJobs extends ModelBaseNoTime
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
     * @var float
     */
    public $group_money;


    /**
     *
     * @var integer
     */
    public $bonus_count;


    /**
     *
     * @var integer
     */
    public $use_type;


    /**
     *
     * @var string
     */
    public $user_group;


    /**
     *
     * @var string
     */
    public $user_tag;


    /**
     *
     * @var integer
     */
    public $tag_relation;


    /**
     *
     * @var integer
     */
    public $group_validity;


    /**
     *
     * @var integer
     */
    public $bonus_validity;


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
    public $end_time;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $send_sms;


    /**
     *
     * @var string
     */
    public $sms_tpl;


    /**
     *
     * @var string
     */
    public $sms_tpl_params;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->groupCount = '0';
        $this->groupMoney = '0.00';
        $this->bonusCount = '0';
        $this->useType = '0';
        $this->userGroup = '';
        $this->userTag = '';
        $this->tagRelation = '0';
        $this->groupValidity = '0';
        $this->bonusValidity = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->startTime = '0';
        $this->endTime = '0';
        $this->isEffect = '1';
        $this->sendSms = '0';
        $this->smsTpl = '';
        $this->smsTplParams = '';
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
            'group_money' => 'groupMoney',
            'bonus_count' => 'bonusCount',
            'use_type' => 'useType',
            'user_group' => 'userGroup',
            'user_tag' => 'userTag',
            'tag_relation' => 'tagRelation',
            'group_validity' => 'groupValidity',
            'bonus_validity' => 'bonusValidity',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'start_time' => 'startTime',
            'end_time' => 'endTime',
            'is_effect' => 'isEffect',
            'send_sms' => 'sendSms',
            'sms_tpl' => 'smsTpl',
            'sms_tpl_params' => 'smsTplParams',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_jobs";
    }
}