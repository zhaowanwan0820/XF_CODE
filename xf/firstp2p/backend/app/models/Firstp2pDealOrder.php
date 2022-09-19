<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealOrder extends ModelBaseNoTime
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
    public $order_sn;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $pay_status;


    /**
     *
     * @var float
     */
    public $total_price;


    /**
     *
     * @var float
     */
    public $pay_amount;


    /**
     *
     * @var integer
     */
    public $delivery_status;


    /**
     *
     * @var integer
     */
    public $order_status;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $return_total_score;


    /**
     *
     * @var float
     */
    public $refund_amount;


    /**
     *
     * @var string
     */
    public $admin_memo;


    /**
     *
     * @var string
     */
    public $memo;


    /**
     *
     * @var integer
     */
    public $region_lv1;


    /**
     *
     * @var integer
     */
    public $region_lv2;


    /**
     *
     * @var integer
     */
    public $region_lv3;


    /**
     *
     * @var integer
     */
    public $region_lv4;


    /**
     *
     * @var string
     */
    public $address;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $zip;


    /**
     *
     * @var string
     */
    public $consignee;


    /**
     *
     * @var float
     */
    public $deal_total_price;


    /**
     *
     * @var float
     */
    public $discount_price;


    /**
     *
     * @var float
     */
    public $delivery_fee;


    /**
     *
     * @var float
     */
    public $ecv_money;


    /**
     *
     * @var float
     */
    public $account_money;


    /**
     *
     * @var integer
     */
    public $delivery_id;


    /**
     *
     * @var integer
     */
    public $payment_id;


    /**
     *
     * @var float
     */
    public $payment_fee;


    /**
     *
     * @var float
     */
    public $return_total_money;


    /**
     *
     * @var integer
     */
    public $extra_status;


    /**
     *
     * @var integer
     */
    public $after_sale;


    /**
     *
     * @var float
     */
    public $refund_money;


    /**
     *
     * @var string
     */
    public $bank_id;


    /**
     *
     * @var string
     */
    public $referer;


    /**
     *
     * @var string
     */
    public $deal_ids;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var integer
     */
    public $refund_status;


    /**
     *
     * @var integer
     */
    public $retake_status;


    /**
     *
     * @var integer
     */
    public $user_delete;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userDelete = '0';
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
            'order_sn' => 'orderSn',
            'type' => 'type',
            'user_id' => 'userId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'pay_status' => 'payStatus',
            'total_price' => 'totalPrice',
            'pay_amount' => 'payAmount',
            'delivery_status' => 'deliveryStatus',
            'order_status' => 'orderStatus',
            'is_delete' => 'isDelete',
            'return_total_score' => 'returnTotalScore',
            'refund_amount' => 'refundAmount',
            'admin_memo' => 'adminMemo',
            'memo' => 'memo',
            'region_lv1' => 'regionLv1',
            'region_lv2' => 'regionLv2',
            'region_lv3' => 'regionLv3',
            'region_lv4' => 'regionLv4',
            'address' => 'address',
            'mobile' => 'mobile',
            'zip' => 'zip',
            'consignee' => 'consignee',
            'deal_total_price' => 'dealTotalPrice',
            'discount_price' => 'discountPrice',
            'delivery_fee' => 'deliveryFee',
            'ecv_money' => 'ecvMoney',
            'account_money' => 'accountMoney',
            'delivery_id' => 'deliveryId',
            'payment_id' => 'paymentId',
            'payment_fee' => 'paymentFee',
            'return_total_money' => 'returnTotalMoney',
            'extra_status' => 'extraStatus',
            'after_sale' => 'afterSale',
            'refund_money' => 'refundMoney',
            'bank_id' => 'bankId',
            'referer' => 'referer',
            'deal_ids' => 'dealIds',
            'user_name' => 'userName',
            'refund_status' => 'refundStatus',
            'retake_status' => 'retakeStatus',
            'user_delete' => 'userDelete',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_order";
    }
}