<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealChannelLog extends ModelBaseNoTime
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
    public $channel_id;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var float
     */
    public $advisor_fee_rate;


    /**
     *
     * @var float
     */
    public $pay_factor;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $deal_load_id;


    /**
     *
     * @var float
     */
    public $deal_load_money;


    /**
     *
     * @var float
     */
    public $pay_fee;


    /**
     *
     * @var integer
     */
    public $deal_status;


    /**
     *
     * @var integer
     */
    public $fee_status;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $pay_time;


    /**
     *
     * @var integer
     */
    public $add_type;


    /**
     *
     * @var integer
     */
    public $is_delete;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->channelId = '0';
        $this->dealId = '0';
        $this->advisorFeeRate = '0.00000';
        $this->payFactor = '1.0000';
        $this->userId = '0';
        $this->dealLoadId = '0';
        $this->dealLoadMoney = '0.0000';
        $this->payFee = '0.00';
        $this->dealStatus = '0';
        $this->feeStatus = '0';
        $this->createTime = '0';
        $this->payTime = '0';
        $this->addType = '1';
        $this->isDelete = '0';
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
            'channel_id' => 'channelId',
            'deal_id' => 'dealId',
            'advisor_fee_rate' => 'advisorFeeRate',
            'pay_factor' => 'payFactor',
            'user_id' => 'userId',
            'deal_load_id' => 'dealLoadId',
            'deal_load_money' => 'dealLoadMoney',
            'pay_fee' => 'payFee',
            'deal_status' => 'dealStatus',
            'fee_status' => 'feeStatus',
            'create_time' => 'createTime',
            'pay_time' => 'payTime',
            'add_type' => 'addType',
            'is_delete' => 'isDelete',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_channel_log";
    }
}