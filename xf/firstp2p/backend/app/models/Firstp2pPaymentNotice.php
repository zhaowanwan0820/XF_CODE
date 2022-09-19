<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPaymentNotice extends ModelBaseNoTime
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
    public $notice_sn;


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
    public $is_paid;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $payment_id;


    /**
     *
     * @var string
     */
    public $memo;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $fee;


    /**
     *
     * @var string
     */
    public $outer_notice_sn;


    /**
     *
     * @var integer
     */
    public $is_platform_fee_charged;


    /**
     *
     * @var integer
     */
    public $platform;


    /**
     *
     * @var integer
     */
    public $amount_limit;


    /**
     *
     * @var integer
     */
    public $is_fast_pay;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $site_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->isPaid = '0';
        $this->fee = '0.0000';
        $this->isPlatformFeeCharged = '0';
        $this->platform = '0';
        $this->amountLimit = '0';
        $this->isFastPay = '0';
        $this->updateTime = '0';
        $this->siteId = '0';
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
            'notice_sn' => 'noticeSn',
            'create_time' => 'createTime',
            'pay_time' => 'payTime',
            'order_id' => 'orderId',
            'is_paid' => 'isPaid',
            'user_id' => 'userId',
            'payment_id' => 'paymentId',
            'memo' => 'memo',
            'money' => 'money',
            'fee' => 'fee',
            'outer_notice_sn' => 'outerNoticeSn',
            'is_platform_fee_charged' => 'isPlatformFeeCharged',
            'platform' => 'platform',
            'amount_limit' => 'amountLimit',
            'is_fast_pay' => 'isFastPay',
            'update_time' => 'updateTime',
            'site_id' => 'siteId',
        );
    }

    public function getSource()
    {
        return "firstp2p_payment_notice";
    }
}