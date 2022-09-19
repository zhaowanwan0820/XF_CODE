<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBankChargeAuxiliary extends ModelBaseNoTime
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
    public $charge_id;


    /**
     *
     * @var string
     */
    public $category;


    /**
     *
     * @var string
     */
    public $card_type;


    /**
     *
     * @var string
     */
    public $one_money;


    /**
     *
     * @var string
     */
    public $date_norm;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $status;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->chargeId = '0';
        $this->category = '';
        $this->cardType = '';
        $this->oneMoney = '';
        $this->dateNorm = '';
        $this->createTime = '0';
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
            'charge_id' => 'chargeId',
            'category' => 'category',
            'card_type' => 'cardType',
            'one_money' => 'oneMoney',
            'date_norm' => 'dateNorm',
            'create_time' => 'createTime',
            'status' => 'status',
        );
    }

    public function getSource()
    {
        return "firstp2p_bank_charge_auxiliary";
    }
}