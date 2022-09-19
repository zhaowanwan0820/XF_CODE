<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserCarry extends ModelBaseNoTime
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
    public $user_id;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $fee;


    /**
     *
     * @var integer
     */
    public $bank_id;


    /**
     *
     * @var string
     */
    public $bankcard;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $update_time_step1;


    /**
     *
     * @var integer
     */
    public $update_time_step2;


    /**
     *
     * @var string
     */
    public $msg;


    /**
     *
     * @var string
     */
    public $desc;


    /**
     *
     * @var integer
     */
    public $warning_stat;


    /**
     *
     * @var float
     */
    public $money_limit;


    /**
     *
     * @var string
     */
    public $real_name;


    /**
     *
     * @var integer
     */
    public $region_lv1;


    /**
     *
     * @var integer
     */
    public $region_lv2;


    /**
     *
     * @var integer
     */
    public $region_lv3;


    /**
     *
     * @var integer
     */
    public $region_lv4;


    /**
     *
     * @var string
     */
    public $bankzone;


    /**
     *
     * @var integer
     */
    public $withdraw_status;


    /**
     *
     * @var integer
     */
    public $withdraw_time;


    /**
     *
     * @var string
     */
    public $withdraw_msg;


    /**
     *
     * @var integer
     */
    public $platform;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealId = '0';
        $this->type = '1';
        $this->warningStat = '0';
        $this->withdrawStatus = '0';
        $this->withdrawTime = '0';
        $this->withdrawMsg = '';
        $this->platform = '0';
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
            'user_id' => 'userId',
            'money' => 'money',
            'fee' => 'fee',
            'bank_id' => 'bankId',
            'bankcard' => 'bankcard',
            'create_time' => 'createTime',
            'deal_id' => 'dealId',
            'type' => 'type',
            'status' => 'status',
            'update_time' => 'updateTime',
            'update_time_step1' => 'updateTimeStep1',
            'update_time_step2' => 'updateTimeStep2',
            'msg' => 'msg',
            'desc' => 'desc',
            'warning_stat' => 'warningStat',
            'money_limit' => 'moneyLimit',
            'real_name' => 'realName',
            'region_lv1' => 'regionLv1',
            'region_lv2' => 'regionLv2',
            'region_lv3' => 'regionLv3',
            'region_lv4' => 'regionLv4',
            'bankzone' => 'bankzone',
            'withdraw_status' => 'withdrawStatus',
            'withdraw_time' => 'withdrawTime',
            'withdraw_msg' => 'withdrawMsg',
            'platform' => 'platform',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_carry";
    }
}