<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusConsume extends ModelBaseNoTime
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
     * @var string
     */
    public $out_order_id;


    /**
     *
     * @var integer
     */
    public $channel;


    /**
     *
     * @var float
     */
    public $amount;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $notify_time;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var string
     */
    public $info;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->outOrderId = '';
        $this->channel = '0';
        $this->amount = '0.00';
        $this->status = '0';
        $this->notifyTime = '0';
        $this->createTime = '0';
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
            'out_order_id' => 'outOrderId',
            'channel' => 'channel',
            'amount' => 'amount',
            'status' => 'status',
            'notify_time' => 'notifyTime',
            'create_time' => 'createTime',
            'info' => 'info',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_consume";
    }
}