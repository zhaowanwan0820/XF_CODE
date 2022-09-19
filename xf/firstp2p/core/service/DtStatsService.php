<?php
namespace core\service;

use NCFGroup\Protos\Ptp\ResponseDtP2pStats;
use core\dao\DealLoanRepayModel;
use core\service\DealService;
/**
 * 多投宝统计p2p信息服务
 *
 * @author wangchuanlu
 * @date 2015-11-18
 */
class DtStatsService {

    /**
     *获取p2p统计信息
     * @param int $p2pDealId
     * @throws \Exception
     */
    public function getP2pStats($p2pDealId) {
        $deal_service = new DealService();
        $dealInfo = $deal_service->getDeal($p2pDealId, true, false);
        $response = new ResponseDtP2pStats();
        if (empty($dealInfo)) {//没查到
            return $response;
        }

        $dealLoanRepayModel = new DealLoanRepayModel();
        //投资标已收收益
        $repayEarnings = $dealLoanRepayModel->getPayedEarnMoneyByDealId($p2pDealId, DealLoanRepayModel::STATUS_ISPAYED);
        //投资标待收收益
        $norepayEarnings = $dealLoanRepayModel->getPayedEarnMoneyByDealId($p2pDealId, DealLoanRepayModel::STATUS_NOTPAYED);

//        //已付利息
//        $repayInterest = $dealLoanRepayModel->getTotalMoneyByTypeDealId($p2pDealId, array(DealLoanRepayModel::MONEY_INTREST,DealLoanRepayModel::MONEY_PREPAY_INTREST),DealLoanRepayModel::STATUS_ISPAYED);
//        //待付利息
//        $norepayInterest = $dealLoanRepayModel->getTotalMoneyByTypeDealId($p2pDealId, DealLoanRepayModel::MONEY_INTREST,DealLoanRepayModel::STATUS_NOTPAYED);
//        //已还本金
//        $repayPrincipal = $dealLoanRepayModel->getTotalMoneyByTypeDealId($p2pDealId, array(DealLoanRepayModel::MONEY_PRINCIPAL,DealLoanRepayModel::MONEY_PREPAY),DealLoanRepayModel::STATUS_ISPAYED);
//        //待赎回本金
//        $norepayPrincipal = $dealLoanRepayModel->getTotalMoneyByTypeDealId($p2pDealId, DealLoanRepayModel::MONEY_PRINCIPAL,DealLoanRepayModel::STATUS_NOTPAYED);

//         $now = time();//执行时间
//         $todayBeginTime = date('Ymd',get_gmtime());
//         // 今日还款本金
//        $dayRepayPrincipal = 0;
//        //今日还款信息
//        $sql = "SELECT * FROM (SELECT COALESCE(SUM(`money`),0) as dayRepayPrincipal ,FROM_UNIXTIME(`real_time`, %s) as cdate FROM firstp2p_deal_loan_repay WHERE deal_id = %d AND `type` IN (%s)  AND `status` = %d GROUP BY cdate ) t1 WHERE t1.cdate = %d";
//        $sql = sprintf($sql,"'%Y%m%d'",$p2pDealId,implode(',', array(DealLoanRepayModel::MONEY_PRINCIPAL,DealLoanRepayModel::MONEY_PREPAY)),DealLoanRepayModel::STATUS_ISPAYED,$todayBeginTime);
//        $todayRepayInfo = $dealLoanRepayModel->findBySql($sql, array(), true);
//        if(!empty($todayRepayInfo)) {
//            $dayRepayPrincipal = $todayRepayInfo['dayRepayPrincipal'];
//        }

//        $response->setRepayPrincipal($repayPrincipal==null?0:$repayPrincipal);
//        $response->setNorepayPrincipal($norepayPrincipal==null?0:$norepayPrincipal);
//        $response->setDayRepayPrincipal($dayRepayPrincipal==null?0:$dayRepayPrincipal);

        $response->setRepayInterest($repayInterest==null?0:$repayInterest);
        $response->setNorepayInterest($norepayInterest==null?0:$norepayInterest);
        $response->setRepayEarnings($repayEarnings==null?0:$repayEarnings);
        $response->setNorepayEarnings($norepayEarnings==null?0:$norepayEarnings);

        return $response;
    }

}