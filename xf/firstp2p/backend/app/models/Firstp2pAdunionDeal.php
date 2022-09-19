<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAdunionDeal extends ModelBaseNoTime
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
    public $cn;


    /**
     *
     * @var string
     */
    public $euid;


    /**
     *
     * @var integer
     */
    public $mid;


    /**
     *
     * @var string
     */
    public $order_sn;


    /**
     *
     * @var date
     */
    public $order_time;


    /**
     *
     * @var integer
     */
    public $order_channel;


    /**
     *
     * @var integer
     */
    public $is_new_custom;


    /**
     *
     * @var string
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $goods_id;


    /**
     *
     * @var string
     */
    public $goods_name;


    /**
     *
     * @var integer
     */
    public $goods_ta;


    /**
     *
     * @var float
     */
    public $goods_price;


    /**
     *
     * @var float
     */
    public $total_price;


    /**
     *
     * @var float
     */
    public $commission;


    /**
     *
     * @var string
     */
    public $commission_type;


    /**
     *
     * @var float
     */
    public $rate;


    /**
     *
     * @var integer
     */
    public $goods_cate;


    /**
     *
     * @var string
     */
    public $goods_cate_name;


    /**
     *
     * @var date
     */
    public $created_at;


    /**
     *
     * @var date
     */
    public $updated_at;


    /**
     *
     * @var integer
     */
    public $uid;


    /**
     *
     * @var string
     */
    public $goods_cn;


    /**
     *
     * @var integer
     */
    public $goods_type;


    /**
     *
     * @var integer
     */
    public $days;


    /**
     *
     * @var integer
     */
    public $track_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->cn = '';
        $this->euid = '';
        $this->mid = '0';
        $this->orderSn = '';
        $this->orderTime = '0000-00-00 00:00:00';
        $this->orderChannel = '0';
        $this->isNewCustom = '0';
        $this->status = '';
        $this->goodsId = '0';
        $this->goodsName = '';
        $this->goodsTa = '0';
        $this->goodsPrice = '0.00';
        $this->totalPrice = '0.00';
        $this->commission = '0.00';
        $this->commissionType = '';
        $this->rate = '0.00';
        $this->goodsCate = '0';
        $this->goodsCateName = '';
        $this->createdAt = XDateTime::now();
        $this->updatedAt = '0000-00-00 00:00:00';
        $this->uid = '0';
        $this->trackId = '0';
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
            'cn' => 'cn',
            'euid' => 'euid',
            'mid' => 'mid',
            'order_sn' => 'orderSn',
            'order_time' => 'orderTime',
            'order_channel' => 'orderChannel',
            'is_new_custom' => 'isNewCustom',
            'status' => 'status',
            'goods_id' => 'goodsId',
            'goods_name' => 'goodsName',
            'goods_ta' => 'goodsTa',
            'goods_price' => 'goodsPrice',
            'total_price' => 'totalPrice',
            'commission' => 'commission',
            'commission_type' => 'commissionType',
            'rate' => 'rate',
            'goods_cate' => 'goodsCate',
            'goods_cate_name' => 'goodsCateName',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
            'uid' => 'uid',
            'goods_cn' => 'goodsCn',
            'goods_type' => 'goodsType',
            'days' => 'days',
            'track_id' => 'trackId',
        );
    }

    public function getSource()
    {
        return "firstp2p_adunion_deal";
    }
}