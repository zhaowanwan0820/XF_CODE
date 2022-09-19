<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pFund extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var float
     */
    public $income_min;


    /**
     *
     * @var float
     */
    public $income_max;


    /**
     *
     * @var string
     */
    public $income_intro;


    /**
     *
     * @var integer
     */
    public $repay_time;


    /**
     *
     * @var integer
     */
    public $repay_type;


    /**
     *
     * @var float
     */
    public $loan_money_min;


    /**
     *
     * @var string
     */
    public $fund_intro;


    /**
     *
     * @var string
     */
    public $security_intro;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $is_effect;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->repayType = '1';
        $this->loanMoneyMin = '10000.00';
        $this->status = '0';
        $this->isEffect = '1';
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
            'name' => 'name',
            'income_min' => 'incomeMin',
            'income_max' => 'incomeMax',
            'income_intro' => 'incomeIntro',
            'repay_time' => 'repayTime',
            'repay_type' => 'repayType',
            'loan_money_min' => 'loanMoneyMin',
            'fund_intro' => 'fundIntro',
            'security_intro' => 'securityIntro',
            'status' => 'status',
            'create_time' => 'createTime',
            'is_effect' => 'isEffect',
        );
    }

    public function getSource()
    {
        return "firstp2p_fund";
    }
}