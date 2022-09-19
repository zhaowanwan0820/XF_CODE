<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponLevelRebate extends ModelBaseNoTime
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
    public $level_id;


    /**
     *
     * @var string
     */
    public $prefix;


    /**
     *
     * @var float
     */
    public $fixed_days;


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
    public $agency_rebate_amount;


    /**
     *
     * @var float
     */
    public $agency_rebate_ratio;


    /**
     *
     * @var string
     */
    public $remark;


    /**
     *
     * @var integer
     */
    public $valid_begin;


    /**
     *
     * @var integer
     */
    public $valid_end;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealId = '0';
        $this->levelId = '1';
        $this->prefix = '';
        $this->fixedDays = '0.0000';
        $this->rebateAmount = '0.00';
        $this->rebateRatio = '0.00000';
        $this->refererRebateAmount = '0.00';
        $this->refererRebateRatio = '0.00000';
        $this->agencyRebateAmount = '0.00';
        $this->agencyRebateRatio = '0.00000';
        $this->remark = '';
        $this->validBegin = '0';
        $this->validEnd = '0';
        $this->isEffect = '1';
        $this->updateTime = '0';
        $this->createTime = '0';
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
            'level_id' => 'levelId',
            'prefix' => 'prefix',
            'fixed_days' => 'fixedDays',
            'rebate_amount' => 'rebateAmount',
            'rebate_ratio' => 'rebateRatio',
            'referer_rebate_amount' => 'refererRebateAmount',
            'referer_rebate_ratio' => 'refererRebateRatio',
            'agency_rebate_amount' => 'agencyRebateAmount',
            'agency_rebate_ratio' => 'agencyRebateRatio',
            'remark' => 'remark',
            'valid_begin' => 'validBegin',
            'valid_end' => 'validEnd',
            'is_effect' => 'isEffect',
            'update_time' => 'updateTime',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_level_rebate";
    }
}