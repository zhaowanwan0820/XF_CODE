<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pReferrals extends ModelBaseNoTime
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
    public $rel_user_id;


    /**
     *
     * @var float
     */
    public $money;


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
    public $order_id;


    /**
     *
     * @var integer
     */
    public $score;

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
            'user_id' => 'userId',
            'rel_user_id' => 'relUserId',
            'money' => 'money',
            'create_time' => 'createTime',
            'pay_time' => 'payTime',
            'order_id' => 'orderId',
            'score' => 'score',
        );
    }

    public function getSource()
    {
        return "firstp2p_referrals";
    }
}