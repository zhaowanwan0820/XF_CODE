<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pReport extends ModelBaseNoTime
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
    public $prevTerm;


    /**
     *
     * @var string
     */
    public $term;


    /**
     *
     * @var string
     */
    public $nextTerm;


    /**
     *
     * @var float
     */
    public $p2p_begining_balance;


    /**
     *
     * @var float
     */
    public $pay_begining_balance;


    /**
     *
     * @var float
     */
    public $p2p_day_balance;


    /**
     *
     * @var float
     */
    public $pay_day_balance;


    /**
     *
     * @var float
     */
    public $p2p_endding_balance;


    /**
     *
     * @var float
     */
    public $pay_endding_balance;


    /**
     *
     * @var float
     */
    public $p2p_online_charge_success_balance;


    /**
     *
     * @var float
     */
    public $pay_online_charge_success_balance;


    /**
     *
     * @var float
     */
    public $p2p_online_charge_fail_balance;


    /**
     *
     * @var float
     */
    public $pay_online_charge_fail_balance;


    /**
     *
     * @var float
     */
    public $p2p_online_charge_inprocess_balance;


    /**
     *
     * @var float
     */
    public $pay_online_charge_inprocess_balance;


    /**
     *
     * @var float
     */
    public $p2p_online_charge_unrequest_balance;


    /**
     *
     * @var float
     */
    public $p2p_online_brower_charge_balance;


    /**
     *
     * @var float
     */
    public $p2p_offline_brower_charge_balance;


    /**
     *
     * @var float
     */
    public $pay_offline_brower_charge_balance;


    /**
     *
     * @var float
     */
    public $p2p_offline_charge_balance;


    /**
     *
     * @var float
     */
    public $pay_offline_charge_balance;


    /**
     *
     * @var float
     */
    public $p2p_offline_withdraw_refund_balance;


    /**
     *
     * @var float
     */
    public $p2p_offline_system_fix_balance;


    /**
     *
     * @var float
     */
    public $p2p_withdraw_failed_balance;


    /**
     *
     * @var float
     */
    public $pay_withdraw_failed_balance;


    /**
     *
     * @var float
     */
    public $p2p_withdraw_success_balance;


    /**
     *
     * @var float
     */
    public $pay_withdraw_success_balance;


    /**
     *
     * @var float
     */
    public $p2p_withdraw_inprocess_balance;


    /**
     *
     * @var float
     */
    public $pay_withdraw_inprocess_balance;


    /**
     *
     * @var float
     */
    public $p2p_withdraw_account_audit_balance;


    /**
     *
     * @var float
     */
    public $p2p_withdraw_operation_audit_balance;


    /**
     *
     * @var float
     */
    public $p2p_withdraw_frozen_balance;


    /**
     *
     * @var float
     */
    public $pay_withdraw_frozen_balance;


    /**
     *
     * @var float
     */
    public $p2p_term_balance;


    /**
     *
     * @var float
     */
    public $charge_without_trade;


    /**
     *
     * @var float
     */
    public $deal_frozen;


    /**
     *
     * @var float
     */
    public $deal_frozen_unaudit;


    /**
     *
     * @var float
     */
    public $pre_deal;


    /**
     *
     * @var float
     */
    public $deal_repayed;


    /**
     *
     * @var string
     */
    public $memo;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->term = '';
        $this->p2pBeginingBalance = '0.00';
        $this->payBeginingBalance = '0.00';
        $this->p2pDayBalance = '0.00';
        $this->payDayBalance = '0.00';
        $this->p2pEnddingBalance = '0.00';
        $this->payEnddingBalance = '0.00';
        $this->p2pOnlineChargeSuccessBalance = '0.00';
        $this->payOnlineChargeSuccessBalance = '0.00';
        $this->p2pOnlineChargeFailBalance = '0.00';
        $this->payOnlineChargeFailBalance = '0.00';
        $this->p2pOnlineChargeInprocessBalance = '0.00';
        $this->payOnlineChargeInprocessBalance = '0.00';
        $this->p2pOnlineChargeUnrequestBalance = '0.00';
        $this->p2pOnlineBrowerChargeBalance = '0.00';
        $this->p2pOfflineBrowerChargeBalance = '0.00';
        $this->payOfflineBrowerChargeBalance = '0.00';
        $this->p2pOfflineChargeBalance = '0.00';
        $this->payOfflineChargeBalance = '0.00';
        $this->p2pOfflineWithdrawRefundBalance = '0.00';
        $this->p2pOfflineSystemFixBalance = '0.00';
        $this->p2pWithdrawFailedBalance = '0.00';
        $this->payWithdrawFailedBalance = '0.00';
        $this->p2pWithdrawSuccessBalance = '0.00';
        $this->payWithdrawSuccessBalance = '0.00';
        $this->p2pWithdrawInprocessBalance = '0.00';
        $this->payWithdrawInprocessBalance = '0.00';
        $this->p2pWithdrawAccountAuditBalance = '0.00';
        $this->p2pWithdrawOperationAuditBalance = '0.00';
        $this->p2pWithdrawFrozenBalance = '0.00';
        $this->payWithdrawFrozenBalance = '0.00';
        $this->p2pTermBalance = '0.00';
        $this->chargeWithoutTrade = '0.00';
        $this->dealFrozen = '0.00';
        $this->dealFrozenUnaudit = '0.00';
        $this->preDeal = '0.00';
        $this->dealRepayed = '0.00';
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
            'prevTerm' => 'prevTerm',
            'term' => 'term',
            'nextTerm' => 'nextTerm',
            'p2p_begining_balance' => 'p2pBeginingBalance',
            'pay_begining_balance' => 'payBeginingBalance',
            'p2p_day_balance' => 'p2pDayBalance',
            'pay_day_balance' => 'payDayBalance',
            'p2p_endding_balance' => 'p2pEnddingBalance',
            'pay_endding_balance' => 'payEnddingBalance',
            'p2p_online_charge_success_balance' => 'p2pOnlineChargeSuccessBalance',
            'pay_online_charge_success_balance' => 'payOnlineChargeSuccessBalance',
            'p2p_online_charge_fail_balance' => 'p2pOnlineChargeFailBalance',
            'pay_online_charge_fail_balance' => 'payOnlineChargeFailBalance',
            'p2p_online_charge_inprocess_balance' => 'p2pOnlineChargeInprocessBalance',
            'pay_online_charge_inprocess_balance' => 'payOnlineChargeInprocessBalance',
            'p2p_online_charge_unrequest_balance' => 'p2pOnlineChargeUnrequestBalance',
            'p2p_online_brower_charge_balance' => 'p2pOnlineBrowerChargeBalance',
            'p2p_offline_brower_charge_balance' => 'p2pOfflineBrowerChargeBalance',
            'pay_offline_brower_charge_balance' => 'payOfflineBrowerChargeBalance',
            'p2p_offline_charge_balance' => 'p2pOfflineChargeBalance',
            'pay_offline_charge_balance' => 'payOfflineChargeBalance',
            'p2p_offline_withdraw_refund_balance' => 'p2pOfflineWithdrawRefundBalance',
            'p2p_offline_system_fix_balance' => 'p2pOfflineSystemFixBalance',
            'p2p_withdraw_failed_balance' => 'p2pWithdrawFailedBalance',
            'pay_withdraw_failed_balance' => 'payWithdrawFailedBalance',
            'p2p_withdraw_success_balance' => 'p2pWithdrawSuccessBalance',
            'pay_withdraw_success_balance' => 'payWithdrawSuccessBalance',
            'p2p_withdraw_inprocess_balance' => 'p2pWithdrawInprocessBalance',
            'pay_withdraw_inprocess_balance' => 'payWithdrawInprocessBalance',
            'p2p_withdraw_account_audit_balance' => 'p2pWithdrawAccountAuditBalance',
            'p2p_withdraw_operation_audit_balance' => 'p2pWithdrawOperationAuditBalance',
            'p2p_withdraw_frozen_balance' => 'p2pWithdrawFrozenBalance',
            'pay_withdraw_frozen_balance' => 'payWithdrawFrozenBalance',
            'p2p_term_balance' => 'p2pTermBalance',
            'charge_without_trade' => 'chargeWithoutTrade',
            'deal_frozen' => 'dealFrozen',
            'deal_frozen_unaudit' => 'dealFrozenUnaudit',
            'pre_deal' => 'preDeal',
            'deal_repayed' => 'dealRepayed',
            'memo' => 'memo',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_report";
    }
}