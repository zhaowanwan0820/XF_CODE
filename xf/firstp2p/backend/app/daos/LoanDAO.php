<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\models\Firstp2pDeal;
use NCFGroup\Ptp\models\Firstp2pDealLoanRepay;
use NCFGroup\Ptp\models\Firstp2pDealLoad;

class LoanDAO
{
    /**
     * getLoansByUserId
     * 获取投资详情列表
     *
     * @param mixed $cfpId
     * @param mixed $userId
     * @param Pageable $pageable
     * @static
     * @access public
     * @return void
     */
    public static function getLoansByUserId($cfpId, $userId, Pageable $pageable = null)
    {
        $columns = array(
            'dl.id AS dealLoanId',
            'd.id',
            'd.name',
            'dl.createTime',
            'd.borrowAmount',
            'd.rate',
            '(de.incomeBaseRate + de.incomeFloatRate) AS incomeBaseRate',
            'dl.money',
            'd.loantype',
        );
        $builder = getDI()->getModelsManager()->createBuilder()
            ->columns(implode(',', $columns))
            ->from(array('dl' => 'NCFGroup\Ptp\models\Firstp2pDealLoad'))
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDeal', 'dl.dealId = d.id', 'd')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealExt', 'dl.dealId = de.dealId', 'de')
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pCouponLog', 'cl.dealLoadId = dl.id', 'cl')
            ->where('dl.userId = :userId: AND cl.referUserId = :cfpId:', array('userId' => $userId, 'cfpId' => $cfpId))
            ->inWhere('d.dealStatus', array(1,2,4,5))
            ->andWhere('d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND d.publishWait = 0')
            ->orderBy("dl.createTime DESC");
        if ($pageable) {
            $total = $builder->getQuery()->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $deals = $builder->getQuery()->execute()->toArray();
        $list = array();
        foreach ($deals as $deal) {
            $list[] = $deal; //->toArray();
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
     * getLatestDueDay
     * 获取最近本金返款到期时间
     *
     * @param mixed $dealLoanId
     * @static
     * @access public
     * @return void
     */
    public static function getLatestDueDay($dealLoanId)
    {
        $result = Firstp2pDealLoanRepay::query()
            ->columns('time, realTime')
            ->where('dealLoanId = :dealLoanId: AND type = 1 AND money > 0', array('dealLoanId' => $dealLoanId))
            ->orderBy('time')
            ->execute()
            ->getFirst();
        if (empty($result)) {
            return '-';
        }
        if ($result->realTime > 0) {
            $date = date('Y-m-d', $result->realTime + 3600 * 8);
        } else {
            $date = date('Y-m-d', $result->time + 3600 * 8);
        }
        return $date;
    }
}

