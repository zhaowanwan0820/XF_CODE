<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Library\DateTimeLib;
use NCFGroup\Ptp\models\Firstp2pDeal;
use NCFGroup\Ptp\models\Firstp2pDealLoad;

/**
 * Performance
 * 绩效相关
 *
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Tobias Schlitt <toby@php.net>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class PerformanceDAO
{
    /**
     * getSummary
     * 获取自己绩效统计基本信息
     * SELECT SUM(dl.money) totalMoney, SUM(DISTINCT(dl.user_id)) totalUsers, SUM(dl.money * IF(d.loantype = 5, 1, 30) * d.repay_time / 360) avgTotalMoney FROM firstp2p_deal_load dl LEFT JOIN firstp2p_deal d ON dl.deal_id = d.id AND from_unixtime(dl.create_time, '%Y') = '2015' AND dl.user_id IN(:uids:);
     *
     * @param mixed $cfpId
     * @static
     * @access public
     * @return array(
         totalMoney - 今年投资金额
         totalUsers - 今年投资人数
         avgTotalMoney - 年化总额
     * )
     */
    public static function getSummary($cfpId)
    {
        $userIds = UserDAO::getCustomerIdsByUserId($cfpId);
        $columns = array(
            'SUM(dl.money) totalMoney',
            //'COUNT(DISTINCT(dl.userId)) totalUsers', // 投资人数
            'COUNT(DISTINCT(dl.id)) totalUsers', // 投资人次
            'SUM(dl.money * IF(d.loantype = 5, 1, 30) * d.repayTime / 360) avgTotalMoney'
        );
        $builder = getDI()->getModelsManager()->createBuilder();
        $builder->from(array('dl' => 'NCFGroup\Ptp\models\Firstp2pDealLoad'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'cl.dealLoadId = dl.id', 'cl')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'dl.dealId=d.id', 'd')
            ->columns(implode(', ', $columns))
            ->where('cl.referUserId = :cfpId: AND from_unixtime((dl.createTime + '.DateTimeLib::PTP_DB_STEP_TIME.'), "%Y") = :year:', array('cfpId' => $cfpId, 'year' => date('Y')))
            ->inWhere('dl.userId', empty($userIds) ? array(0) : $userIds);
        $ret = $builder->getQuery()->execute();
        if ($ret) {
            $result = array_pop($ret->toArray());
            if ($result['totalUsers'] === null) {
                $result['totalMoney'] = 0;
                $result['totalUsers'] = 0;
                $result['avgTotalMoney'] = 0;
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * getRecentProfitsByUser
     * 统计理财师最近60天的佣金情况
     * 通知贷的已返佣金在 Firstp2pCouponPayLog
     * 非通知贷的已返、未返佣金在 Firstp2pCouponLog
     *
     * @param mixed $cfpId
     * @static
     * @access public
     * @return void
     */
    public static function getRecentProfitsByUser($cfpId)
    {
        $maxTime = strtotime(date('Y-m-d'));
        $minTime = $maxTime - 86400 * 60; // 统计最近60天的，包括最后一天，总共61天
        $minDay = date('Y-m-d', $minTime);
        $profits = self::initKeyValDaysArr($minTime, $maxTime);
        $columns = array(
            'from_unixtime((cl.createTime + '.DateTimeLib::PTP_DB_STEP_TIME.'), "%Y-%m-%d") dayKey',
            'SUM(cl.refererRebateRatioAmount + cl.refererRebateAmount) totalMoney',
        );
        // 非通知贷相关
        // 对于未返的要去除 dealStatus == 2
        $builder = getDI()->getModelsManager()->createBuilder()
            ->from(array('cl' => 'NCFGroup\Ptp\models\Firstp2pCouponLog'))
            ->columns(implode(', ', $columns))
            ->where('cl.referUserId=:userId: AND cl.type IN(1,2) AND cl.dealType=0', array('userId' => $cfpId))
            ->andWhere('(cl.createTime + '.DateTimeLib::PTP_DB_STEP_TIME.') >= :minTime:', array('minTime' => $minTime))
            ->andWhere('cl.payStatus IN(1,2) OR (cl.payStatus IN(0,3,4) AND cl.dealStatus != 2)')
            ->groupBy('dayKey')
            ->orderBy('cl.createTime');
        $ret = $builder->getQuery()->execute();
        foreach ($ret as $r) {
            if (isset($profits[$r->dayKey])) {
                $profits[$r->dayKey][1] = strval($profits[$r->dayKey][1] + $r->totalMoney);
            }
        }

        // 通知贷相关
        $builder = getDI()->getModelsManager()->createBuilder()
            ->from(array('cl' => 'NCFGroup\Ptp\models\Firstp2pCouponPayLog'))
            ->columns(implode(', ', $columns))
            ->where('cl.referUserId=:userId:', array('userId' => $cfpId))
            ->andWhere('(cl.createTime + '.DateTimeLib::PTP_DB_STEP_TIME.') >= :minTime:', array('minTime' => $minTime))
            ->groupBy('dayKey')
            ->orderBy('cl.createTime');
        $ret = $builder->getQuery()->execute();
        foreach ($ret as $r) {
            if (isset($profits[$r->dayKey])) {
                $profits[$r->dayKey][1] = strval($profits[$r->dayKey][1] + $r->totalMoney);
            }
        }
        return array_values($profits);
    }

    /**
     * initKeyValDaysArr
     * 初始化区间段数组
     *
     * @param mixed $minTime
     * @param mixed $maxTime
     * @static
     * @access private
     * @return void
     */
    private static function initKeyValDaysArr($minTime, $maxTime)
    {
        $ret = array();
        $tmpTime = $minTime;
        while ($tmpTime <= $maxTime) {
            $dayKey = date('Y-m-d', $tmpTime);
            $ret[$dayKey] = array($dayKey, '0');
            $tmpTime += 86400;
        }
        return $ret;
    }

    /**
     * investDaysStat
     * 统计最近每天投资客户数
     *
     * @param mixed $cfpId
     * @static
     * @access public
     * @return void
     */
    public static function investDaysStat($cfpId)
    {
        $maxTime = strtotime(date('Y-m-d'));
        $minTime = $maxTime - 86400 * 60; // 统计最近60天的，包括最后一天，总共61天
        $minDay = date('Y-m-d', $minTime);
        $investDays = self::initKeyValDaysArr($minTime, $maxTime);
        $columns = array(
            'from_unixtime(cl.createTime +'.DateTimeLib::PTP_DB_STEP_TIME.', "%Y-%m-%d") dayKey',
            //'COUNT(cl.id) totalInvests',
            'COUNT( DISTINCT(cl.consumeUserId) ) totalInvests', // 投资人数
        );
        $builder = getDI()->getModelsManager()->createBuilder()
            ->from(array('cl' => 'NCFGroup\Ptp\models\Firstp2pCouponLog'))
            ->columns(implode(', ', $columns))
            ->where('cl.referUserId=:userId: AND cl.type IN(1,2)', array('userId' => $cfpId))
            ->andWhere('(cl.createTime + '.DateTimeLib::PTP_DB_STEP_TIME.') >= :minTime:', array('minTime' => $minTime))
            ->groupBy('dayKey')
            ->orderBy('cl.createTime');
        $ret = $builder->getQuery()->execute();
        foreach ($ret as $r) {
            if (isset($investDays[$r->dayKey])) {
                $investDays[$r->dayKey][1] = strval($investDays[$r->dayKey][1] + $r->totalInvests);
            }
        }
        return array_values($investDays);
    }
}
