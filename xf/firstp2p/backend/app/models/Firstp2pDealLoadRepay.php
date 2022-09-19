<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealLoadRepay extends ModelBaseNoTime
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
    public $user_id;


    /**
     *
     * @var float
     */
    public $self_money;


    /**
     *
     * @var float
     */
    public $repay_money;


    /**
     *
     * @var float
     */
    public $manage_money;


    /**
     *
     * @var float
     */
    public $impose_money;


    /**
     *
     * @var integer
     */
    public $repay_time;


    /**
     *
     * @var integer
     */
    public $true_repay_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $is_site_repay;


    /**
     *
     * @var integer
     */
    public $deal_load_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealLoadId = '0';
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
            'user_id' => 'userId',
            'self_money' => 'selfMoney',
            'repay_money' => 'repayMoney',
            'manage_money' => 'manageMoney',
            'impose_money' => 'imposeMoney',
            'repay_time' => 'repayTime',
            'true_repay_time' => 'trueRepayTime',
            'status' => 'status',
            'is_site_repay' => 'isSiteRepay',
            'deal_load_id' => 'dealLoadId',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_load_repay";
    }
}