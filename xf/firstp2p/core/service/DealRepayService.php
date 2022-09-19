<?php
/**
 * DealRepayService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealExtModel;
use core\dao\DealRepayModel;
use core\dao\DealPrepayModel;
use core\dao\DealModel;
use core\dao\DealRepayOplogModel;
use core\dao\PartialRepayModel;
use core\dao\UserModel;
use core\service\UserCarryService;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;


/**
 * 订单还款计划
 *
 * Class DealRepayService
 * @package core\service
 */
class DealRepayService extends BaseService {

    /**
     * 根据订单id获取订单还款计划列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealRepayListByDealId($dealId) {
         return DealRepayModel::instance()->getDealRepayListByDealId($dealId);
    }

    /**
     * 获取逾期的借款列表
     * @return array
     */
    public function getDelayRepayList() {
        return DealRepayModel::instance()->getDelayDealList();
    }

    /**
     * 获取逾期的借款数目
     * @return int
     */
    public function getDelayRepayCount() {
        return DealRepayModel::instance()->getDelayDealCount();
    }

    /**
     * 根据订单id与用户id获取还款信息
     * @param int $deal_id
     * @param int $user_id
     * @return array
     */
    public function getDealRepayInfo($deal_id, $user_id) {
        if (!$deal_id) {
            return false;
        }
        $deal = DealModel::instance()->find($deal_id);
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal_id);
        if ($deal['user_id'] != $user_id || $deal['deal_status'] != 4) {
            return false;
        }
        $repay_list = $this->getDealRepayListByDealId($deal_id);
        foreach ($repay_list as $k => $v) {
            $repay_list[$k]['can_repay'] = $v->canRepay();
            $repay_list[$k]['fee_of_overdue'] = $v->feeOfOverdue();
            $repay_list[$k]['printerest'] = $v['principal'] + $v['interest'];
        }
        $applied_prepay = $deal->isAppliedPrepay();
        $overdue = $deal->isOverdue();
        $cannot_prepay = !$deal->canPrepay();

