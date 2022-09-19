<?php
namespace core\service;

use core\dao\BaseModel;
use core\dao\UserLoanRepayStatisticsModel;

/**
 * loan_repay 汇总到user_loan_repay 减少数据库压力
 * @author jinhaidong
 * @date 2016-2-26 11:40:44
 */
class UserLoanRepayStatisticsService extends BaseService {
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

    /**
     * @param $uid
     * @param $money
     * @param $moneyTypes mixed 数组 or 字符串
     * @return mixed
     * @throws \Exception
     */
    public static function updateUserAssets($uid,array $moneyInfo) {
        $moneyTypes = array_keys($moneyInfo);

        $diff = array_diff($moneyTypes,self::$moneyTypes);
        if($diff) {
            throw new \Exception("user_loan_repay_statistics 非法的字段类型:".implode(",",$moneyTypes));
        }
        return UserLoanRepayStatisticsModel::instance()->updateUserAssets($uid,$moneyInfo);
    }

    /**
     * 获取用户资产数据loan
     * @param $uid
     * @return mixed
     */
    public static function getUserAssets($uid,$get_from_slave=false) {
        $res = UserLoanRepayStatisticsModel::instance()->getUserAssets($uid,$get_from_slave);
        if(!$res) {
            return UserLoanRepayStatisticsModel::instance()->initUserAssets($uid);
        }
        $assets = $res->getRow();
        $assets['cg_norepay_principal'] = 0;
        $assets['cg_norepay_earnings'] = 0;
        $assets['cg_total_earnings'] = 0;
        return $assets;
    }

    /**
     * 同步用户的loan_repay 表数据到 新的表
     * @param $uid
     * @param $data
     */
    public static function syncUserAssets($uid,$data) {
        $res = UserLoanRepayStatisticsModel::instance()->isExistsUser($uid);
        if($res) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'user summary has been synchronized')));
            return true;
        }
        try{
            $syncRes = UserLoanRepayStatisticsModel::instance()->syncUserAssets($uid,$data);
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'user summary sync success ')));
        }catch (\Exception $ex) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'user summary sync error '.$ex->getMessage())));
            return false;
        }
        return $syncRes;
    }

    /**
     * 初始化注册用户的资产
     * @param $uid
     */
    public static function initRegUserAssets($uid) {
        $data = array(
            'load_repay_money' => 0,
            'load_earnings' => 0,
            'load_tq_impose' => 0,
            'load_yq_impose' => 0,
            'norepay_principal' => 0,
            'norepay_interest' => 0,
            'js_norepay_principal' => 0,
            'js_norepay_earnings' => 0,
            'js_total_earnings' => 0,
            'cg_norepay_principal' => 0,
            'cg_norepay_earnings' => 0,
            'cg_total_earnings' => 0,
        );
        return UserLoanRepayStatisticsModel::instance()->saveUserAssets($uid,$data);
    }
}