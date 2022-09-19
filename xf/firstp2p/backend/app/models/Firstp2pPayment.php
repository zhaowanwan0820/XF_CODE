<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPayment extends ModelBaseNoTime
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
    public $class_name;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $online_pay;


    /**
     *
     * @var float
     */
    public $fee_amount;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $description;


    /**
     *
     * @var float
     */
    public $total_amount;


    /**
     *
     * @var string
     */
    public $config;


    /**
     *
     * @var string
     */
    public $logo;


    /**
     *
     * @var integer
     */
    public $sort;


    /**
     *
     * @var integer
     */
    public $fee_type;


    /**
     *
     * @var float
     */
    public $max_fee;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->maxFee = '0.0000';
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
            'class_name' => 'className',
            'is_effect' => 'isEffect',
            'online_pay' => 'onlinePay',
            'fee_amount' => 'feeAmount',
            'name' => 'name',
            'description' => 'description',
            'total_amount' => 'totalAmount',
            'config' => 'config',
            'logo' => 'logo',
            'sort' => 'sort',
            'fee_type' => 'feeType',
            'max_fee' => 'maxFee',
        );
    }

    public function getSource()
    {
        return "firstp2p_payment";
    }
}