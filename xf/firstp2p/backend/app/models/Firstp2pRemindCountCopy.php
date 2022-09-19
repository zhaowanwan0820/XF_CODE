<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pRemindCountCopy extends ModelBaseNoTime
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
    public $topic_count;


    /**
     *
     * @var integer
     */
    public $topic_count_time;


    /**
     *
     * @var integer
     */
    public $dp_count;


    /**
     *
     * @var integer
     */
    public $dp_count_time;


    /**
     *
     * @var integer
     */
    public $msg_count;


    /**
     *
     * @var integer
     */
    public $msg_count_time;


    /**
     *
     * @var integer
     */
    public $buy_msg_count;


    /**
     *
     * @var integer
     */
    public $buy_msg_count_time;


    /**
     *
     * @var integer
     */
    public $order_count;


    /**
     *
     * @var integer
     */
    public $order_count_time;


    /**
     *
     * @var integer
     */
    public $refund_count;


    /**
     *
     * @var integer
     */
    public $refund_count_time;


    /**
     *
     * @var integer
     */
    public $retake_count;


    /**
     *
     * @var integer
     */
    public $retake_count_time;


    /**
     *
     * @var integer
     */
    public $incharge_count;


    /**
     *
     * @var integer
     */
    public $incharge_count_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

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
            'topic_count' => 'topicCount',
            'topic_count_time' => 'topicCountTime',
            'dp_count' => 'dpCount',
            'dp_count_time' => 'dpCountTime',
            'msg_count' => 'msgCount',
            'msg_count_time' => 'msgCountTime',
            'buy_msg_count' => 'buyMsgCount',
            'buy_msg_count_time' => 'buyMsgCountTime',
            'order_count' => 'orderCount',
            'order_count_time' => 'orderCountTime',
            'refund_count' => 'refundCount',
            'refund_count_time' => 'refundCountTime',
            'retake_count' => 'retakeCount',
            'retake_count_time' => 'retakeCountTime',
            'incharge_count' => 'inchargeCount',
            'incharge_count_time' => 'inchargeCountTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_remind_count_copy";
    }
}