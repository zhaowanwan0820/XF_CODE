<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealOrderItem extends ModelBaseNoTime
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
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $number;


    /**
     *
     * @var float
     */
    public $unit_price;


    /**
     *
     * @var float
     */
    public $total_price;


    /**
     *
     * @var integer
     */
    public $delivery_status;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var integer
     */
    public $return_score;


    /**
     *
     * @var integer
     */
    public $return_total_score;


    /**
     *
     * @var string
     */
    public $attr;


    /**
     *
     * @var string
     */
    public $verify_code;


    /**
     *
     * @var integer
     */
    public $order_id;


    /**
     *
     * @var float
     */
    public $return_money;


    /**
     *
     * @var float
     */
    public $return_total_money;


    /**
     *
     * @var integer
     */
    public $buy_type;


    /**
     *
     * @var string
     */
    public $sub_name;


    /**
     *
     * @var string
     */
    public $attr_str;

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
            'deal_id' => 'dealId',
            'number' => 'number',
            'unit_price' => 'unitPrice',
            'total_price' => 'totalPrice',
            'delivery_status' => 'deliveryStatus',
            'name' => 'name',
            'return_score' => 'returnScore',
            'return_total_score' => 'returnTotalScore',
            'attr' => 'attr',
            'verify_code' => 'verifyCode',
            'order_id' => 'orderId',
            'return_money' => 'returnMoney',
            'return_total_money' => 'returnTotalMoney',
            'buy_type' => 'buyType',
            'sub_name' => 'subName',
            'attr_str' => 'attrStr',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_order_item";
    }
}