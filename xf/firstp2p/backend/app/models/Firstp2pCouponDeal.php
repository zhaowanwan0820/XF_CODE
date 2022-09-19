<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponDeal extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $pay_type;


    /**
     *
     * @var integer
     */
    public $pay_auto;


    /**
     *
     * @var integer
     */
    public $is_paid;


    /**
     *
     * @var integer
     */
    public $rebate_days;


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
    public $start_pay_time;


    /**
     *
     * @var integer
     */
    public $loantype;


    /**
     *
     * @var integer
     */
    public $repay_time;


    /**
     *
     * @var integer
     */
    public $deal_type;


    /**
     *
     * @var integer
     */
    public $deal_status;


    /**
     *
     * @var integer
     */
    public $is_rebate;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->payType = '0';
        $this->payAuto = '2';
        $this->isPaid = '0';
        $this->rebateDays = '0';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->startPayTime = '0';
        $this->loantype = '0';
        $this->repayTime = '0';
        $this->dealType = '0';
        $this->dealStatus = '0';
        $this->isRebate = '1';
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
            'deal_id' => 'dealId',
            'pay_type' => 'payType',
            'pay_auto' => 'payAuto',
            'is_paid' => 'isPaid',
            'rebate_days' => 'rebateDays',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'start_pay_time' => 'startPayTime',
            'loantype' => 'loantype',
            'repay_time' => 'repayTime',
            'deal_type' => 'dealType',
            'deal_status' => 'dealStatus',
            'is_rebate' => 'isRebate',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_deal";
    }
}