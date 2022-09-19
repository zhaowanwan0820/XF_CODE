<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Library\DateTimeLib;
use NCFGroup\Ptp\models\Firstp2pUser;
use NCFGroup\Ptp\models\Firstp2pDeal;
use NCFGroup\Ptp\models\Firstp2pDealExt;
use NCFGroup\Ptp\models\Firstp2pDealLoad;
use NCFGroup\Ptp\models\Firstp2pCouponLog;
use NCFGroup\Ptp\models\Firstp2pCouponPayLog;
use NCFGroup\Ptp\models\Firstp2pDealLoanRepay;
use NCFGroup\Ptp\models\Firstp2pUserLog0;
use NCFGroup\Ptp\models\Firstp2pMoneyQueue;
use NCFGroup\Ptp\models\Firstp2pUserBankcard;
use NCFGroup\Ptp\models\Firstp2pUserTag;
use NCFGroup\Ptp\models\Firstp2pUserTagRelation;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Protos\Ptp\Enum\UserEnum;
use libs\utils\PaymentApi;
use \libs\utils\DBDes;

/**
 * UserDAO
 * 用户相关数据库操作
 * deal.deal_status: 0待等材料，1进行中，2满标，3流标.4还款中，5已还清
 * deal.loantype: 还款方式 1:按季等额还款；2:按月等额还款；3:到期支付本金收益 4:按月付息一次还本 5:按天一次性还款 6.按季支付收益到期还本
 * coupon_log.type: 类型：1:注册;2:投资;3:特斯拉
 * coupon_log.pay_status: 费用结算状态：-2:未实名认证;-1:未绑定银行卡;0:运营待审核; 1:自动结算; 2:已结算; 3:财务待审核; 4:财务拒绝-未结算
 *
 * @package default
 */
class UserDAO
{
    /**
     * getUserInfo
     * 获取用户基本信息
     *
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getUserInfo($userId)
    {
        Assert::min($userId, 1);
        $userObj = Firstp2pUser::findFirstById($userId);
        return $userObj;
    }

    /**
     * getCfpProfitByUserId
     * 计算理财师收益相关信息
     *
     * @param mixed $cfpId
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getCfpProfitByUserId($cfpId, $userId = 0)
    {
        Assert::min($cfpId, 1);
        $condition = '';
        if ($userId > 0) {
            $condition = " AND consumeUserId = '$userId'";
        }
        // 不用判断is_delete，不可用不再进入
        $ret = array();
        // 已返只统计其中状态为1,2的
        $ret['beenSettled'] = Firstp2pCouponLog::sum(array(
            'column' => 'refererRebateRatioAmount + refererRebateAmount',
            'conditions' => 'dealType=0 AND referUserId = ?0 AND type IN(1, 2) AND payStatus IN(?1, ?2)'.$condition,
            'bind' => array($cfpId, 1, 2)
        ));
        // 已返还需要添加通知贷的部分
        $ret['beenSettled'] += Firstp2pCouponPayLog::sum(array(
            'column' => 'refererRebateRatioAmount + refererRebateAmount',
            'conditions' => 'referUserId = ?0'.$condition,
            'bind' => array($cfpId)
        ));
        // 待返
        $ret['tobeSettled'] = Firstp2pCouponLog::sum(array(
            'column' => 'refererRebateRatioAmount + refererRebateAmount',
            'conditions' => 'dealType=0 AND referUserId = ?0 AND type IN(1, 2) AND payStatus IN(0,3,4) AND dealStatus!=2'.$condition,
            'bind' => array($cfpId)
        ));
        $ret['profitTotal'] = $ret['beenSettled'] + $ret['tobeSettled'];
        return $ret;
    }

    /**
     * getCustomerProfitByUserId
     * === getCfpProfitByUserId，此函数没有区分已返、未返，但是计算简单
     * 获取客户给给这个理财师带来多少佣金
     *
     * @param mixed $userId
     * @param mixed $customerId
     * @static
     * @access public
     * @return void
     */
    public static function getCustomerProfitByUserId($cfpId, $userId)
    {
        Assert::min($cfpId, 1);
        Assert::min($userId, 1);
        $profitTotal = Firstp2pCouponLog::sum(array(
            'column' => 'refererRebateRatioAmount + refererRebateAmount',
            'conditions' => 'referUserId = ?0 AND consumeUserId = ?1 AND type IN (1, 2)',
            'bind' => array($cfpId,$userId)
        ));
        return $profitTotal;
    }

    /**
     * getCustomersInfoByUserId
     * 获取理财师的客户数量以及在投数量相关信息
     *
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getCustomersInfoByUserId($cfpId)
    {
        $ret = array();
        $customerIds = self::getCustomerIdsByUserId($cfpId);
        $ret['customerNum'] = count($customerIds);

        if (empty($customerIds)) {
            $total = 0;
        } else {
            $total = Firstp2pDealLoad::query()
                ->columns('DISTINCT NCFGroup\Ptp\models\Firstp2pDealLoad.userId')
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'NCFGroup\Ptp\models\Firstp2pDealLoad.dealId = d.id', 'd')
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'NCFGroup\Ptp\models\Firstp2pDealLoad.id=cl.dealLoadId', 'cl')
                ->inWhere('NCFGroup\Ptp\models\Firstp2pDealLoad.userId', $customerIds)
                ->andWhere('cl.referUserId = :cfpId: AND d.dealStatus IN(1, 2, 4) AND d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0', array('cfpId' => $cfpId))
                ->execute()
                ->count();
        }
        $ret['investingNum'] = $total;
        return $ret;
    }

    /**
     * getCustomerIdsByUserId
     * 获取理财师的客户ID列表
     *
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getCustomerIdsByUserId($userId)
    {
        Assert::min($userId, 1);
        //$customerNum = Firstp2pCouponLog::count(array(
        //    'distinct' => 'consumeUserId',
        //    'conditions' => 'referUserId = ?0',
        //    'bind' => array($userId)
        //));
        $customers = Firstp2pCouponLog::query()
            ->columns('DISTINCT consumeUserId')
            ->where("referUserId = :userId: AND (type=2 OR type=1)") // 注册和投资
            //->where("referUserId = :userId:")
            ->bind(array("userId" => $userId))
            ->execute();
        $ret = array();
        foreach ($customers as $customerObj) {
            $ret[] = $customerObj->consumeUserId;
        }
        return $ret;
    }

    /**
     * getPrincipal
     * 待收本金，在投金额
     *
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getPrincipal($userId)
    {
        Assert::min($userId, 1);
        $query = "SELECT SUM(d_l.money) AS principal FROM `firstp2p_deal` AS d"
            ."  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id"
            ." WHERE d.deal_status = 4 AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = ".$userId;
        $result = getDI()->get('firstp2p')->query($query)->fetch();
        $principal = $result['principal'] === null ? 0 : $result['principal'];
        return $principal;
    }

    /**
     * getProfitTotal
     * 获取总收益
     *
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getProfitTotal($userId)
    {
        $total = Firstp2pDealLoanRepay::sum(array(
            'column' => 'money',
            'conditions' => 'loanUserId = :userId: AND ((status = 1 AND type IN(2,4,5,7)) OR (status = 0 AND type = 2))',
            'bind' => array('userId' => $userId)
        ));
        return $total;
    }

    /**
     * getInVestingSummaryByUserId
     * 获取用户在投标摘要信息
     *
     * @param mixed $userId
     * @param mixed $cfpId
     * @static
     * @access public
     * @return void
     */
    public static function getInVestingSummaryByUserId($userId, $cfpId)
    {
        $ret = array(); // 普通标相关
        $query = "SELECT d.id, d.name, d.rate, de.income_base_rate, d.loantype, d.repay_time, dl.user_id"
            .", SUM(dl.money) totalMoney, count(dl.id) investNum"
            ." FROM firstp2p_deal d, firstp2p_deal_load dl, firstp2p_deal_ext de, firstp2p_coupon_log cl"
            ." WHERE d.id=dl.deal_id AND cl.deal_load_id = dl.id"
            ." AND d.id=de.deal_id AND d.deal_type=0 AND dl.user_id={$userId} AND cl.refer_user_id={$cfpId}"
            ." AND d.deal_status IN(1, 2, 4) GROUP BY d.id";
        $results = getDI()->get('firstp2p')->query($query)->fetchAll();
        $ret2 = array(); // 通知贷相关
        // 申请赎回的时候进入firstp2p_deal_loan_repay，一旦还清status=1，通知贷只可以申购一次
        $query2 = "SELECT d.id, d.name, d.rate, de.income_base_rate, d.loantype, d.repay_time,"
            ." dl.user_id, SUM(dl.money) totalMoney, count(dl.id) investNum, dc.lock_period,"
            ." dc.redemption_period, d.deal_type"
            ." FROM firstp2p_deal_load dl LEFT JOIN firstp2p_deal d ON dl.deal_id=d.id"
            ." LEFT JOIN  firstp2p_deal_ext de ON dl.deal_id=de.deal_id"
            ." LEFT JOIN firstp2p_deal_compound dc ON dl.deal_id=dc.deal_id"
            ." LEFT JOIN firstp2p_deal_loan_repay dlr ON dl.id=dlr.deal_loan_id"
            ." LEFT JOIN firstp2p_coupon_log cl ON dl.id=cl.deal_load_id"
            ." WHERE d.deal_type=1 AND ((dlr.type=8 AND dlr.status!=1) OR dlr.type is null)"
            ." AND dl.user_id={$userId} AND cl.refer_user_id={$cfpId} AND d.deal_status IN(1, 2, 4) GROUP BY d.id";
        $results2 = getDI()->get('firstp2p')->query($query2)->fetchAll();
        $totalMoney = 0;
        $investNum = 0;
        $rateAvg = 0;
        $timeLimitAvg = 0;
        foreach ($results as $r) {
            $totalMoney += $r['totalMoney'];
            $investNum += $r['investNum'];
            $rateAvg += $r['income_base_rate'] * $r['totalMoney'];
            $days = $r['loantype'] == 5 ? $r['repay_time'] : $r['repay_time'] * 30;
            $timeLimitAvg += $days * $r['totalMoney'];
        }
        foreach ($results2 as $r) {
            $totalMoney += $r['totalMoney'];
            $investNum += $r['investNum'];
            $rateAvg += $r['income_base_rate'] * $r['totalMoney'];
            $days = $r['loantype'] == 5 ? $r['lock_period'] : $r['lock_period'] * 30;
            $days += $r['redemption_period'];
            $timeLimitAvg += $days * $r['totalMoney'];
        }
        if ($totalMoney > 0) {
            $ret['profitRatioAvg'] = number_format($rateAvg / $totalMoney, 2).'%';
            $ret['periodAvg'] = number_format($timeLimitAvg / $totalMoney, 2).'天';
            $ret['investNum'] = $investNum;
            $ret['totalMoney'] = $totalMoney;
            $ret['pastDay'] = self::getPastDayByUserId($userId, $cfpId);
        } else {
            $ret['profitRatioAvg'] = '-';
            $ret['periodAvg'] = '-';
            $ret['investNum'] = '-';
            $ret['totalMoney'] = '-';
            $ret['pastDay'] = self::getPastDayByUserId($userId, $cfpId);
        }

        return $ret;
    }

