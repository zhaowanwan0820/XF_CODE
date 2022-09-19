<?php
namespace core\service\user;

use core\dao\BaseModel;
use core\service\BaseService;
use core\dao\user\UserLoanRepayStatisticsModel;
use core\enum\UserLoanRepayStatisticsEnum;

/**
 * loan_repay 汇总到user_loan_repay 减少数据库压力
 * @author gengkuan
 * @date 2018-7-7 14:40:44
 */
class UserLoanRepayStatisticsService extends BaseService {

    /**
     * @param $uid
     * @param $money
     * @param $moneyTypes mixed 数组 or 字符串
     * @return mixed
     * @throws \Exception
     */
    public static function updateUserAssets($uid,array $moneyInfo) {
        $moneyTypes = array_keys($moneyInfo);

        $diff = array_diff($moneyTypes,UserLoanRepayStatisticsEnum::$moneyTypes);
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
        if(!$uid){
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid empty')));
            return array();
        }
        $res = UserLoanRepayStatisticsModel::instance()->getUserAssets($uid,$get_from_slave);
        if(!$res) {
            return UserLoanRepayStatisticsModel::instance()->initUserAssets($uid);
        }
        return $res->getRow();
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
