<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserSta extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $dp_count;


    /**
     *
     * @var float
     */
    public $borrow_amount;


    /**
     *
     * @var float
     */
    public $repay_amount;


    /**
     *
     * @var float
     */
    public $need_repay_amount;


    /**
     *
     * @var float
     */
    public $need_manage_amount;


    /**
     *
     * @var float
     */
    public $avg_rate;


    /**
     *
     * @var float
     */
    public $avg_borrow_amount;


    /**
     *
     * @var integer
     */
    public $deal_count;


    /**
     *
     * @var integer
     */
    public $success_deal_count;


    /**
     *
     * @var integer
     */
    public $repay_deal_count;


    /**
     *
     * @var integer
     */
    public $tq_repay_deal_count;


    /**
     *
     * @var integer
     */
    public $zc_repay_deal_count;


    /**
     *
     * @var integer
     */
    public $wh_repay_deal_count;


    /**
     *
     * @var integer
     */
    public $yuqi_count;


    /**
     *
     * @var integer
     */
    public $yz_yuqi_count;


    /**
     *
     * @var float
     */
    public $yuqi_amount;


    /**
     *
     * @var float
     */
    public $yuqi_impose;


    /**
     *
     * @var float
     */
    public $load_earnings;


    /**
     *
     * @var float
     */
    public $load_tq_impose;


    /**
     *
     * @var float
     */
    public $load_yq_impose;


    /**
     *
     * @var float
     */
    public $load_avg_rate;


    /**
     *
     * @var integer
     */
    public $load_count;


    /**
     *
     * @var float
     */
    public $load_money;


    /**
     *
     * @var float
     */
    public $load_repay_money;


    /**
     *
     * @var float
     */
    public $load_wait_repay_money;


    /**
     *
     * @var integer
     */
    public $reback_load_count;


    /**
     *
     * @var integer
     */
    public $wait_reback_load_count;

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
            'user_id' => 'userId',
            'dp_count' => 'dpCount',
            'borrow_amount' => 'borrowAmount',
            'repay_amount' => 'repayAmount',
            'need_repay_amount' => 'needRepayAmount',
            'need_manage_amount' => 'needManageAmount',
            'avg_rate' => 'avgRate',
            'avg_borrow_amount' => 'avgBorrowAmount',
            'deal_count' => 'dealCount',
            'success_deal_count' => 'successDealCount',
            'repay_deal_count' => 'repayDealCount',
            'tq_repay_deal_count' => 'tqRepayDealCount',
            'zc_repay_deal_count' => 'zcRepayDealCount',
            'wh_repay_deal_count' => 'whRepayDealCount',
            'yuqi_count' => 'yuqiCount',
            'yz_yuqi_count' => 'yzYuqiCount',
            'yuqi_amount' => 'yuqiAmount',
            'yuqi_impose' => 'yuqiImpose',
            'load_earnings' => 'loadEarnings',
            'load_tq_impose' => 'loadTqImpose',
            'load_yq_impose' => 'loadYqImpose',
            'load_avg_rate' => 'loadAvgRate',
            'load_count' => 'loadCount',
            'load_money' => 'loadMoney',
            'load_repay_money' => 'loadRepayMoney',
            'load_wait_repay_money' => 'loadWaitRepayMoney',
            'reback_load_count' => 'rebackLoadCount',
            'wait_reback_load_count' => 'waitRebackLoadCount',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_sta";
    }
}