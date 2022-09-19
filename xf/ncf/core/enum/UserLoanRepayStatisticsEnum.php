<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserLoanRepayStatisticsEnum extends AbstractEnum
{
    const LOAD_REPAY_MONEY  = 'load_repay_money';
    const LOAD_EARNINGS     = 'load_earnings';
    const LOAD_TQ_IMPOSE    = 'load_tq_impose';
    const LOAD_YQ_IMPOSE    = 'load_yq_impose';
    const NOREPAY_PRINCIPAL = 'norepay_principal'; // 普通标待收本金
    const NOREPAY_INTEREST  = 'norepay_interest';  // 待还利息
    const JS_NOREPAY_PRINCIPAL  = 'js_norepay_principal'; //交易所待回本金
    const JS_NOREPAY_EARNINGS   = 'js_norepay_earnings'; //交易所待收收益
    const JS_TOTAL_EARNINGS     = 'js_total_earnings';  //交易所累计收益
    const DT_LOAD_MONEY     = 'dt_load_money';  // 智多鑫的投资底层资产金额
    const DT_NOREPAY_PRINCIPAL  = 'dt_norepay_principal';  // 智多鑫的已投金额；智多鑫的待投本金=投资总额-投资底层资产金额

    const CG_NOREPAY_PRINCIPAL  = 'cg_norepay_principal'; //存管网贷待回本金
    const CG_NOREPAY_EARNINGS   = 'cg_norepay_earnings'; //存管网贷待收收益
    const CG_TOTAL_EARNINGS     = 'cg_total_earnings';  //存管网贷累计收益

    public static $moneyTypes = array(
        self::LOAD_REPAY_MONEY,
        self::LOAD_EARNINGS,
        self::LOAD_TQ_IMPOSE,
        self::LOAD_YQ_IMPOSE,
        self::NOREPAY_INTEREST,
        self::NOREPAY_PRINCIPAL,
        self::JS_NOREPAY_PRINCIPAL,
        self::JS_NOREPAY_EARNINGS,
        self::JS_TOTAL_EARNINGS,

        self::CG_NOREPAY_PRINCIPAL,
        self::CG_NOREPAY_EARNINGS,
        self::CG_TOTAL_EARNINGS,

        self::DT_LOAD_MONEY,
        self::DT_NOREPAY_PRINCIPAL,

    );


}
