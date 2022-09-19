<?php
/**
 * 即付宝资金转账服务
 * User: jinhaidong
 * Date: 2015/8/10
 * Time: 16:24
 */

namespace core\service\jifu;

use core\service\DealService;
use core\dao\ThirdpartyOrderModel;
use libs\utils\Logger;


class JfTransferService {

    static $instance;
    public static function instance() {
        if(!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 回款转账处理
     * @param $user 用户对象
     * @param $dealId 标ID
     * @param $dealLoanId 投资记录ID
     * @param $repayMoney 回款金额
     * @return bool
     */
    public function repayTransferToJf($user,$dealId,$dealLoanId,$repayMoney) {
        $dealService = new DealService();
        //$isDealJf = $dealService->isDealJF(false,$dealId);
        //if($isDealJf) {
        //    Logger::info("repayTransferToJf user:".$user->id." dealId:".$dealId." dealLoanId:".$dealLoanId." repayMoney:".$repayMoney);

        $orderInfo = ThirdpartyOrderModel::instance()->getOrderByDealLoanId($dealLoanId);
        $orderId = $orderInfo['order_id'];
        if(empty($orderId)) {
            //Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $user->id,$dealId,$dealLoanId,$repayMoney,"即付订单ID缺失参数")));
            return true;
        }
        if(!$dealService->transferRepayJF($user,$repayMoney,$dealId,$orderId)) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $user->id,$dealId,$dealLoanId,$repayMoney,"即付回款转账失败")));
            return false;
        }

        //}
        return true;
    }
}
