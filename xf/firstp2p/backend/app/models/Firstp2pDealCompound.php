<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealCompound extends ModelBaseNoTime
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
    public $lock_period;


    /**
     *
     * @var integer
     */
    public $redemption_period;


    /**
     *
     * @var integer
     */
    public $end_date;


    /**
     *
     * @var float
     */
    public $redemption_limit;


    /**
     *
     * @var float
     */
    public $rate_day;


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
        $this->lockPeriod = '0';
        $this->redemptionLimit = '1.00';
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
            'lock_period' => 'lockPeriod',
            'redemption_period' => 'redemptionPeriod',
            'end_date' => 'endDate',
            'redemption_limit' => 'redemptionLimit',
            'rate_day' => 'rateDay',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_compound";
    }
}