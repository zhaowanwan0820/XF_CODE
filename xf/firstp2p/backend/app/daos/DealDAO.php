<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\models\Firstp2pDeal;
use NCFGroup\Ptp\models\Firstp2pDealExt;
use NCFGroup\Ptp\models\Firstp2pDealProject;
use NCFGroup\Ptp\models\Firstp2pDealLoanType;
use NCFGroup\Ptp\models\Firstp2pDealCompound;
class DealDAO
{
    const DETAIL_COLUMNS = 'id, name, dealTagName, repayTime, loantype, borrowAmount, minLoanMoney, rate, loadMoney, projectId, de.incomeBaseRate';

    /**
     * getOnlineDeals
     * 获取当前在投有效标: 只获取进行中与满标的标
     *
     * @param Pageable $pageable
     * @static
     * @access public
     * @return void
     */
    public static function getOnlineDeals(Pageable $pageable = null)
    {
        $detailColumns = self::DETAIL_COLUMNS;
        if ($pageable) {
            $dealStatus = array(1,2); // 只取进行中与满标
        } else {
            $dealStatus = array(1,2,4); // 用户在投客户包括还款中
        }
        $builder = Firstp2pDeal::query()
            ->columns($detailColumns)
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealExt', 'NCFGroup\Ptp\models\Firstp2pDeal.id = de.dealId', 'de')
            ->inWhere('dealStatus', $dealStatus)
            ->andWhere('parentId != 0 AND isEffect = 1 AND isDelete = 0 AND publishWait = 0')
            ->orderBy("field(dealStatus, 1, 2, 4)")
            ->orderBy("repayStartTime DESC")
            ->orderBy("successTime DESC")
            ->orderBy("startTime DESC")
            ->orderBy("createTime DESC")
            ->orderBy("updateTime DESC");
        if ($pageable) {
            $total = $builder->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $deals = $builder->execute()->toArray();
        $list = array();
        foreach ($deals as $deal) {
            $list[$deal['id']] = $deal; //->toArray();
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
     * getWaitingDeals
     * 待上线标列表
     *
     * @static
     * @access public
     * @return void
     */
    public static function getWaitingDeals(Pageable $pageable = null)
    {
        $detailColumns = self::DETAIL_COLUMNS;
        $builder = Firstp2pDeal::query()
            ->columns($detailColumns)
            ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealExt', 'NCFGroup\Ptp\models\Firstp2pDeal.id = de.dealId', 'de')
            ->andWhere('dealStatus = 0')
            ->andWhere('parentId != 0 AND isEffect = 1 AND isDelete = 0 AND publishWait = 0')
            ->orderBy("id DESC");
        if ($pageable) {
            $total = $builder->execute()->count();
            $offset = ($pageable->getPageNo() - 1) * $pageable->getPageSize();
            $limit = $pageable->getPageSize();
            $builder->limit($limit, $offset);
        }
        $deals = $builder->execute()->toArray();
        $list = array();
        foreach ($deals as $deal) {
            $list[$deal['id']] = $deal; //->toArray();
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
     * getProject
     * 获取项目信息
     *
     * @param mixed $id
     * @static
     * @access public
     * @return void
     */
    public static function getProject($id)
    {
        $projectObj = Firstp2pDealProject::findFirstById($id);
        return $projectObj;
    }



    /**
     * getUseableDeals
     * 获取当前可投的标的
     * @static
     * @access public
     * @return void
     */
    public static function getUseableDeals(){
        $columns = array(
            'd.id',
            'd.name',
            'd.dealTagName',
            'd.repayTime',
            'd.loantype',
            'd.borrowAmount',
            'd.minLoanMoney',
            'd.incomeFeeRate',
            'd.loadMoney',
            'd.typeId',
            'd.dealType',
            'ds.siteId',
            'dlt.name AS dealTypeText',
        ); 
        $enableSiteId = app_conf('DEAL_SITE_ALLOW');
        $deals = getDI()->getModelsManager()->createBuilder() 
                ->columns(implode(',', $columns))
                ->from(array('d' => 'NCFGroup\Ptp\models\Firstp2pDeal'))
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanType', 'd.typeId=dlt.id', 'dlt')
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealSite', 'd.id=ds.dealId', 'ds')
                ->where('d.dealStatus = :dealStatus:',array('dealStatus'=>1))
                ->andWhere('d.parentId != 0 AND d.isEffect = 1 AND d.isDelete = 0 AND publishWait = 0')
                ->andWhere('ds.siteId IN ('.$enableSiteId.')')
                ->orderBy('FIELD(d.dealStatus, 1,0,2,4,5,3), d.repayStartTime DESC, d.successTime DESC, d.startTime DESC, d.createTime DESC, d.updateTime DESC, d.sort DESC, d.id DESC')
                ->limit(10,0)
                ->getQuery()->execute()->toArray();
        return $deals;
    }

       /**
     * getDealDetailsById
     * 获取当前可投的标的
     * @static
     * @access public
     * @return void
     */
    public static function getDealDetailsById($id){
        $columns = array(
            'd.id',
            'd.name',
            'd.dealTagName',
            'd.repayTime',
            'd.loantype',
            'd.borrowAmount',
            'd.minLoanMoney',
            'd.incomeFeeRate',
            'd.loadMoney',
            'd.typeId',
            'dlt.name AS dealTypeText',
        );
        $deal = getDI()->getModelsManager()->createBuilder()
                ->columns(implode(',', $columns))
                ->from(array('d' => 'NCFGroup\Ptp\models\Firstp2pDeal'))
                ->leftJoin('NCFGroup\Ptp\models\Firstp2pDealLoanType', 'd.typeId=dlt.id', 'dlt')
                ->where('d.id = :id:',array('id'=>$id))
                ->orderBy('d.createTime DESC')
                ->getQuery()->execute()->getFirst()->toArray();
        return $deal;
    }

    public static function getDealExt($id){
        $columns = array(
            'dc.lockPeriod',
            'dc.redemptionPeriod',
        );
        $dealExt = getDI()->getModelsManager()->createBuilder()
                   ->columns(implode(',', $columns))
                   ->from(array('dc' => 'NCFGroup\Ptp\models\Firstp2pDealCompound'))
                   ->where('dc.dealId = :id:',array('id'=>$id))
                   ->getQuery()->execute()->getFirst()->toArray();
        return $dealExt;
    }
    /**
     * update deal
     * @param int $user_id
     * @param int $site_id
     * @param array $params
     * @return boolean
     */
    public static function updateDealInfo($id, $params = array())
    {
        Assert::notEmpty($id, 'id不能为空');
        $dealModel = new Firstp2pDeal();
        $db = getDI()->get('firstp2p');
        $cond = array(
            'conditions' => 'id = ?',
            'bind' => array(intval($id))
        );
        $db->update($dealModel->getSource(), array_keys($params), array_values($params), $cond);
        if ($db->affectedRows() == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据项目 id，获取所有的标 id
     *
     * @params  int $project_id
     * @return array
     */
    public static function getDealIdsByProjectId($project_id)
    {
        $params = array(
            'conditions' => sprintf('projectId = %d', $project_id),
            'columns' => 'id',
        );
        $deal_id_arr = array();
        foreach (Firstp2pDeal::find($params) as $deal_obj) {
            $deal_id_arr[] = $deal_obj->id;
        }

        return $deal_id_arr;
    }
}

