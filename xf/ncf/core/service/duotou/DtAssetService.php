<?php
namespace core\service\duotou;

use libs\utils\Logger;
use core\service\user\UserLoanRepayStatisticsService;
use core\service\duotou\DuotouService;


/**
 * 多投宝资产统计
 *
 * @author jinhaidong
 * @date 2015-01-18
 */
class DtAssetService extends DuotouService{

    public function getDtAsset($userId) {
        // 多投宝用户资金汇总
        $data = array(
            'remainMoney' => 0, // 多投宝余额
            'canRedemMoney' => 0, // 可赎回金额
            'totalLoanMoney' => 0, // 总投资额
            'totalInterest' => 0, // 总收益
        );
        if(!is_duotou_inner_user()) {
            return $data;
        }
        $request = array(
            'userId' => $userId,
        );
        
        $response = self::callByObject(array('NCFGroup\Duotou\Services\UserStats','getUserDuotouInfo',$request));
        if(!$response) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $userId, "fail "." rpc(getUserDuotouInfo)调用失败")));
        }

        $userAsset = UserLoanRepayStatisticsService::getUserAssets($userId);
        $response['data']['remainMoney'] = $userAsset['dt_norepay_principal']; // 待收本金
        $response['data']['totalInterest'] = $userAsset['dt_repay_interest']; //已赚利息
        $response['data']['totalLoanMoney'] = $userAsset['dt_load_money'];//总投资额
        return $response['data'] ? $response['data'] : $data;
    }

}