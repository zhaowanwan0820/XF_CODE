<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusActivity extends ModelBaseNoTime
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
    public $is_fixed;


    /**
     *
     * @var string
     */
    public $multiple_money;


    /**
     *
     * @var float
     */
    public $range_money_start;


    /**
     *
     * @var float
     */
    public $range_money_end;


    /**
     *
     * @var integer
     */
    public $is_diff_new_old_user;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $load_limit;


    /**
     *
     * @var integer
     */
    public $link_invalid_date;


    /**
     *
     * @var integer
     */
    public $valid_day;


    /**
     *
     * @var string
     */
    public $subject;


    /**
     *
     * @var string
     */
    public $icon;


    /**
     *
     * @var string
     */
    public $desc;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $temp_id;


    /**
     *
     * @var integer
     */
    public $group_id;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->isFixed = '0';
        $this->rangeMoneyStart = '0.00';
        $this->rangeMoneyEnd = '0.00';
        $this->isDiffNewOldUser = '0';
        $this->loadLimit = '0';
        $this->validDay = '3';
        $this->status = '1';
        $this->tempId = '';
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
            'is_fixed' => 'isFixed',
            'multiple_money' => 'multipleMoney',
            'range_money_start' => 'rangeMoneyStart',
            'range_money_end' => 'rangeMoneyEnd',
            'is_diff_new_old_user' => 'isDiffNewOldUser',
            'type' => 'type',
            'load_limit' => 'loadLimit',
            'link_invalid_date' => 'linkInvalidDate',
            'valid_day' => 'validDay',
            'subject' => 'subject',
            'icon' => 'icon',
            'desc' => 'desc',
            'status' => 'status',
            'temp_id' => 'tempId',
            'group_id' => 'groupId',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_activity";
    }
}