<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pFinanceAudit extends ModelBaseNoTime
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
    public $into_name;


    /**
     *
     * @var string
     */
    public $attach_name;


    /**
     *
     * @var string
     */
    public $out_name;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $attach_money;


    /**
     *
     * @var string
     */
    public $agency_name;


    /**
     *
     * @var float
     */
    public $agency_money;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var string
     */
    public $apply_user;


    /**
     *
     * @var integer
     */
    public $deal_load_id;


    /**
     *
     * @var integer
     */
    public $coupon_id;


    /**
     *
     * @var string
     */
    public $log;


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
     * @var string
     */
    public $admin;


    /**
     *
     * @var string
     */
    public $info;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->intoName = '';
        $this->attachName = '';
        $this->outName = '';
        $this->money = '0.00';
        $this->attachMoney = '0.00';
        $this->agencyName = '';
        $this->agencyMoney = '0.00';
        $this->status = '1';
        $this->type = '1';
        $this->applyUser = '';
        $this->dealLoadId = '0';
        $this->couponId = '0';
        $this->log = '';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->admin = '';
        $this->info = '';
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
            'into_name' => 'intoName',
            'attach_name' => 'attachName',
            'out_name' => 'outName',
            'money' => 'money',
            'attach_money' => 'attachMoney',
            'agency_name' => 'agencyName',
            'agency_money' => 'agencyMoney',
            'status' => 'status',
            'type' => 'type',
            'apply_user' => 'applyUser',
            'deal_load_id' => 'dealLoadId',
            'coupon_id' => 'couponId',
            'log' => 'log',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'admin' => 'admin',
            'info' => 'info',
        );
    }

    public function getSource()
    {
        return "firstp2p_finance_audit";
    }
}