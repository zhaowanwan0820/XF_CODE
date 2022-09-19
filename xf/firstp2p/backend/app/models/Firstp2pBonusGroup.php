<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBonusGroup extends ModelBaseNoTime
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
    public $deal_load_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $bonus_type_id;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $deal_load_money;


    /**
     *
     * @var integer
     */
    public $count;


    /**
     *
     * @var integer
     */
    public $created_at;


    /**
     *
     * @var integer
     */
    public $expired_at;


    /**
     *
     * @var integer
     */
    public $batch_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealLoadId = '0';
        $this->userId = '0';
        $this->dealId = '0';
        $this->bonusTypeId = '0';
        $this->money = '0.00';
        $this->dealLoadMoney = '0.00';
        $this->count = '0';
        $this->createdAt = '0';
        $this->expiredAt = '0';
        $this->batchId = '0';
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
            'deal_load_id' => 'dealLoadId',
            'user_id' => 'userId',
            'deal_id' => 'dealId',
            'bonus_type_id' => 'bonusTypeId',
            'money' => 'money',
            'deal_load_money' => 'dealLoadMoney',
            'count' => 'count',
            'created_at' => 'createdAt',
            'expired_at' => 'expiredAt',
            'batch_id' => 'batchId',
        );
    }

    public function getSource()
    {
        return "firstp2p_bonus_group";
    }
}