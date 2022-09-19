<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealLoadProve extends ModelBaseNoTime
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
    public $type;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $load_id;


    /**
     *
     * @var integer
     */
    public $apply_time;


    /**
     *
     * @var integer
     */
    public $cron_time;


    /**
     *
     * @var integer
     */
    public $effect_time;


    /**
     *
     * @var integer
     */
    public $send_time;


    /**
     *
     * @var integer
     */
    public $is_send;


    /**
     *
     * @var string
     */
    public $remark;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->type = '0';
        $this->dealId = '0';
        $this->loadId = '0';
        $this->applyTime = '0';
        $this->cronTime = '0';
        $this->effectTime = '0';
        $this->sendTime = '0';
        $this->isSend = '0';
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
            'deal_id' => 'dealId',
            'load_id' => 'loadId',
            'apply_time' => 'applyTime',
            'cron_time' => 'cronTime',
            'effect_time' => 'effectTime',
            'send_time' => 'sendTime',
            'is_send' => 'isSend',
            'remark' => 'remark',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_load_prove";
    }
}