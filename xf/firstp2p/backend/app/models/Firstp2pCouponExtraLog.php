<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponExtraLog extends ModelBaseNoTime
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
    public $coupon_log_id;


    /**
     *
     * @var integer
     */
    public $coupon_extra_id;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var string
     */
    public $tags;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $deal_load_id;


    /**
     *
     * @var float
     */
    public $deal_load_money;


    /**
     *
     * @var integer
     */
    public $consume_user_id;


    /**
     *
     * @var string
     */
    public $consume_user_name;


    /**
     *
     * @var float
     */
    public $rebate_amount;


    /**
     *
     * @var float
     */
    public $rebate_ratio;


    /**
     *
     * @var float
     */
    public $rebate_ratio_amount;


    /**
     *
     * @var float
     */
    public $referer_rebate_amount;


    /**
     *
     * @var float
     */
    public $referer_rebate_ratio;


    /**
     *
     * @var float
     */
    public $referer_rebate_ratio_amount;


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

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->couponLogId = '0';
        $this->couponExtraId = '0';
        $this->tags = '0';
        $this->dealId = '0';
        $this->dealLoadId = '0';
        $this->dealLoadMoney = '0.00';
        $this->consumeUserId = '0';
        $this->consumeUserName = '';
        $this->rebateAmount = '0.00';
        $this->rebateRatio = '0.00000';
        $this->rebateRatioAmount = '0.00';
        $this->refererRebateAmount = '0.00';
        $this->refererRebateRatio = '0.00000';
        $this->refererRebateRatioAmount = '0.00';
        $this->createTime = '0';
        $this->updateTime = '0';
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
            'coupon_log_id' => 'couponLogId',
            'coupon_extra_id' => 'couponExtraId',
            'type' => 'type',
            'tags' => 'tags',
            'deal_id' => 'dealId',
            'deal_load_id' => 'dealLoadId',
            'deal_load_money' => 'dealLoadMoney',
            'consume_user_id' => 'consumeUserId',
            'consume_user_name' => 'consumeUserName',
            'rebate_amount' => 'rebateAmount',
            'rebate_ratio' => 'rebateRatio',
            'rebate_ratio_amount' => 'rebateRatioAmount',
            'referer_rebate_amount' => 'refererRebateAmount',
            'referer_rebate_ratio' => 'refererRebateRatio',
            'referer_rebate_ratio_amount' => 'refererRebateRatioAmount',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_extra_log";
    }
}