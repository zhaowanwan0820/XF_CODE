<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserAutobidCopy extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var float
     */
    public $fixed_amount;


    /**
     *
     * @var float
     */
    public $min_rate;


    /**
     *
     * @var float
     */
    public $max_rate;


    /**
     *
     * @var integer
     */
    public $min_period;


    /**
     *
     * @var integer
     */
    public $max_period;


    /**
     *
     * @var integer
     */
    public $min_level;


    /**
     *
     * @var integer
     */
    public $max_level;


    /**
     *
     * @var float
     */
    public $retain_amount;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $last_bid_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->userId = '0';
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
            'user_id' => 'userId',
            'fixed_amount' => 'fixedAmount',
            'min_rate' => 'minRate',
            'max_rate' => 'maxRate',
            'min_period' => 'minPeriod',
            'max_period' => 'maxPeriod',
            'min_level' => 'minLevel',
            'max_level' => 'maxLevel',
            'retain_amount' => 'retainAmount',
            'is_effect' => 'isEffect',
            'last_bid_time' => 'lastBidTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_autobid_copy";
    }
}