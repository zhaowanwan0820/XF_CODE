<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pCouponExtra extends ModelBaseNoTime
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
    public $source_type;


    /**
     *
     * @var string
     */
    public $tags;


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
    public $is_effect;


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
        $this->dealId = '0';
        $this->tags = '0';
        $this->rebateAmount = '0.00';
        $this->rebateRatio = '0.00000';
        $this->refererRebateAmount = '0.00';
        $this->refererRebateRatio = '0.00000';
        $this->agencyRebateAmount = '0.00';
        $this->agencyRebateRatio = '0.00000';
        $this->remark = '';
        $this->isEffect = '1';
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
            'deal_id' => 'dealId',
            'source_type' => 'sourceType',
            'tags' => 'tags',
            'rebate_amount' => 'rebateAmount',
            'rebate_ratio' => 'rebateRatio',
            'referer_rebate_amount' => 'refererRebateAmount',
            'referer_rebate_ratio' => 'refererRebateRatio',
            'agency_rebate_amount' => 'agencyRebateAmount',
            'agency_rebate_ratio' => 'agencyRebateRatio',
            'remark' => 'remark',
            'is_effect' => 'isEffect',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_coupon_extra";
    }
}