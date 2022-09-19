<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponLog extends ModelBaseNoTime
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
    public $type;


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
    public $deal_type;


    /**
     *
     * @var integer
     */
    public $deal_repay_time;


    /**
     *
     * @var integer
     */
    public $rebate_days;


    /**
     *
     * @var integer
     */
    public $rebate_add_time;


    /**
     *
     * @var integer
     */
    public $rebate_days_update_time;


    /**
     *
     * @var integer
     */
    public $deal_repay_days;


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
     * @var integer
     */
    public $refer_user_id;


    /**
     *
     * @var string
     */
    public $refer_user_name;


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
     * @var float
     */
    public $referer_rebate_ratio_factor;


    /**
     *
     * @var integer
     */
    public $deal_status;


    /**
     *
     * @var integer
     */
    public $pay_status;


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
    public $add_type;


    /**
     *
     * @var integer
     */
    public $admin_id;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $update_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->type = '0';
        $this->dealId = '0';
        $this->dealLoadId = '0';
        $this->dealType = '0';
        $this->dealRepayTime = '0';
        $this->rebateDays = '0';
        $this->rebateAddTime = '0';
        $this->rebateDaysUpdateTime = '0';
        $this->dealRepayDays = '0';
        $this->consumeUserId = '0';
        $this->consumeUserName = '';
        $this->referUserId = '0';
        $this->referUserName = '';
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
        $this->refererRebateRatioFactor = '1.0000';
        $this->dealStatus = '0';
        $this->payStatus = '0';
        $this->createTime = '0';
        $this->payTime = '0';
        $this->addType = '1';
        $this->adminId = '0';
        $this->isDelete = '0';
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
            'type' => 'type',
            'deal_id' => 'dealId',
            'deal_load_id' => 'dealLoadId',
            'deal_type' => 'dealType',
            'deal_repay_time' => 'dealRepayTime',
            'rebate_days' => 'rebateDays',
            'rebate_add_time' => 'rebateAddTime',
            'rebate_days_update_time' => 'rebateDaysUpdateTime',
            'deal_repay_days' => 'dealRepayDays',
            'consume_user_id' => 'consumeUserId',
            'consume_user_name' => 'consumeUserName',
            'refer_user_id' => 'referUserId',
            'refer_user_name' => 'referUserName',
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
            'referer_rebate_ratio_factor' => 'refererRebateRatioFactor',
            'deal_status' => 'dealStatus',
            'pay_status' => 'payStatus',
            'create_time' => 'createTime',
            'pay_time' => 'payTime',
            'add_type' => 'addType',
            'admin_id' => 'adminId',
            'is_delete' => 'isDelete',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_log";
    }
}