        $deal['remain_repay_money'] = $deal->remainRepayMoney();
        $deal['loantype_name'] = $deal->getLoantypeName();
        $deal['total_repay_money'] = $deal->totalRepayMoney();
        return array("deal"=>$deal, "repay_list"=>$repay_list, "applied_prepay"=>$applied_prepay, "overdue"=>$overdue, "cannot_prepay"=>$cannot_prepay,'deal_ext'=>$deal_ext);
    }


    /**
     * 执行还款
     *
     * @param $deal_repay_id 还款计划ID
     * @param $ignore_impose_money
     * @param $negative 0 不可扣负 1 可扣负
     * @return mixed
     * @throws \Exception
     */
    public function repay($deal_repay_id, $ignore_impose_money, $admin = array(),$negative=1,$repayType=0, $submitUid = 0, $auditType = 0, $orderId = '') {
        $deal_repay_id = intval($deal_repay_id);
        if (empty($deal_repay_id)) {
            throw new \Exception("参数错误");
        }
        $deal_repay_model = new DealRepayModel();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        if (empty($deal_repay)) {
            throw new \Exception("获取还款计划失败[$deal_repay_id]");
        }
        $userCarryService = new UserCarryService();

        $deal = DealModel::instance()->find($deal_repay['deal_id']);

        if($repayType == 1){//代垫
            if($deal['advance_agency_id'] > 0){
                $dealService = new DealService();
                $userModel = new UserModel();
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],1);
                $user = $userModel->find($advanceAgencyUserId);
                $deal_repay['user_id'] = $user['id'];
            }else{
                throw new \Exception('还款失败,未设置代垫机构!');
            }
        } elseif ($repayType == 2){//代偿
            if($deal['agency_id'] > 0){//担保机构代偿
                $dealService = new DealService();
                $userModel = new UserModel();
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                $user = $userModel->find($advanceAgencyUserId);
                $deal_repay['user_id'] = $user['id'];
            }else{
                throw new \Exception('还款失败,未设置代偿机构!');
            }
        }

        $totalRepayMoney = 0.00;
        $rs = $deal_repay->repay($ignore_impose_money, $totalRepayMoney,$negative,$repayType, $orderId);

        if($rs === false){
            throw new \Exception("还款失败[$deal_repay_id]");
        }else if($rs === 2){
            return true;

        }

        $rs = $userCarryService->updateWithdrawLimitAfterRepalyMoney($deal_repay['id'],$deal_repay['repay_money']);
        if($rs === false){
            throw new \Exception("更新金额限制失败[".$deal_repay['user_id']."]");
        }

        $dealModel = new DealModel();
        $deal = $dealModel->find($deal_repay['deal_id']);
        $dealService = new DealService();
        $isND = $dealService->isDealND($deal['id']);
        if ($isND) {
            $this->addNdDealRepayOplog($deal_repay,$deal,$admin,$submitUid,$auditType);
        } else {
            //添加还款操作记录
            $repayOpLog = new DealRepayOplogModel();
            $repayOpLog->operation_type = DealRepayOplogModel::REPAY_TYPE_NORMAL;//正常还款
            $repayOpLog->operation_time = get_gmtime();
            $repayOpLog->operation_status = 1;
            $repayOpLog->operator = $admin['adm_name'];
            $repayOpLog->operator_id = $admin['adm_id'];
            //标的信息
            $repayOpLog->deal_id = $deal['id'];
            $repayOpLog->deal_name = $deal['name'];
            $repayOpLog->borrow_amount = $deal['borrow_amount'];
            $repayOpLog->rate = $deal['rate'];
            $repayOpLog->loantype = $deal['loantype'];
            $repayOpLog->repay_period = $deal['repay_time'];
            $repayOpLog->user_id = $deal['user_id'];

            //存管&&还款方式
            $repayOpLog->repay_type = $repayType;
            $repayOpLog->report_status = $deal['report_status'];

            //还款的信息
            $repayOpLog->deal_repay_id = $deal_repay['id'];
            $repayOpLog->repay_money = $totalRepayMoney;
            $repayOpLog->real_repay_time = get_gmtime();
            $repayOpLog->submit_uid = intval($submitUid);
            $repayOpLog->audit_type= intval($auditType);
            $repayOpLog->save();
        }

        return true;
    }

    /**
     * 添加农担贷还款操作记录
     * @param $deal_repay
     * @param $deal
     */
    public function addNdDealRepayOplog($deal_repay,$deal,$admin,$submitUid,$auditType) {
        $partialRepayModel = new PartialRepayModel();
        $borrowerRepayMoney = $partialRepayModel->getRepayMoney($deal_repay['id'],PartialRepayModel::REPAY_TYPE_BORROWER);
        $compensatoryRepayMoney = $partialRepayModel->getRepayMoney($deal_repay['id'],PartialRepayModel::REPAY_TYPE_COMPENSATORY);
        $logArray=array();
        if((bccomp($borrowerRepayMoney,'0.00',2) ==1) &&(bccomp($compensatoryRepayMoney,'0.00',2) ==1)) {
            $logArray[] = array('repay_money'=>$borrowerRepayMoney,'repay_type'=>DealRepayModel::DEAL_REPAY_TYPE_PART_SELF);
            $logArray[] = array('repay_money'=>$compensatoryRepayMoney,'repay_type'=>DealRepayModel::DEAL_REPAY_TYPE_PART_DAICHANG);
        } else {
            if(bccomp($borrowerRepayMoney,'0.00',2) ==1) {
                $logArray[] = array('repay_money'=>$borrowerRepayMoney,'repay_type'=>DealRepayModel::DEAL_REPAY_TYPE_SELF);
            } else {
                $logArray[] = array('repay_money'=>$compensatoryRepayMoney,'repay_type'=>DealRepayModel::DEAL_REPAY_TYPE_DAICHANG);
            }
        }
        foreach ($logArray as $logInfo) {
            //添加还款操作记录
            $repayOpLog = new DealRepayOplogModel();
            $repayOpLog->operation_type = DealRepayOplogModel::REPAY_TYPE_NORMAL;//正常还款
            $repayOpLog->operation_time = get_gmtime();
            $repayOpLog->operation_status = 1;
            $repayOpLog->operator = $admin['adm_name'];
            $repayOpLog->operator_id = $admin['adm_id'];
            //标的信息
            $repayOpLog->deal_id = $deal['id'];
            $repayOpLog->deal_name = $deal['name'];
            $repayOpLog->borrow_amount = $deal['borrow_amount'];
            $repayOpLog->rate = $deal['rate'];
            $repayOpLog->loantype = $deal['loantype'];
            $repayOpLog->repay_period = $deal['repay_time'];
            $repayOpLog->user_id = $deal['user_id'];

            //存管&&还款方式
            $repayOpLog->repay_type = $logInfo['repay_type'];
            $repayOpLog->report_status = $deal['report_status'];

            //还款的信息
            $repayOpLog->deal_repay_id = $deal_repay['id'];
            $repayOpLog->repay_money = $logInfo['repay_money'];
            $repayOpLog->real_repay_time = get_gmtime();
            $repayOpLog->submit_uid = intval($submitUid);
            $repayOpLog->audit_type= intval($auditType);
            $repayOpLog->save();
        }

        return true;
    }


    public function getDealRepayListDurTimeV2($time,$ps,$pn){
        $day = date("Y-m-d",$time);
        $res = array();
        $offset = ($pn-1)*$ps;
        $limit = $ps;


        $res['count'] = DealRepayOplogModel::instance()->getRepayDetailCountByDay($day);
        $totalPages = ceil($res['count']/$ps);
        if($pn > $totalPages){
            $pn = $totalPages;
        }

        $ret = DealRepayOplogModel::instance()->getRepayDetailByDay($day,$offset,$limit);
        // 万恶的特殊需求 ，不想在还款公告里面展现 ；对应ID：60286；60524；60525 的逾期还款
        $noShowsIds = explode(',', app_conf('DEAL_ID_FORBIDDEN_REPAY'));
        $res['list'] = array();
        foreach($ret as $one){
            if( in_array(intval($one['id']),$noShowsIds) ){
                continue;
            }

            $tmp = array();
            $tmp['deal_id'] = $one['deal_id'];
            $tmp['name'] = $one['deal_name'];
            if($one['operation_type'] == 2){
                $tmp['type'] = '提前还款';
            }else{
                $tmp['type'] = '正常还款';
            }
            $res['list'][] = $tmp;
        }

        return $res;
    }

    /**
    * 根据传入的日期获取当天所有标的还款情况
    */
    public function getDealRepayListDurTime($time){
        $res = array();
        if(empty($time)){
            return array();
        }
        $start = to_timespan(date('Y-m-d',$time));
        $end = $start + 86399;
        //$ret = DealRepayModel::instance()->getRepayListDurTime($start,$end);
        $ret = DealModel::instance()->getRepayDoneDealsDurTimes($start,$end);
        $deals = array();
        $dealIds = array();
        foreach($ret as $one){
            $dealIds[] = $one['id'];
        }
        if(empty($dealIds)){
            return array();
        }
        $repayInfos = DealRepayModel::instance()->getRepayListByDealIds($dealIds);
        $repayDetails = array();
        foreach($repayInfos as $repayInfo){
            $dealId = $repayInfo['deal_id'];
            if(!isset($repayDetails[$dealId]) || !is_array($repayDetails[$dealId])){
                $repayDetails[$dealId] = array('real_amount'=>0,'interest'=>0);
            }
            if(intval($repayInfo['status']) != 4){
                $repayDetails[$dealId]['interest'] = bcadd($repayDetails[$dealId]['interest'],
                            bcadd($repayInfo['impose_money'],$repayInfo['interest'], 2), 2);

                $repayDetails[$dealId]['real_amount'] = bcadd(
                            $repayDetails[$dealId]['real_amount'],
                            bcadd($repayInfo['principal'],$repayDetails[$dealId]['interest'], 2), 2);
                if(intval($repayInfo['status'])==1){
                    $repayDetails[$dealId]['type'] = '正常还款';
                }else{
                    $repayDetails[$dealId]['type'] = '逾期还款';
                }
            }else{
                // 提前还款
                $preRepay = DealPrepayModel::instance()->findBy("`deal_id`='{$dealId}'","*",array(),true);

                $repayDetails[$dealId]['real_amount'] = bcadd(
                            $repayDetails[$dealId]['real_amount'],
                            bcadd($preRepay['prepay_money'], $preRepay['prepay_interest'], 2), 2);
                $repayDetails[$dealId]['interest'] = bcadd(
                            $repayDetails[$dealId]['interest'],
                            bcadd($preRepay['prepay_interest'], $preRepay['prepay_compensation'], 2), 2);
                $repayDetails[$dealId]['type'] = '提前还款';
            }
        }


        // 万恶的特殊需求 ，不想在还款公告里面展现 ；对应ID：60286；60524；60525 的逾期还款
        $noShowsIds = explode(',', app_conf('DEAL_ID_FORBIDDEN_REPAY'));
        foreach($ret as $one){
            if( in_array(intval($one['id']),$noShowsIds) ){
                continue;
            }

            $tmp = array();
            $tmp['deal_id'] = $one['id'];
            $tmp['borrow_amount']=$one['borrow_amount'];
            $tmp['name'] = $one['name'];
            $tmp['real_amount'] = $repayDetails[$one['id']]['real_amount'];
            $tmp['interest'] = $repayDetails[$one['id']]['interest'];
            $tmp['type'] = $repayDetails[$one['id']]['type'];
            $res[] = $tmp;
        }
        return $res;
    }

    public function getRepayDealList( $ps, $pn ){
        $res = array();
        $offset = ($pn-1)*$ps;
        $limit = $ps;
        $ret = DealModel::instance()->getRepayDays($offset,$limit);
        foreach($ret as $one){
            $tmp = array();
            $realRepayCount = $one['count'];
            $time = $one['ymd'];
            $tmp['time'] = $one['ymd'];
            $tmp['time_readable'] = date('Y-m-d',strtotime($one['ymd']));
            $tmp['fake_create_time'] = date('Y-m-d',strtotime($one['ymd'])+86400);
            $repays = $this->calcRepayCountByTime($time,$realRepayCount);
            $tmp['normal_count'] = $repays['normal'];
            $tmp['delay_count'] = $repays['delay'];
            $preRepays = $this->calcPrepayCountByTime($time);
            $tmp['pre_count'] = $preRepays;
            $tmp['all'] = $tmp['normal_count']+$tmp['delay_count']+$tmp['pre_count'];
            $res['list'][] = $tmp;
        }
        //分页使用
        $res['count'] = DealModel::instance()->getRepayDaysCount();
        return $res;
    }

    /*
    * 新版还款公告，利用还款操作表
    */
    public function getRepayDealListV2( $ps, $pn , $is_firstp2p=false ){
        $res = array();
        $res['list'] = array();
        $offset = ($pn-1)*$ps;
        $limit = $ps;
        $daysInfo = DealRepayOplogModel::instance()->getRepayDays($offset,$limit,$is_firstp2p);
        // 如果不为空才进行后续逻辑
        if( !empty($daysInfo) ){
            $tmp = array();
            foreach($daysInfo as $day){
                $tmp[] = sprintf("'%s'",$day['ymd']);
            }
            $days = implode(",", $tmp);
            $daysDetail = DealRepayOplogModel::instance()->getRepayDaysDetail($days,$is_firstp2p);
            foreach($daysDetail as $one){
                if(!isset($res['list'][$one['ymd']])){
                    $res['list'][$one['ymd']] = array();
                }

                $res['list'][$one['ymd']]['time_readable'] = $one['ymd'];
                $res['list'][$one['ymd']]['time'] = str_replace('-','', $one['ymd']);
                $res['list'][$one['ymd']]['fake_create_time'] = date('Y-m-d',strtotime($one['ymd'])+86400);
                if( !isset($res['list'][$one['ymd']]['pre_count']) )
                    $res['list'][$one['ymd']]['pre_count']=0;
                if( !isset($res['list'][$one['ymd']]['normal_count']) )
                    $res['list'][$one['ymd']]['normal_count']=0;
                if($one['operation_type'] == 2){
                    $res['list'][$one['ymd']]['pre_count'] = intval($one['count']);
                }else{
                    $res['list'][$one['ymd']]['normal_count'] = intval($one['count']);
                }
                $res['list'][$one['ymd']]['delay_count'] = 0;
                $res['list'][$one['ymd']]['all'] = $res['list'][$one['ymd']]['normal_count'] + $res['list'][$one['ymd']]['pre_count'];
            }
        }
        //分页使用
       // $res['count'] = DealRepayOplogModel::instance()->getRepayDaysCount($is_firstp2p);
        $res['count']  = 20;
        return $res;
    }

    /*
    * 还款公告日历函数
     * $type (0:首页,1:列表页)
    */
    public function getRepayDaysV2($ps,$pn, $is_firstp2p=false){
        $res = array();
        $limit = intval($ps);
        $offset = intval(($pn-1)*$ps);
        $ret = DealRepayOplogModel::instance()->getRepayDaysSite($is_firstp2p);
        foreach($ret as $one){
            $tmp['time_readable'] = $one['ymd'];
            $tmp['time'] = str_replace('-','', $one['ymd']);
            $res['list'][] = $tmp;
        }
        return $res;

    }


    /*
    * 计算每天有多少笔还款
    */
    private function calcRepayCountByTime($time,$realRepayCount){
        $start = to_timespan($time);
        $end = $start + 86399;
        $normalRepays = DealRepayModel::instance()->getRepaysByTime($start,$end);
        $normalRepaysTmp = array();
        // 去重＋改状态;
        //如果不一致，就是说明还款记录中有分期的标的。
        foreach($normalRepays as $one){
            if($normalRepays != $realRepayCount){
                $dealStatus = DealModel::instance()->find($one['deal_id'], "deal_status", true);
                if(intval($dealStatus['deal_status']) != 5){
                    continue;
                }
            }
            if(!isset($normalRepaysTmp[$one['deal_id']])){
                $normalRepaysTmp[$one['deal_id']] = 0;
            }
            if($normalRepaysTmp[$one['deal_id']] < $one['status'])
                $normalRepaysTmp[$one['deal_id']] = $one['status'];
        }
        $ret = array('normal'=>0,'delay'=>0);
        foreach($normalRepaysTmp as $one){
            if(intval($one)==1)
                $ret['normal'] ++;
            else
                $ret['delay'] ++;
        }
        return $ret;
    }

    /*
    * 计算每天有多少笔还款 ++;
    */
    private function calcPrepayCountByTime($time){
        $start = to_timespan($time);
        $end = $start + 86399;
        $preRepays = DealPrepayModel::instance()->getPrepaysByTime($start,$end);
        return $preRepays[0]['deal_count'];
    }

    /**
     * 计算标的的最近一次还款时间（开始计息时间）
     * @param $deal
     * @return mixed
     */
    public function getMaxRepayTimeByDealId($deal) {
        $res = DealRepayModel::instance()->getMaxRepayTimeByDealId($deal['id']);
        if(!$res->repay_time){
            $interest_time = $deal['repay_start_time'];
        }else{
            $interest_time = $res['repay_time'];
        }
        return $interest_time;
    }

    public function getLastRepayTimeByDealId($deal){
        $res = DealRepayModel::instance()->getLastRepayTimeByDealId($deal['id']);
        if(!$res->repay_time){
            $interest_time = $deal['repay_start_time'];
        }else{
            $interest_time = $res['repay_time'];
        }
        return $interest_time;
    }

    /**
     * 到期还款明细汇总
     * @param $deal_id
     * @return mixed
     */
    public function getExpectRepayStat($deal_id) {
        return DealRepayModel::instance()->getExpectRepayStat($deal_id);
    }
    /**
     * 根据标ID获取最后一期还款时间
     * @param $deal_id 标ID
     */
    public function getFinalRepayTimeByDealId($deal_id) {
        return DealRepayModel::instance()->getFinalRepayTimeByDealId($deal_id);
    }

    /**
     * 专享标的按项目还款
     * @param $projectId 项目ID
     * @param $ignoreImposeMoney
     * @param $admin 管理员信息
     * @param $negative 是否可以扣负
     * @param $repayType 还款类型(0:借款人还款,1:代垫还款)
     * @param $submitUid 提交人ID
     * @param $auditType
     * @return mixed
     * @throws \Exception
     */
    public function projectRepay($projectId,$ignoreimposeMoney,$admin,$negative,$repayType,$submitUid,$auditType){

        if(empty($projectId)){
            throw new \Exception("参数错误");
        }

        try {
            $GLOBALS['db']->startTrans();
            $dealReapyModel = new DealRepayModel();
            $projectRepay = $dealReapyModel->projectRepay($projectId,$ignoreimposeMoney,$admin,$negative,$repayType,$submitUid,$auditType);
            if($projectRepay){
                //按标还
                $GLOBALS['db']->commit();
            }else{
                throw new \Exception("还款失败!");
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }

        return true;

        //根据项目ID


    }

    /**
     * 通过标的，汇总项目的还款明细
     * @param int $project_id
     * @return array $repay_collection
     */
    public function getProjectRepayInfo($project_id)
    {
        $sql_deal_ids = sprintf('SELECT `id` FROM %s WHERE `project_id` = %d', DealModel::instance()->tableName(), $project_id);

        $sql_repay = sprintf('SELECT
            SUM(repay_money) as repay_money,
            SUM(loan_fee) as loan_fee,
            SUM(consult_fee) as consult_fee,
            SUM(guarantee_fee) as guarantee_fee,
            SUM(pay_fee) as pay_fee,
            SUM(canal_fee) as canal_fee,
            SUM(management_fee) as management_fee,
            MAX(repay_time) as last_repay_time,
            SUM(principal) as principal,
            SUM(interest) as interest
            FROM `firstp2p_deal_repay` WHERE `status` = 0 AND deal_id IN (%s)', $sql_deal_ids);
        return DealRepayModel::instance()->findBySqlViaSlave($sql_repay);
    }

    /**
     * 放款时根据标的判断还款类型
     * @param $deal
     * @return int
     */
    public function getRepayTypeByDeal($dealId){
        $deal = DealModel::instance()->find($dealId);
        $typeId = $deal['type_id'];
        $typeXFD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XFD);
        $typeDSD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DSD);
        $typeCDT = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XJDCDT);
        $typeGFD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XJDGFD);


       $typeDFD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DFD);
       $typeHDD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_HDD);

        if(in_array($typeId, array($typeXFD, $typeGFD, $typeCDT, $typeDFD, $typeHDD))){
            $repayType = DealRepayModel::DEAL_REPAY_TYPE_DAIKOU;
        }elseif($typeDSD == $typeId){
            $repayType = DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN;
        }else{
            $repayType = DealRepayModel::DEAL_REPAY_TYPE_SELF;
        }
        return $repayType;
    }

    public function getInfoById($repayId)
    {
        return DealRepayModel::instance()->find($repayId);
    }
}
