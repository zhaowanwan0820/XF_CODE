<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBdActivityOrder extends ModelBaseNoTime
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
     * @var integer
     */
    public $relation_id;


    /**
     *
     * @var string
     */
    public $order_sn;


    /**
     *
     * @var integer
     */
    public $count;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var string
     */
    public $code;


    /**
     *
     * @var string
     */
    public $result;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var date
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $prize_id;


    /**
     *
     * @var string
     */
    public $coupon;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
        $this->relationId = '0';
        $this->orderSn = '';
        $this->count = '0';
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
            'user_id' => 'userId',
            'relation_id' => 'relationId',
            'order_sn' => 'orderSn',
            'count' => 'count',
            'status' => 'status',
            'code' => 'code',
            'result' => 'result',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'prize_id' => 'prizeId',
            'coupon' => 'coupon',
        );
    }

    public function getSource()
    {
        return "firstp2p_bd_activity_order";
    }
}