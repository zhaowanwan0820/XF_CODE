<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponPayLog extends ModelBaseNoTime
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
    public $deal_load_id;


    /**
     *
     * @var integer
     */
    public $pay_day;


    /**
     *
     * @var integer
     */
    public $coupon_log_id;


    /**
     *
     * @var integer
     */
    public $rebate_days;


    /**
     *
     * @var integer
     */
    public $consume_user_id;


    /**
     *
     * @var integer
     */
    public $refer_user_id;


    /**
     *
     * @var integer
     */
    public $agency_user_id;


    /**
     *
     * @var float
     */
    public $deal_load_money;


    /**
     *
     * @var string
     */
    public $short_alias;


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
     * @var float
     */
    public $agency_rebate_amount;


    /**
     *
     * @var float
     */
    public $agency_rebate_ratio;


    /**
     *
     * @var float
     */
    public $agency_rebate_ratio_amount;


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
    public $pay_time;


    /**
     *
     * @var integer
     */
    public $pay_type;


    /**
     *
     * @var integer
     */
    public $admin_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealId = '0';
        $this->dealLoadId = '0';
        $this->payDay = '0';
        $this->couponLogId = '0';
        $this->rebateDays = '0';
        $this->consumeUserId = '0';
        $this->referUserId = '0';
        $this->agencyUserId = '0';
        $this->dealLoadMoney = '0.00';
        $this->shortAlias = '';
        $this->rebateAmount = '0.00';
        $this->rebateRatio = '0.00000';
        $this->rebateRatioAmount = '0.00';
        $this->refererRebateAmount = '0.00';
        $this->refererRebateRatio = '0.00000';
        $this->refererRebateRatioAmount = '0.00';
        $this->agencyRebateAmount = '0.00';
        $this->agencyRebateRatio = '0.00000';
        $this->agencyRebateRatioAmount = '0.00';
        $this->createTime = '0';
        $this->updateTime = '0';
        $this->payTime = '0';
        $this->payType = '1';
        $this->adminId = '0';
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
            'deal_load_id' => 'dealLoadId',
            'pay_day' => 'payDay',
            'coupon_log_id' => 'couponLogId',
            'rebate_days' => 'rebateDays',
            'consume_user_id' => 'consumeUserId',
            'refer_user_id' => 'referUserId',
            'agency_user_id' => 'agencyUserId',
            'deal_load_money' => 'dealLoadMoney',
            'short_alias' => 'shortAlias',
            'rebate_amount' => 'rebateAmount',
            'rebate_ratio' => 'rebateRatio',
            'rebate_ratio_amount' => 'rebateRatioAmount',
            'referer_rebate_amount' => 'refererRebateAmount',
            'referer_rebate_ratio' => 'refererRebateRatio',
            'referer_rebate_ratio_amount' => 'refererRebateRatioAmount',
            'agency_rebate_amount' => 'agencyRebateAmount',
            'agency_rebate_ratio' => 'agencyRebateRatio',
            'agency_rebate_ratio_amount' => 'agencyRebateRatioAmount',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'pay_time' => 'payTime',
            'pay_type' => 'payType',
            'admin_id' => 'adminId',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_pay_log";
    }
}