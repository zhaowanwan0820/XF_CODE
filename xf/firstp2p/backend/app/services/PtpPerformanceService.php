<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;

use NCFGroup\Ptp\daos\UserDAO;
use NCFGroup\Ptp\daos\PerformanceDAO;

use NCFGroup\Protos\Ptp\RequestUser;
use NCFGroup\Protos\Ptp\ResponsePerformanceIndex;
use NCFGroup\Protos\Ptp\ResponsePerformanceDaysStat;

/**
 * PtpPerformanceService
 * 绩效统计相关
 *
 * @uses ServiceBase
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Tobias Schlitt <toby@php.net>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class PtpPerformanceService extends ServiceBase
{
    /**
     * getSummary
     * 获取绩效首页信息
     *
     * @param RequestUser $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponsePerformanceIndex
     */
    public static function getSummary(RequestUser $request)
    {
        $cfpId = $request->getCfpId();
        $rs = PerformanceDAO::getSummary($cfpId);
        $response = new ResponsePerformanceIndex();
        if ($rs) {
            $response->setTotalMoney($rs['totalMoney']);
            $response->setTotalUsers($rs['totalUsers']);
            $response->setAvgTotalMoney($rs['avgTotalMoney']);
        }
        // TODO 今日投资人数与最近60天投资情况
        $profits = PerformanceDAO::getRecentProfitsByUser($cfpId);
        $response->setProfitData($profits);
        $todayArr = end($profits);
        $response->setTodayProfit($todayArr[1]);
        return $response;
    }

    /**
     * getDaysInvestStat
     * 每日投资客户数等统计信息
     *
     * @param RequestUser $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponsePerformanceDaysStat
     */
    public static function getDaysInvestStat(RequestUser $request)
    {
        $cfpId = $request->getCfpId();
        $investDays = PerformanceDAO::investDaysStat($cfpId);
        $response = new ResponsePerformanceDaysStat();
        $response->setDayInvestNumDetail($investDays);
        $todayArr = end($investDays);
        $response->setTodayInvest(intval($todayArr[1]));
        $userIds = UserDAO::getCustomerIdsByUserId($cfpId);
        $response->setTotalCustomers(count($userIds));
        $analyses = UserDAO::getInvestAnalyse($cfpId, $userIds, 0);
        $response->setMoneyInvestDetail($analyses);

        return $response;
    }
}
