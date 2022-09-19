<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserLog0 extends ModelBaseNoTime
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
    public $log_info;


    /**
     *
     * @var integer
     */
    public $log_time;


    /**
     *
     * @var integer
     */
    public $log_admin_id;


    /**
     *
     * @var integer
     */
    public $log_user_id;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var integer
     */
    public $score;


    /**
     *
     * @var integer
     */
    public $point;


    /**
     *
     * @var float
     */
    public $quota;


    /**
     *
     * @var float
     */
    public $lock_money;


    /**
     *
     * @var float
     */
    public $remaining_money;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $deal_type;


    /**
     *
     * @var string
     */
    public $related_user_show_name;


    /**
     *
     * @var string
     */
    public $note;


    /**
     *
     * @var float
     */
    public $remaining_total_money;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var string
     */
    public $item_id;

    //END PROPERTY

    /**
     * 按该字段进行拆分数据表
     * @var string
     */
    const SHARDING_FIELD_KEY = 'userId';

    /**
     * 要拆分的数据表个数
     * @var int
     */
    const SHARDING_TABLE_NUM = 64;

    /**
     * 按该值进行拆分数据表，查询操作时需要指定
     * @var int
     */
    public static $shardFieldValue = null;

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->logInfo = '';
        $this->lockMoney = '0.0000';
        $this->dealType = '0';
        $this->relatedUserShowName = '';
        $this->note = '';
        $this->isDelete = '0';
        $this->itemId = '0';
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
            'log_info' => 'logInfo',
            'log_time' => 'logTime',
            'log_admin_id' => 'logAdminId',
            'log_user_id' => 'logUserId',
            'money' => 'money',
            'score' => 'score',
            'point' => 'point',
            'quota' => 'quota',
            'lock_money' => 'lockMoney',
            'remaining_money' => 'remainingMoney',
            'user_id' => 'userId',
            'deal_type' => 'dealType',
            'related_user_show_name' => 'relatedUserShowName',
            'note' => 'note',
            'remaining_total_money' => 'remainingTotalMoney',
            'is_delete' => 'isDelete',
            'item_id' => 'itemId',
        );
    }

    public function getSource()
    {
        return sprintf('firstp2p_user_log_%d', fmod(floatval(!is_null(self::$shardFieldValue) ? self::$shardFieldValue : $this->{self::SHARDING_FIELD_KEY}), self::SHARDING_TABLE_NUM));
    }

}