    /**
     * getLatestDealInfoByUserId
     * 获取用户最近到期标信息
     *
     * @param mixed $userId
     * @param mixed $cfpId
     * @static
     * @access public
     * @return void
     */
    public static function getLatestLoanDealInfoByUserId($userId, $cfpId)
    {
        $columns = array(
            'dlr.dealId',
            'dlr.time',
            'dlr.money',
            'cl.dealLoadId',
            'd.name',
            'd.loantype',
            'd.repayTime',
            'de.incomeBaseRate',
            'dlt.name AS dealTypeText'
        );
        $latestRepays = getDI()->getModelsManager()->createBuilder()
            ->columns(implode(',', $columns))
            ->from(array('dlr' => 'NCFGroup\Ptp\models\Firstp2pDealLoanRepay'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'dlr.dealId = d.id', 'd')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'dlr.dealLoanId = cl.dealLoadId', 'cl')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealExt', 'd.id=de.dealId', 'de')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanType', 'd.typeId=dlt.id', 'dlt')
            ->where('dlr.loanUserId = :userId: AND cl.referUserId = :cfpId: AND dlr.type IN(1,8) AND dlr.money > 0 AND dlr.status = 0 AND dlr.time != 0', array('userId' => $userId, 'cfpId' => $cfpId))
            ->orderBy('dlr.time')
            ->getQuery()
            ->execute()
            ->toArray();
        $dealLoanIds = array();
        foreach($latestRepays as $one){
            $dealLoanIds[$one['dealLoadId']] = $one['dealLoadId'];
        }
        $latestRepay = array_shift($latestRepays);
        if ($latestRepay) { // 对于有最近投资的
            $dealId = $latestRepay['dealId'];
            $ret['latestDay'] = ceil((DateTimeLib::realPtpDbTime($latestRepay['time']) - time()) / 86400);
            $ret['latestDay'] = $ret['latestDay'] == 0 ? 0 : $ret['latestDay'];
            $ret['money'] = $latestRepay['money'];
            $ret['dealName'] = $latestRepay['name'];
            $dealLoanIdsStr = implode(',',$dealLoanIds);
            $loanAmount = Firstp2pDealLoad::sum(array(
                'column' => 'money',
                'conditions' => 'dealId = ?0 AND userId = ?1 AND id IN ( '.$dealLoanIdsStr.' )',
                'bind' => array($dealId, $userId)
            ));
            $ret['loanAmount'] = $loanAmount;
            $ret['dealRate'] = number_format($latestRepay['incomeBaseRate'], 2).'%';
            $ret['dealLoanType'] = @$GLOBALS['dict']['LOAN_TYPE'][$latestRepay['loantype']];

            //new add by @wangfei5
            $ret['moneyOriginal'] = number_format($latestRepay['money'],2,'.','');
            $ret['loanAmountOriginal'] = number_format($loanAmount,2,'.','');
            $ret['dealRateOriginal'] = number_format($latestRepay['incomeBaseRate'], 2);
            $ret['bidRepayLimitTime'] = $latestRepay['loantype'] == 5 ? $latestRepay['repayTime'].'天':$latestRepay['repayTime'].'个月';
            $ret['dealTypeText'] = $latestRepay['dealTypeText'];
        } else {
            $ret['latestDay'] = '-';
            $ret['money'] = '-';
            $ret['dealName'] = '-';
            $ret['loanAmount'] = '-';
            $ret['dealRate'] = '-';
            $ret['dealLoanType'] = '-';
            //new add by @wangfei5
            $ret['moneyOriginal'] = '-';
            $ret['loanAmountOriginal'] = '-';
            $ret['dealRateOriginal'] = '-';
            $ret['bidRepayLimitTime'] = '-';
            $ret['dealTypeText'] = '';
        }
        return $ret;
    }

    /**
     * getPastDayByUserId
     * 获取用户最近投资时间
     *
     * @param mixed $userId
     * @param mixed $cfpId
     * @static
     * @access public
     * @return void
     */
    public static function getPastDayByUserId($userId, $cfpId)
    {
        $ret = '';
        //$firstLoad = Firstp2pDealLoad::query()
        //    ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'NCFGroup\Ptp\models\Firstp2pDealLoad.dealId=d.id', 'd')
        //    ->columns('NCFGroup\Ptp\models\Firstp2pDealLoad.createTime')
        //    ->where('NCFGroup\Ptp\models\Firstp2pDealLoad.userId = :userId: AND d.dealStatus IN(1,2,4) AND d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0 AND d.dealType=0')
        //    ->bind(array('userId' => $userId))
        //    ->orderBy('NCFGroup\Ptp\models\Firstp2pDealLoad.createTime DESC')
        //    ->execute()
        //    ->getFirst();
        // 兼容通知贷的处理，需要去除已经赎回成功的投资，该类属于已还清
        $firstLoad = Firstp2pDealLoad::query()
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'NCFGroup\Ptp\models\Firstp2pDealLoad.dealId=d.id', 'd')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'NCFGroup\Ptp\models\Firstp2pDealLoad.id=cl.dealLoadId', 'cl')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanRepay', 'NCFGroup\Ptp\models\Firstp2pDealLoad.id=dlr.dealLoanId', 'dlr')
            ->columns('NCFGroup\Ptp\models\Firstp2pDealLoad.createTime')
            ->where('NCFGroup\Ptp\models\Firstp2pDealLoad.userId = :userId: AND cl.referUserId = :cfpId: AND d.dealStatus IN(1,2,4) AND d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0 AND (d.dealType=0 OR (d.dealType=1 AND (dlr.type IS null OR (dlr.status!=1 AND dlr.type=8))))')
            ->bind(array('userId' => $userId, 'cfpId' => $cfpId))
            ->orderBy('NCFGroup\Ptp\models\Firstp2pDealLoad.createTime DESC')
            ->execute()
            ->getFirst();
        if (!$firstLoad) {
            $ert = '-';
        } else {
            $time = DateTimeLib::realPtpDbTime($firstLoad['createTime']);
            $days = ceil((time() - $time) / 86400);
            if ($days > 30) {
                $ret = floor($days / 30).'个月前';
            } else {
                $ret = $days.'天前';
            }
        }
        return $ret;
    }

    /**
     * getCustomerListByUserId
     * 获取理财师的客户列表
     *
     * @param mixed $cfpId
     * @param int $type 0表示在投，1表示空仓，其余表示所有
     * @param Pageable $pageable
     * @static
     * @access public
     * @return void
     */
    public static function getCustomerListByUserId($cfpId, $type = 0, Pageable $pageable = null)
    {
        $userIds = self::getCustomerIdsByUserId($cfpId);
        $investingUserIds = self::getInvestingUserIds($userIds, $cfpId);
        $repayUserIdsOrder = self::getLatestRepayCustomerListByUserId($cfpId);
        $repayUserIdsOrder = array_reverse($repayUserIdsOrder);
        $orderUserIds = array();
        foreach ($repayUserIdsOrder as $item) {
            $orderUserIds[] = $item['loanUserId'];
        }
        $orderUserIdsStr = empty($orderUserIds) ? "''" : implode(',', $orderUserIds);

        if ($type == 0) { // 在投按到款时间排序
            $obj = new Firstp2pCouponLog();
            $builder = $obj->createBuilder()
                ->columns('consumeUserId AS retUserId')
                ->inWhere('consumeUserId', empty($investingUserIds) ? array('') : $investingUserIds)
                ->andWhere('referUserId = :cfpId:', array('cfpId' => $cfpId))
                ->groupBy('consumeUserId')
                ->orderBy("FIELD(consumeUserId, $orderUserIdsStr) DESC, consumeUserId");
        } elseif ($type == 1) { // 空仓 按最近投标时间排序
            $noinvestUserIds = array_diff($userIds, $investingUserIds);
            $obj = new Firstp2pCouponLog();
            $builder = $obj->createBuilder()
                ->columns('consumeUserId AS retUserId')
                ->inWhere('consumeUserId', empty($noinvestUserIds) ? array('') : $noinvestUserIds)
                ->andWhere('referUserId = :cfpId:', array('cfpId' => $cfpId))
                ->groupBy('consumeUserId')
                ->orderBy('MAX(createTime), consumeUserId');
        } else { // 在投 + 空仓，排序时候在投在前
            $obj = new Firstp2pCouponLog();
            $builder = $obj->createBuilder()
                ->columns('consumeUserId AS retUserId')
                ->inWhere('consumeUserId', empty($userIds) ? array('') : $userIds)
                ->andWhere('referUserId = :cfpId:', array('cfpId' => $cfpId))
                ->groupBy('consumeUserId')
                ->orderBy("FIELD(consumeUserId, $orderUserIdsStr) DESC, consumeUserId");
        }
        if ($pageable) {
            $total = $builder->getQuery()->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $customerIds = $builder->getQuery()->execute();
        $list = array();
        foreach ($customerIds as $customer) {
            $list[$customer->retUserId] = $customer; //->toArray();
        }
        if ($pageable) {
            $ret['list'] = $list;
            $ret['total'] = $total;
            $ret['pageNo'] = $pageable->getPageNo();
            $ret['pageSize'] = $pageable->getPageSize();
            return $ret;
        } else {
            return $list;
        }
    }

    /**
     * getInvestingUserIds
     * 筛选给定客户数组中处于在投的客户ID
     *
     * @param mixed $userIds
     * @static
     * @access public
     * @return void
     */
    public static function getInvestingUserIds($userIds, $cfpId)
    {
        if (empty($userIds)) {
            return array();
        }
        try {
            $result = Firstp2pDealLoad::query()
                ->columns('DISTINCT NCFGroup\Ptp\models\Firstp2pDealLoad.userId')
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'NCFGroup\Ptp\models\Firstp2pDealLoad.dealId = d.id', 'd')
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'NCFGroup\Ptp\models\Firstp2pDealLoad.id=cl.dealLoadId', 'cl')
                ->inWhere('NCFGroup\Ptp\models\Firstp2pDealLoad.userId', $userIds)
                ->andWhere('cl.referUserId = :cfpId: AND d.dealStatus IN(1, 2, 4) AND d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0', array('cfpId' => $cfpId))
                ->execute()
                ->toArray();
            foreach ($result as $r) {
                $ret[] = $r['userId'];
            }
        } catch (\Exception $e) {
            $ret = array();
        }
        return $ret;
    }

    /**
     * getHaveInvestedUserIds
     * 投过资的客户
     *
     * @param mixed $userIds
     * @static
     * @access public
     * @return void
     */
    public static function getHaveInvestedUserIds($userIds)
    {
        if (empty($userIds)) {
            return array();
        }
        try {
            $result = Firstp2pDealLoad::query()
                ->columns('DISTINCT NCFGroup\Ptp\models\Firstp2pDealLoad.userId')
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'NCFGroup\Ptp\models\Firstp2pDealLoad.dealId = d.id', 'd')
                ->inWhere('NCFGroup\Ptp\models\Firstp2pDealLoad.userId', $userIds)
                ->andWhere('d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0')
                ->execute()
                ->toArray();
            foreach ($result as $r) {
                $ret[] = $r['userId'];
            }
        } catch (\Exception $e) {
            $ret = array();
        }
        return $ret;
    }

    /**
     * getCommissionsByUserId
     * 获取理财师佣金列表
     *
     * @param mixed $cfpId
     * @param mixed $userId
     * @param Pageable $pageable
     * @access static
     * @return void
     */
    public static function getCommissionsByUserId($cfpId, $userId, Pageable $pageable = null, $skeys = array())
    {
        $columns = array(
            'cl.refererRebateRatioAmount',
            'cl.refererRebateAmount',
            'cl.refererRebateRatio',
            'cl.payStatus', // 1 OR 2已结，其余未结
            'cl.consumeUserId',
            'cl.dealLoadId',
            'dl.dealId',
            'dl.userId',
            'dl.userName',
            'dl.userDealName',
            'IF(cl.type=1, cl.createTime, dl.createTime) AS createTimeNew',
            'd.name AS dealName',
            'd.borrowAmount',
            'd.rate',
            'd.dealType',
            'd.repayTime',
            'dl.money',
            'dlt.name AS dealTypeText',
            'd.loantype'
            //'NCFGroup\Ptp\models\Firstp2pDealLoanRepay.time',
        );
        //$builder = Firstp2pCouponLog::query()
        //$builder = (new Firstp2pCouponLog())->createBuilder()
        $builder = getDI()->getModelsManager()->createBuilder()
            ->from(array('cl' => 'NCFGroup\Ptp\models\Firstp2pCouponLog'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoad', 'cl.dealLoadId = dl.id', 'dl')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'cl.dealId = d.id', 'd')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanType', 'd.typeId=dlt.id', 'dlt')
            //->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanRepay', 'dl.id = dlr.dealLoanId', 'dlr')
            ->where('cl.type IN(1, 2) AND cl.referUserId=:cfpId:', array('cfpId' => $cfpId));
        if ($userId > 0) { // 指定了客户
            $builder->andWhere('cl.consumeUserId=:userId:', array('userId' => $userId));
        }
        self::generateSearchCommissionConditions($builder, $skeys);
        // 计算搜索结果总的总和 通知贷的全部为已返
        $summary = array();
        $summary1 = $builder->columns('SUM(IF(cl.dealType = 0 AND (cl.payStatus = 1 OR cl.payStatus = 2), cl.refererRebateAmount + cl.refererRebateRatioAmount, 0)) beenSettled, SUM(IF(cl.dealType = 0 AND cl.payStatus IN(0,3,4), cl.refererRebateAmount + cl.refererRebateRatioAmount, 0)) tobeSettled')->getQuery()->execute()->getFirst()->toArray();
        if ($summary1['beenSettled'] === null) {
            $summary1['beenSettled'] = 0;
            $summary1['tobeSettled'] = 0;
        }
        if (isset($skeys['skeySt']) && $skeys['skeySt'] == 0) { // 对于指定了未返状态的，通知贷对应佣金返回0
            $summary2['beenSettled'] = 0;
        } else {
            // 其它则需要统计所有符合条件的标对应的已返佣金
            // 获取符合条件的标
            $builder->columns('DISTINCT(cl.dealLoadId) AS dlId');
            $rs = $builder->getQuery()->execute();
            $dlIds = array();
            foreach ($rs as $r) {
                $dlIds[] = $r->dlId;
            }
            if (!empty($dlIds)) {
                $summary2['beenSettled'] = Firstp2pCouponPayLog::sum(array(
                    'column' => 'refererRebateRatioAmount + refererRebateAmount',
                    'conditions' => 'referUserId = ?0 AND dealLoadId IN ('.implode(',', $dlIds).')',
                    'bind' => array($cfpId)
                ));
            } else {
                $summary2['beenSettled'] = 0;
            }
        }
        $summary['beenSettled'] = strval($summary1['beenSettled'] + $summary2['beenSettled']);
        $summary['tobeSettled'] = $summary1['tobeSettled'];
        $builder->columns(implode(', ', $columns));
        $total = $builder->getQuery()->execute()->count();
        $builder->orderBy('cl.createTime DESC');
        if ($pageable) {
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $list = $builder->getQuery()->execute()->toArray();
        // 丰富通知贷里面返利金额
        self::getCompoundCommisionInfo($list, $cfpId);
        if ($pageable) {
            $ret['list'] = $list;
            $ret['total'] = $total;
            $ret['summary'] = $summary;
            $ret['pageNo'] = $pageable->getPageNo();
            $ret['pageSize'] = $pageable->getPageSize();
            return $ret;
        } else {
            return $list;
        }
    }

    /**
     * getCompoundCommisionInfo
     * 佣金记录中补充通知贷的已返佣金信息
     *
     * @param mixed $list
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    public static function getCompoundCommisionInfo(&$list, $cfpId)
    {
        foreach ($list as &$item) {
            if ($item['dealType'] == 1) {
                $compoundCommision = Firstp2pCouponPayLog::query()
                    ->columns('SUM(refererRebateRatioAmount) AS refererRebateRatioAmount, SUM(refererRebateAmount) AS refererRebateAmount')
                    ->where('referUserId = :cfpId: AND dealLoadId = :dealLoadId:')
                    ->bind(array('cfpId' => $cfpId, 'dealLoadId' => $item['dealLoadId']))
                    ->execute()
                    ->getFirst()
                    ->toArray();
                $item['refererRebateRatioAmount'] = $compoundCommision['refererRebateRatioAmount'] === null ? 0 : $compoundCommision['refererRebateRatioAmount'];
                $item['refererRebateAmount'] = $compoundCommision['refererRebateAmount'] === null ? 0 : $compoundCommision['refererRebateAmount'];
                $item['payStatus'] = 2; // 对于通知贷的，统一为已返
            }
        }
    }

    /**
     * getLatestRepayCustomerListByUserId
     * 获取到期客户列表
     *
     * @param mixed $cfpId
     * @param Pageable $pageable
     * @static
     * @access public
     * @return void
     */
    public static function getLatestRepayCustomerListByUserId($cfpId, Pageable $pageable = null)
    {
        $userIds = self::getCustomerIdsByUserId($cfpId);
        $builder = getDI()->getModelsManager()->createBuilder()
            ->columns('dlr.loanUserId, MIN(dlr.time) time')
            ->from(array('dlr' => 'NCFGroup\Ptp\models\Firstp2pDealLoanRepay'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'cl.dealLoadId = dlr.dealLoanId', 'cl')
            ->inWhere('dlr.loanUserId', empty($userIds) ? array(0) : $userIds)
            ->andWhere('dlr.type IN(1,8) AND dlr.money > 0 AND dlr.status = 0 AND cl.referUserId = '.$cfpId)
            ->groupBy('dlr.loanUserId')
            ->orderBy('MIN(dlr.time), dlr.loanUserId');
        //$obj = new Firstp2pDealLoanRepay();
        //$builder = $obj->createBuilder()
        //    ->columns('loanUserId,time')
        //    ->inWhere('loanUserId', empty($userIds) ? array(0) : $userIds)
        //    ->andWhere('type IN(1,8) AND money > 0 AND status = 0')
        //    ->groupBy('loanUserId')
        //    ->orderBy('MIN(time)'); // 排序加到此处，为后面的到期客户排序做准备
        if ($pageable) {
            $total = $builder->getQuery()->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $list = $builder->getQuery()->execute()->toArray();
        if ($pageable) {
            $ret['list'] = $list;
            $ret['total'] = $total;
            $ret['pageNo'] = $pageable->getPageNo();
            $ret['pageSize'] = $pageable->getPageSize();
            return $ret;
        } else {
            return $list;
        }
    }

    /**
     * getInvestAnalyse
     * 投资分析数据
     *
     * @param array $cfpId
     * @param array $userIds
     * @param int $type 0当前投标，1总体
     * @static
     * @access public
     * @return void
     */
    public static function getInvestAnalyse($cfpId, array $userIds, $type = 0)
    {
        $time = 0;
        $oneBegin  = 1;
        $threeBegin = 3;
        $sixBegin = 6;
        $nineBegin = 9;
        $twelveBegin = 12;

        $columns = array(
            'dl.money',
            'd.id',
            'de.incomeBaseRate',
            'dc.lockPeriod',
            'dc.redemptionPeriod',
        );
        $criteria = getDI()->getModelsManager()->createBuilder()
            ->columns(implode(', ', $columns))
            ->from(array('dl' => 'NCFGroup\Ptp\models\Firstp2pDealLoad'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'dl.dealId = d.id', 'd')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'cl.dealLoadId = dl.id', 'cl')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealExt', 'dl.dealId = de.dealId', 'de')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealCompound', 'dl.dealId = dc.dealId', 'dc')
            ->inWhere('dl.userId', empty($userIds) ? array(0) : $userIds)
            ->andWhere('cl.referUserId = :cfpId: AND d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0', array('cfpId' => $cfpId));
        if ($type == 0) {
            $criteria->inWhere('d.dealStatus', array(1,2,4));
        } else {
            $criteria->inWhere('d.dealStatus', array(1,2,4,5));
        }

        $analyses = array();
        $totalInvested= 0;
        $one = self::getAnalyseByTime($criteria, $oneBegin, $time);
        $one['x'] = '1月';
        $totalInvested += $one['ys'];
        unset($one['ys']);
        $analyses[] = $one;
        $three = self::getAnalyseByTime($criteria, $threeBegin, $oneBegin);
        $three['x'] = '3月';
        $totalInvested += $three['ys'];
        unset($three['ys']);
        $analyses[] = $three;
        $six = self::getAnalyseByTime($criteria, $sixBegin, $threeBegin);
        $six['x'] = '6月';
        $totalInvested += $six['ys'];
        unset($six['ys']);
        $analyses[] = $six;
        $nine = self::getAnalyseByTime($criteria, $nineBegin, $sixBegin);
        $nine['x'] = '9月';
        $totalInvested += $nine['ys'];
        unset($nine['ys']);
        $analyses[] = $nine;
        $twelve = self::getAnalyseByTime($criteria, $twelveBegin, $nineBegin);
        $twelve['x'] = '12月';
        $totalInvested += $twelve['ys'];
        unset($twelve['ys']);
        $analyses[] = $twelve;
        $other = self::getAnalyseByTime($criteria, 1000, $twelveBegin); // 最多1000个月
        $other['x'] = '>12月';
        $totalInvested += $other['ys'];
        unset($other['ys']);
        $analyses[] = $other;

        $ret = array(
            'totalInvested' => number_format($totalInvested / 10000, 2),
            'details' => $analyses
        );
        return $ret;
    }

    /**
     * getAnalyseByTime
     * 指定时间段具体分析数据
     *
     * @param mixed $criteria
     * @param mixed $begin
     * @param mixed $end
     * @static
     * @access private
     * @return void
     */
    private static function getAnalyseByTime($criteria, $end, $begin)
    {
        $ret = array('y' => '-', 'ratio' => '-');
        // 如果是通知贷，平均周期以firstp2p_deal_compound.lock_period + firstp2p_deal_compound.redemption_period为准
        $result = $criteria->andWhere('IF(d.dealType=1,ceil(IF(d.loantype=5,dc.lockPeriod * 30 + dc.redemptionPeriod,dc.lockPeriod + dc.redemptionPeriod)/30),ceil(d.repayTime / IF(d.loantype=5,30,1))) > :begin: AND IF(d.dealType=1,ceil(IF(d.loantype=5,dc.lockPeriod * 30 + dc.redemptionPeriod,dc.lockPeriod + dc.redemptionPeriod)/30),ceil(d.repayTime / IF(d.loantype=5,30,1))) <= :end:', array('begin' => $begin, 'end' => $end))
            ->getQuery()->execute()->toArray();
        $moneys = array();
        $x = 0;
        $y = 0;
        foreach ($result as $r) {
            $x += $r['incomeBaseRate'] * $r['money'];
            $y += $r['money'];
        }
        $ret['ys'] = $y;
        $ret['y'] = number_format($y / 10000, 2);
        if ($y > 0) {
            $ret['ratio'] = number_format($x / $y, 2);
        } else {
            $ret['ratio'] = '-';
        }

        //if (!empty($moneys)) {
        //    //$dealInfo = Firstp2pDeal::query()
        //    //    ->columns('SUM(de.incomeBaseRate * borrowAmount) totalRate, SUM(borrowAmount) totalAmount')
        //    //    ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealExt', 'NCFGroup\Ptp\models\Firstp2pDeal.id = de.dealId', 'de')
        //    //    ->inWhere('id', array_keys($moneys))
        //    //    ->execute()->getFirst();
        //    //if ($dealInfo) {
        //    //    $ret['ratio'] = number_format($dealInfo['totalRate'] / $dealInfo['totalAmount'], 2);
        //    //}
        //}
        return $ret;
    }

    /**
     * searchCfpCustomers
     * 搜索客户
     *
     * @param mixed $userId
     * @param mixed $skey
     * @static
     * @access public
     * @return void
     */
    public static function searchCfpCustomers($cfpId, $searchConditions, Pageable $pageable = null)
    {
        /*
        $paramMap = array(
            //type => array(params list);
            0=>array('skey'),
            1=>array('bidRepayDayMin','bidRepayDayMax'),
            2=>array('bidYearrate'),
            3=>array('bidRepayLimitTime'),
            4=>array('benefitMoneyMin','benefitMoneyMax'),
        );
        */
        $type = $searchConditions['type'];
        $skey = $searchConditions['skey'];
        $bidRepayDayMin = $searchConditions['bidRepayDayMin'];
        $bidRepayDayMax = $searchConditions['bidRepayDayMax'];
        $bidYearrate = $searchConditions['bidYearrate'];
        $bidRepayLimitTime = $searchConditions['bidRepayLimitTime'];
        $benefitMoneyMin = $searchConditions['benefitMoneyMin'];
        $benefitMoneyMax = $searchConditions['benefitMoneyMax'];
        $pageable = $searchConditions['pageable'];
        $customerIds = array();
        $criteria = Firstp2pUser::query();
        switch($type){
            // 电话，姓名查询
            case 0:
                if (!empty($skey)) {
                    $criteria->where('realName = :skey: OR mobile = :skey:', array('skey' => $skey));
                }
                $customerIds = self::getCustomerIdsByUserId($cfpId);
            break;
            // 最近到期查询
            case 1:
                $list = self::getLatestRepayCustomerListByUserId($cfpId);
                foreach($list as $one){
                    if ( $bidRepayDayMax == 0 || $bidRepayDayMin < 0 ){
                        $customerIds[] = $one['loanUserId'];
                    }else{
                        $latestDay = intval(ceil((DateTimeLib::realPtpDbTime($one['time']) - time()) / 86400));
                        $latestDay = $latestDay == 0 ? 0 : $latestDay;
                        if ( $latestDay>=$bidRepayDayMin && $latestDay<=$bidRepayDayMax ){
                            $customerIds[] = $one['loanUserId'];
                        }
                    }
                }
            break;
            case 2:
            break;
            case 3:
            break;
            case 4:
                // 佣金收入查询，所有客户列表
                $allCustomerIds = self::getCustomerIdsByUserId($cfpId);
                if ( $benefitMoneyMin < 0 || $benefitMoneyMax == 0 ){
                    $customerIds = $allCustomerIds;
                }else{
                    foreach( $allCustomerIds as $one){
                        $ret = self::getCfpProfitByUserId($cfpId, $one);
                        $customerProfit = $ret['profitTotal'];
                        $customerProfitRet = self::getCfpProfitByUserId($cfpId, $one);
                        $customerProfit = $customerProfitRet['tobeSettled']+$customerProfitRet['beenSettled'];
                        if( $customerProfit>=floatval($benefitMoneyMin) && $customerProfit<=floatval($benefitMoneyMax)  ){
                            $customerIds[] = $one;
                        }
                    }
                }
            break;
            default:
        }

        $criteria->inWhere('id', empty($customerIds) ? array(0) : $customerIds);
        if ($pageable) {
            $total = $criteria->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $criteria->limit($limit, $offset);
        }
        $result = $criteria->execute()->toArray();
        $list = array();
        foreach ($result as $r) {
            $r['userId'] = $r['id'];
            $list[$r['id']] = $r;
            //$list[] = array(
            //    'userId' => $r['id'],
            //    'userName' => $r['userName'],
            //    'realName' => $r['realName'],
            //    'mobile' => $r['mobile'],
            //);
        }
        if ($pageable) {
            $ret['list'] = $list;
            $ret['total'] = $total;
            $ret['pageNo'] = $pageable->getPageNo();
            $ret['pageSize'] = $pageable->getPageSize();
            return $ret;
        } else {
            return $list;
        }
    }

    /**
     * generateSearchCommissionConditions
     * 生成佣金等的检索条件
     *
     * @param mixed $builder
     * @param mixed $skeys
     * @static
     * @access private
     * @return void
     */
    private static function generateSearchCommissionConditions(&$builder, $skeys)
    {
        // 添加搜索
        $conds = array();
        if (isset($skeys['skeyDt'])) {
            $dts = explode(',', $skeys['skeyDt']);
            if (!empty($dts[0])) {
                $conds[] = 'cl.createTime >= '.(strtotime($dts[0]) - DateTimeLib::PTP_DB_STEP_TIME);
            }
            if (!empty($dts[1])) {
                $conds[] = 'cl.createTime <= '.(strtotime($dts[1]) - DateTimeLib::PTP_DB_STEP_TIME+86399);
            }
        }
        if (isset($skeys['skeySt'])) { // 通知贷佣金统一为已返
            $st = $skeys['skeySt'];
            if ($st == 0) { // 未返
                $conds[] = '(cl.dealType = 0 AND cl.payStatus NOT IN (1,2))';
            } elseif ($st == 1) { // 已返
                $conds[] = '(cl.dealType = 1 OR cl.payStatus IN (1,2))';
            }
        }
        if (isset($skeys['skeyDealName'])) {
            $conds[] = 'd.name LIKE "%'.$skeys['skeyDealName'].'%"';
        }

        if (isset($skeys['investMin'])){
            $conds[] = 'dl.money >= '.$skeys['investMin'];
        }

        if (isset($skeys['investMax'])){
            $conds[] = 'dl.money <= '.$skeys['investMax'];
        }

        if (isset($skeys['skeyUser'])) {
            $builder->leftJoin('NCFGroup\Ptp\models\Firstp2pUser', 'cl.consumeUserId = u.id', 'u')
                ->andWhere("u.realName LIKE :skeyUser: OR u.mobile LIKE :skeyUser:",
                    array(
                        'skeyUser' => '%'.trim($skeys['skeyUser']).'%',
                    ));
        }
        if (!empty($conds)) {
            $builder->andWhere(implode(' AND ', $conds));
        }
    }


    /**
     * getInvestAndCommissionsByUserId
     * 获取理财师佣he投资金列表
     *
     * @param mixed $cfpId
     * @param mixed $userId
     * @param Pageable $pageable
     * @access static
     * @return void
     */
    public static function getInvestAndCommissionsByUserId($cfpId, $userId, Pageable $pageable = null, $skeys = array())
    {
        $columns = array(
            'cl.refererRebateRatioAmount',
            'cl.refererRebateAmount',
            'cl.refererRebateRatio',
            'cl.payStatus', // 1 OR 2已结，其余未结
            'cl.consumeUserId',
            'cl.dealLoadId',
            'dl.dealId',
            'dl.userId',
            'dl.userName',
            'dl.userDealName',
            'IF(cl.type=1, cl.createTime, dl.createTime) AS createTimeNew',
            'd.name AS dealName',
            'd.borrowAmount',
            'd.rate',
            'd.dealType',
            'd.repayTime',
            'dl.money',
            'dlt.name AS dealTypeText',
            'd.loantype'
            //'NCFGroup\Ptp\models\Firstp2pDealLoanRepay.time',
        );
        //$builder = Firstp2pCouponLog::query()
        //$builder = (new Firstp2pCouponLog())->createBuilder()
        $builder = getDI()->getModelsManager()->createBuilder()
            ->from(array('cl' => 'NCFGroup\Ptp\models\Firstp2pCouponLog'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoad', 'cl.dealLoadId = dl.id', 'dl')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'cl.dealId = d.id', 'd')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanType', 'd.typeId=dlt.id', 'dlt')
            //->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanRepay', 'dl.id = dlr.dealLoanId', 'dlr')
            ->where('cl.type IN(1, 2) AND cl.referUserId=:cfpId:', array('cfpId' => $cfpId));
        if ($userId > 0) { // 指定了客户
            $builder->andWhere('cl.consumeUserId=:userId:', array('userId' => $userId));
        }
        self::generateSearchCommissionConditions($builder, $skeys);
        // 计算搜索结果总的总和 通知贷的全部为已返

        $allInvest = $builder->columns('sum(dl.money) as allInvest')->getquery()->execute()->getfirst()->toarray();

        $summary = array();
        $summary1 = $builder->columns('SUM(IF(cl.dealType = 0 AND (cl.payStatus = 1 OR cl.payStatus = 2), cl.refererRebateAmount + cl.refererRebateRatioAmount, 0)) beenSettled, SUM(IF(cl.dealType = 0 AND cl.payStatus IN(0,3,4), cl.refererRebateAmount + cl.refererRebateRatioAmount, 0)) tobeSettled')->getQuery()->execute()->getFirst()->toArray();
        if ($summary1['beenSettled'] === null) {
            $summary1['beenSettled'] = 0;
            $summary1['tobeSettled'] = 0;
        }
        if (isset($skeys['skeySt']) && $skeys['skeySt'] == 0) { // 对于指定了未返状态的，通知贷对应佣金返回0
            $summary2['beenSettled'] = 0;
        } else {
            // 其它则需要统计所有符合条件的标对应的已返佣金
            // 获取符合条件的标
            $builder->columns('DISTINCT(cl.dealLoadId) AS dlId');
            $rs = $builder->getQuery()->execute();
            $dlIds = array();
            foreach ($rs as $r) {
                $dlIds[] = $r->dlId;
            }
            if (!empty($dlIds)) {
                $summary2['beenSettled'] = Firstp2pCouponPayLog::sum(array(
                    'column' => 'refererRebateRatioAmount + refererRebateAmount',
                    'conditions' => 'referUserId = ?0 AND dealLoadId IN ('.implode(',', $dlIds).')',
                    'bind' => array($cfpId)
                ));
            } else {
                $summary2['beenSettled'] = 0;
            }
        }
        $summary['beenSettled'] = strval($summary1['beenSettled'] + $summary2['beenSettled']);
        $summary['tobeSettled'] = $summary1['tobeSettled'];
        $builder->columns(implode(', ', $columns));
        $total = $builder->getQuery()->execute()->count();
        $builder->orderBy('cl.createTime DESC');
        if ($pageable) {
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $list = $builder->getQuery()->execute()->toArray();
        // 丰富通知贷里面返利金额
        self::getCompoundCommisionInfo($list, $cfpId);
        if ($pageable) {
            $ret['list'] = $list;
            $ret['total'] = $total;
            $ret['summary'] = $summary;
            $ret['allInvest'] = $allInvest['allInvest'];
            $ret['pageNo'] = $pageable->getPageNo();
            $ret['pageSize'] = $pageable->getPageSize();
            return $ret;
        } else {
            return $list;
        }
    }

    /**
     * 直接进行资金变动
     * @param int $userId 用户UID
     * @param float $money 金额，单位元
     * @param string $message 类型(消费简介)
     * @param string $note 备注(消费事由)
     * @param int $moneyType 金额类型(0:增加余额1:冻结金额，增加冻结资金同时减少余额2:减少冻结金额)
     * @param int $negative 是否允许负数的用户余额
     * @param int $adminId 管理员id
     * @param int $isManage 是否是管理费
     * @param boolean $isMoneyAsync 是否异步更新用户余额 
     * @param array $bizToken 业务参数数组
     * @return boolean
     **/
    public static function changeMoney($userId, $money, $message, $note, $moneyType = 0, $negative = 0, $adminId = 0, $isManage = 0, $isMoneyAsync = false, $bizToken = [])
    {
        if (!is_numeric($userId) OR $userId <= 0 OR !is_numeric($money)) {
            return false;
        }

        // 生成业务token
        $bizToken = self::generateBizToken($bizToken);
        if (empty($bizToken)) {
            Logger::info("UserDAO::changeMoney no bizToken. userId:{$userId}, money:{$money}, log_info:{$message}, note:{$note}");
        }

        //是否异步更新用户余额
        if ($isMoneyAsync === true) {
            //插入firstp2p_money_queue表，异步更新用户余额
            return self::insertMoneyQueue($userId, $money, $message, $note, $moneyType, $bizToken);
        }

        $res = false;
        $db = getDI()->get('firstp2p');
        //开启事务
        $db->begin();
        try {
            // 不是管理费、money不为0时，更新用户余额
            if ((int)$isManage == 0 && $money != 0) {
                //根据Money类型，更新用户余额
                self::updateUserMoneyByType($userId, $money, $moneyType, $negative);
            }

            //记录用户的资金变动日志
            self::insertUserLog($userId, $money, $message, $note, $moneyType, $adminId, null, $bizToken);

            $db->commit();
            $res = true;
        } catch (\Exception $ex) {
            Logger::error(sprintf('%s|%s.%s_is_exception,userId:%d,money:%d,message:%s,note:%s,moneyType:%d,negative:%d,adminId:%d,isManage:%d,isMoneyAsync:%s,ExceptionMsg:%s', TASK_APP_NAME, __CLASS__, __FUNCTION__, $userId, $money, $message, $note, $moneyType, $negative, $adminId, $isManage, (int)$isMoneyAsync, $ex->getMessage()));
            //回滚事务
            $db->rollback();
        }
        return $res;
    }

    /**
     * 插入firstp2p_money_queue表，异步更新用户余额
     * @param int $userId 用户UID
     * @param float $money 金额
     * @param string $message 类型(消费简介)
     * @param string $note 备注(消费事由)
     * @param int $moneyType 金额类型(0:增加余额1:冻结金额，增加冻结资金同时减少余额2:减少冻结金额)
     * @param array $bizToken 业务参数数组
     * @return boolean
     **/
    public static function insertMoneyQueue($userId, $money, $message, $note, $moneyType = 0, $bizToken = [])
    {
        if (!is_numeric($userId) OR $userId <= 0) {
            return false;
        }

        // 生成业务token
        $bizToken = self::generateBizToken($bizToken);
        if (empty($bizToken)) {
            Logger::info("UserDAO::insertMoneyQueue no bizToken. userId:{$userId}, money:{$money}, log_info:{$message}, note:{$note}");
        }

        //插入firstp2p_money_queue表
        $moneyQueueModel = new Firstp2pMoneyQueue();
        $moneyQueueModel->userId = $userId;
        $moneyQueueModel->money = $money;
        $moneyQueueModel->message = $message;
        $moneyQueueModel->note = $note;
        $moneyQueueModel->moneyType = $moneyType;
        $moneyQueueModel->bizToken = $bizToken;
        $moneyQueueModel->createTime = time();
        $moneyQueueModel->status = 0;
        if ($moneyQueueModel->create() == false) {
            Logger::error(implode(' | ', array(__FUNCTION__, TASK_APP_NAME, 
                'Insert_firstp2p_money_queue_failed', $userId, $money, $message, 
                $note, $moneyType, $bizToken, $moneyQueueModel->getMessage())));
            return false;
        }
        unset($moneyQueueModel);
        return true;
    }

    /**
     * 根据Money类型，更新用户余额
     * @param int $userId 用户UID
     * @param float $money 金额
     * @param int $moneyType 金额类型(0:增加余额1:冻结金额，增加冻结资金同时减少余额2:减少冻结金额)
     * @param int $negative 是否允许负数的用户余额
     * @return boolean
     */
    public static function updateUserMoneyByType($userId, $money, $moneyType, $negative = 0)
    {
        if (!is_numeric($userId) OR $userId <= 0 OR $money == 0) {
            throw new \Exception('参数不合法');
        }

        $ret = false;
        $userModel = new Firstp2pUser();
        //获取GMTime时间
        $updateTime = self::getGmtime();
        $sql = '';
        switch ($moneyType) {
            case UserEnum::TYPE_MONEY: //增加余额
                $sqlWhere = $negative == 0 ? sprintf(' AND money + \'%s\' >= 0', floatval($money)) : '';
                $sql = sprintf('UPDATE `%s` SET `money` = `money` + \'%s\', `update_time` = \'%s\'
                    WHERE `id`=\'%d\' %s', $userModel->getSource(), floatval($money), $updateTime,
                    $userId, $sqlWhere);
                break;
            case UserEnum::TYPE_LOCK_MONEY: //冻结金额，增加冻结资金同时减少余额
                // 不允许扣负
                if ($negative == 0) {
                    if (bccomp($money, '0.00', 2) >= 0) {
                        $sqlWhere = sprintf(' AND `money` >= \'%s\'', floatval($money));
                    }else{
                        $sqlWhere = sprintf(' AND `lock_money` + \'%s\' >= 0', floatval($money));
                    }
                }
                $sql = sprintf('UPDATE `%s` SET `money` = `money` - \'%s\', `lock_money` = `lock_money` + \'%s\',
                    `update_time` = \'%s\' WHERE `id`=\'%d\' %s', $userModel->getSource(), floatval($money), 
                    floatval($money), $updateTime, $userId, $sqlWhere);
                break;
            case UserEnum::TYPE_DEDUCT_LOCK_MONEY: //减少冻结金额
                // 不允许扣负
                $sqlWhere = $negative == 0 ? sprintf(' AND `lock_money` >= \'%s\'', floatval($money)) : '';
                $sql = sprintf('UPDATE `%s` SET `lock_money` = `lock_money` - \'%s\', `update_time` = \'%s\'
                    WHERE `id`=\'%d\' %s', $userModel->getSource(), floatval($money), $updateTime,
                    $userId, $sqlWhere);
                break;
            default: //未知类型
                break;
        }

        if ($sql) {
            $db = $userModel->getDI()->get('firstp2p');
            $updateUserInfoRet = $db->execute($sql);
            $affectedRows = $db->affectedRows();
            unset($userModel);
            // core\dao\UserModel里面的ChangeMoney有变动
            $userData = new \core\data\UserData();
            $userData->clearUserSummary($userId);
            if (!$updateUserInfoRet || ($money != 0 && $affectedRows <= 0)) {
                throw new \Exception('ChangeMoney修改用户余额失败');
            }else{
                $ret = true;
            }
        }
        return $ret;
    }

    /**
     * 记录用户的资金变动日志
     * @param int $userId 用户UID
     * @param float $money 金额
     * @param string $message 类型(消费简介)
     * @param string $note 备注(消费事由)
     * @param int $moneyType 金额类型(0:增加余额1:冻结金额，增加冻结资金同时减少余额2:减少冻结金额)
     * @param int $adminId 管理员id
     * @param array $userInfo 用户信息
     * @param array $bizToken 业务参数数组
     * @return boolean
     */
    public static function insertUserLog($userId, $money, $message, $note, $moneyType = 0, $adminId = 0, $userInfo = null, $bizToken = [])
    {
        // 生成业务token
        $bizToken = self::generateBizToken($bizToken);
        if (empty($bizToken)) {
            Logger::info("UserDAO::insertUserLog no bizToken. userId:{$userId}, money:{$money}, log_info:{$message}, note:{$note}");
        }

        //根据用户UID，获取最新的用户金额数据
        is_null($userInfo) && $userInfo = Firstp2pUser::findFirst($userId);
        //记录资金变动日志
        $userLogModel = new Firstp2pUserLog0();
        $userLogModel->logInfo = $message;
        $userLogModel->note = $note;
        $userLogModel->logTime = self::getGmtime();
        $userLogModel->logAdminId = $adminId;
        $userLogModel->logUserId = $userId;
        $userLogModel->userId = $userId;
        $userLogModel->remainingMoney = isset($userInfo->money) ? $userInfo->money : 0;
        $userLogModel->remainingTotalMoney = (isset($userInfo->money) && isset($userInfo->lockMoney)) ? $userInfo->money + $userInfo->lockMoney : 0;
        $userLogModel->score = $userLogModel->point = $userLogModel->quota = 0;
        unset($userInfo);
        //根据Money类型，给firstp2p_user_log_[0-9]表中的money、lock_money赋值
        switch ($moneyType) {
            case UserEnum::TYPE_MONEY: //增加余额
                $userLogModel->money = floatval($money);
                $userLogModel->remainingMoney += $userLogModel->money;
                break;
            case UserEnum::TYPE_LOCK_MONEY: //冻结金额，增加冻结资金同时减少余额
                $userLogModel->money = -floatval($money);
                $userLogModel->lockMoney = floatval($money);
                $userLogModel->remainingMoney += $userLogModel->money;
                $userLogModel->remainingTotalMoney += $userLogModel->money + $userLogModel->lockMoney;
                break;
            case UserEnum::TYPE_DEDUCT_LOCK_MONEY: //减少冻结金额
                $userLogModel->money = 0;
                $userLogModel->lockMoney = -floatval($money);
                $userLogModel->remainingTotalMoney += $userLogModel->lockMoney;
                break;
            default: //未知类型
                break;
        }
        $userLogModel->bizToken = $bizToken;

        $res = false;
        if ($userLogModel->create() == false) {
            throw new \Exception(sprintf('insertUserLog记录资金数据异常. userId:%d,ExceptionMsg:%s', $userId, $userLogModel->getMessage()));
        } else {
            $res = true;
            //记录firstp2p_user_log_[0-9]表的最大id到redis
            self::_setUserLogMaxId($userLogModel->id);
        }

        //记录用户资金流水记录
        $userLogModel->remaingLockMoney = $userLogModel->remainingTotalMoney - $userLogModel->remainingMoney;
        Logger::info(implode(' | ', array_merge(array(__FUNCTION__, TASK_APP_NAME, $res), $userLogModel->toArray())));
        //记录本次请求的回溯跟踪流程
        $trace = debug_backtrace();
        $caller1 = isset($trace[1]['function']) ? basename($trace[0]['file']) . '/' . $trace[1]['function'] . ':' . $trace[0]['line'] : '';
        $caller2 = isset($trace[2]['function']) ? basename($trace[1]['file']) . '/' . $trace[2]['function'] . ':' . $trace[1]['line'] : '';
        PaymentApi::log("ChangeMoney. {$caller1}, {$caller2}, userLog:" . json_encode($userLogModel->toArray(), JSON_UNESCAPED_UNICODE));

        return $res;
    }

    /**
     * 根据site_id和其他条件获取user
     * @param int $site_id
     * @param int $params
     * @return
     */
    public static function getAllUserBySiteId($site_id, $params = array(), Pageable $pageable, $isCamelField = true)
    {
        Assert::notEmpty($site_id, 'site_id不能为空');
        extract($params);

        if ($params['app_type']) {
            $where = 'siteId = :site_id:';
            $bind = array('site_id' => intval($site_id));
        } else {
            $where = '1 = 1 ';
            $bind = array();
        }

        if (!empty($id)) {
            $where .= ' AND id = :id:'; //查询条件
            $bind['id'] = intval($id);
        }
        if (!empty($real_name)) {       //查询条件
            $where .= ' AND realName = :real_name:';
            $bind['real_name'] = trim($real_name);
        }
        if (!empty($mobile)) {          //查询条件
            $where .= ' AND mobile = :mobile:';
            $bind['mobile'] = DBDes::encryptOneValue(trim($mobile));
        }
        if (!empty($regist_start)) {    //查询条件
            $where .= ' AND createTime >= :createTimeStart:';
            $bind['createTimeStart'] = $regist_start - 8 * 3600;
        }
        if (!empty($regist_end)) {       //查询条件
            $where .= ' AND createTime <= :createTimeEnd:';
            $bind['createTimeEnd'] = $regist_end - 8 * 3600;
        }

        if (!empty($user_name)) {
            $where .= ' AND userName = :user_name:';
            $bind['user_name'] = trim($user_name);
        }
        if (!empty($idno)) {
            $where .= ' AND idno = :idno:';
            $bind['idno'] = strtoupper(trim($idno));//身份证号采用加密存储，统一使用大写的X后缀
        }
        if (!empty($invite_code)) {
            $where .= ' AND inviteCode = :invite_code:';
            $bind['invite_code'] = trim($invite_code);
        }
        if (!empty($bankcard)) {
            $bankuser = Firstp2pUserBankcard::query()->columns('GROUP_CONCAT(DISTINCT userId) AS user_id_list')->where('bankcard = :bankcard:')->bind(array('bankcard' => $bankcard))->execute();
            if (!empty($bankuser[0]['user_id_list'])) {
                $where .= ' AND id IN ('.$bankuser[0]['user_id_list'].')';
            }
        }

       $res = Firstp2pUser::query()->where($where)->bind($bind);
        if ($pageable) {
            $total = $res->columns(array('id'))->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $res->limit($limit, $offset);
        }

        $list = $res->columns(array('id', 'realName', 'mobile', 'idno', 'createTime'))->order('id DESC')->execute();
        $list = self::addUserBidTagInfo(empty($list) ? array() : $list->toArray());
        if (empty($isCamelField)) {
            foreach($list as &$value) {
                $new_value = array();
                foreach ($value as $key => $v) {
                    $key = strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $key));
                    $new_value[$key]  = $v;
                }
                $value = $new_value;
            }
        }

        if ($pageable) {
            $ret['list'] = $list;
            $ret['total'] = $total;
            $ret['pageNo'] = $pageable->getPageNo();
            $ret['pageSize'] = $pageable->getPageSize();
            return $ret;
        }

        return $list;
    }

    public static function addUserBidTagInfo($userList) {
        if (empty($userList)) {
            return $userList;
        }

        $userIds = array();
        foreach ($userList as &$userItem) {
            $userItem['mobile'] = DBDes::decryptOneValue($userItem['mobile']);
            $userItem['idno'] = DBDes::decryptOneValue($userItem['idno']);
            $userItem['createTime'] = $userItem['createTime'] + 8 * 3600;
            $userIds[] = $userItem['id'];
        }

        $tagInfo = Firstp2pUserTag::find(array('conditions' => "status = 1 AND constName IN('BID_ONE', 'BID_MORE')"));
        $tagInfo = empty($tagInfo) ? array() : $tagInfo->toArray();
        $tagIds = array();
        foreach ($tagInfo as $tagItem) {
            $tagIds[$tagItem['id']] = $tagItem['constName'];
        }
        if (empty($tagIds)) {
            return $userList;
        }

        $result = Firstp2pUserTagRelation::find(array('conditions' => sprintf('uid IN (%s) AND tagId IN(%s)', implode(', ', $userIds), implode(', ', array_keys($tagIds)))));
        $result = empty($result) ? array() : $result->toArray();
        $tagRelation = array();
        foreach ($result as $item) {
            $tagRelation[$item['uid']][] = $tagIds[$item['tagId']];
        }

        foreach ($userList as &$userItem) {
            if (isset($tagRelation[$userItem['id']])) {
                $userItem['tagInfo'] = $tagRelation[$userItem['id']];
            }
        }

        return $userList;
    }

    /**
     * update user
     * @param int $user_id
     * @param int $site_id
     * @param array $params
     * @return boolean
     */
    public static function updateUserInfo($user_id = 0, $site_id = 0, $params = array())
    {
        Assert::notEmpty($user_id, 'user_id不能为空');
        Assert::notEmpty($site_id, 'site_id不能为空');
        Assert::notEmpty($params, 'params不能为空');
        $cond = array(
            'conditions' => 'id = ?0 AND siteId = ?1',
            'bind' => array(intval($user_id), intval($site_id))
        );
        $count = Firstp2pUser::count($cond);
        if (intval($count) > 0) {
            require_once(APP_ROOT_PATH . 'system/libs/user.php');
            $res = save_user($params, 'UPDATE', 0, true);
            return $res;
        }
        return false;
    }

    /**
     * update user
     * @param int $user_id
     * @param int $site_id
     * @param array $params
     * @return boolean
     */
    public static function updateUser($user_id = 0, $site_id = 0, $params = array())
    {
        Assert::notEmpty($user_id, 'user_id不能为空');
        Assert::notEmpty($site_id, 'site_id不能为空');
        Assert::notEmpty($params, 'params不能为空');
        $cond = array(
                'conditions' => 'id = ? AND site_id = ?',
                'bind' => array(intval($user_id), intval($site_id))
                );
        $userModel=new Firstp2pUser();
        $db = getDI()->get('firstp2p');
        $db->update($userModel->getSource(),array_keys($params),array_values($params),$cond);
        return $db->affectedRows();
    }


    /**
     * 记录firstp2p_user_log_[0-9]表的最大id到redis
     * @param int $user_log_id
     * @see http://doc.redisfans.com/string/set.html
     */
    private static function _setUserLogMaxId($userLogId)
    {
        getDI()->get('redis')->set('max_user_log_id', $userLogId, array('nx', 'ex' => DateTimeLib::PTP_DB_STEP_TIME*3*30));
    }

    /**
     * 获取GMTime
     */
    public static function getGmtime()
    {
        return (time() - date('Z'));
    }

    public static function getUserList($params = array(), Pageable $pageable)
    {
        extract($params);
        $where = sprintf(' FROM firstp2p_user AS u JOIN firstp2p_coupon_bind AS b ON b.refer_user_id = %d AND u.id = b.user_id WHERE 1 = 1', $refer_user_id);

        if (!empty($id)) {
            $where .= sprintf(' AND u.id = %d', intval($id));
        }

        $real_name = trim($real_name);
        if (!empty($real_name)) {
            $where .= sprintf(' AND u.real_name = "%s"', mysql_escape_string($real_name));
        }

        if (!empty($mobile)) {
            $where .= sprintf(' AND u.mobile = "%s"', DBDes::encryptOneValue(trim($mobile)));
        }

        if (!empty($regist_start)) {
            $where .= sprintf(' AND u.create_time >= %d', $regist_start - 8 * 3600);
        }

        if (!empty($regist_end)) {
            $where .= sprintf(' AND u.create_time <= "%d"', $regist_end - 8 * 3600);
        }

        //计算count
        $db = getDI()->get('firstp2p_r');
        $res = $db->fetchOne('SELECT COUNT(u.id) AS total ' . $where, \Phalcon\Db::FETCH_ASSOC);
        $total = intval($res['total']);

        //计算列表
        $pageNo = $pageable->getPageNo() < 1 ? 1 : $pageable->getPageNo();
        $pageSize = $pageable->getPageSize() < 1 ? 10 : $pageable->getPageSize();
        $offset = ($pageNo - 1) * $pageSize;

        $where .= ' ORDER BY u.id DESC';
        $sql = "SELECT u.id, u.real_name AS realName, u.mobile, u.idno, u.create_time AS createTime " . $where . sprintf(' LIMIT %d,%d', $offset, $pageSize);
        $list = $db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);

        //添加tag
        $list = self::formatUserList($list);

        //返回
        return array(
             'list'     => $list,
             'total'    => $total,
             'pageNo'   => $pageNo,
             'pageSize' => $pageSize,
        );

    }

    public static function formatUserList($userList)
    {
        if (!empty($userList)) {
            foreach ($userList as &$userItem) {
                $userItem['mobile'] = DBDes::decryptOneValue($userItem['mobile']);
                $userItem['idno'] = DBDes::decryptOneValue($userItem['idno']);
                $userItem['createTime'] = $userItem['createTime'] + 8 * 3600;
            }
        }

        return $userList;
    }


    /**
     * @根据用户手机号获取用户id
     * @param $mobile
     * @return $uid
     */
    public static function getUidByMobile($mobile)
    {
        $mobile = DBDes::encryptOneValue(trim($mobile));
        $conditons = 'mobile = :mobile:';
        $parameters = [
            'mobile' => $mobile
        ];
        $userInfo = Firstp2pUser::findFirst(array(
            $conditons,
            'bind' => $parameters,
            'columns' => 'id'
         ));
        $uid = $userInfo->id;
        return $uid;
     }

    public static function getUserListByParams ($params = array())
    {
        $where = " where 1 = 1 ";

        if (!empty($params['userId'])) {
            $where .= " AND id = {$params['userId']} ";
        }

        if (!empty($params['userName'])) {
            $params['userName'] = mysql_escape_string(trim($params['userName']));
            $where .= " AND real_name = '{$params['userName']}' ";
        }

        if (!empty($params['mobile'])) {
            $where .= sprintf(' AND mobile = "%s"', DBDes::encryptOneValue(trim($params['mobile'])));
        }

        if (!empty($params['inviteCode'])) {
            $where .= " AND invite_code = '{$params['inviteCode']}' ";
        }

        if (!empty($params['stime'])) {
            $stime = $params['stime'] - 8 * 3600;
            $where .= " AND create_time >= {$stime} ";
        }

        if (!empty($params['etime'])) {
            $etime = $params['etime'] - 8 * 3600;
            $where .= " AND create_time <= {$etime} ";
        }

        //计算count
        $db = getDI()->get('firstp2p_r');
        $res = $db->fetchOne('SELECT COUNT(id) AS total FROM firstp2p_user ' . $where, \Phalcon\Db::FETCH_ASSOC);
        $total = intval($res['total']);

        //计算列表
        $pageNo = isset($params['pageNo']) ? $params['pageNo'] : 1;
        $pageSize = isset($params['pageSize']) ? $params['pageSize'] : 10;
        $offset = ($pageNo - 1) * $pageSize;

        $where .= ' ORDER BY id DESC';
        $sql = "SELECT id,real_name AS realName,invite_code AS inviteCode,
                mobile,idno,create_time AS createTime FROM firstp2p_user "
                . $where . sprintf(' LIMIT %d,%d', $offset, $pageSize);
        $list = $db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);

        //添加tag
        $list = self::formatUserList($list);

        //返回
        return array(
             'list'     => $list,
             'total'    => $total,
             'pageNo'   => $pageNo,
             'pageSize' => $pageSize,
        );

    }

    /**
     * 生成业务token
     */
    private static function generateBizToken($bizToken) {
        if (empty($bizToken)) {
            return '';
        }
        if (is_string($bizToken)) {
            return $bizToken;
        }

        foreach($bizToken as $key => $value) {
            $bizToken[$key] = strval($value);
        }

        return json_encode($bizToken);
    }

    public static function getUserInfoByIds($uids) {
        if (empty($uids)) {
            return [];
        }

        $return = [];
        $uids = is_array($uids) ? implode(",", $uids) : $uids;

        $query = sprintf("SELECT id, mobile, create_time FROM firstp2p_user WHERE id IN (%s)", $uids);
        $queryRes = getDI()->get('firstp2p_r')->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        foreach ($queryRes as $item) {
            $item['mobile'] = DBDes::decryptOneValue($item['mobile']);
            $return[$item['id']] = $item;
        }

        return $return;
    }

}
