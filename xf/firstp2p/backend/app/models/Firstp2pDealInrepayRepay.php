<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealInrepayRepay extends ModelBaseNoTime
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
    public $impose;


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
     * @var float
     */
    public $principal;


    /**
     *
     * @var float
     */
    public $interest;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

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
            'repay_money' => 'repayMoney',
            'manage_money' => 'manageMoney',
            'impose' => 'impose',
            'repay_time' => 'repayTime',
            'true_repay_time' => 'trueRepayTime',
            'principal' => 'principal',
            'interest' => 'interest',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_inrepay_repay";
    }
}