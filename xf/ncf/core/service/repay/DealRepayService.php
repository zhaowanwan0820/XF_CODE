<?php
namespace core\service\repay;

use core\dao\deal\DealAgencyModel;
use core\dao\jobs\JobsModel;
use core\dao\repay\PartialRepayModel;
use core\enum\DealEnum;
use core\enum\PartialRepayEnum;
use libs\utils\Logger;
use core\enum\DealLoanTypeEnum;
use core\enum\DealRepayEnum;
use core\enum\DealRepayOpLogEnum;
use core\enum\JobsEnum;
use core\service\BaseService;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\repay\DealRepayModel;
use core\dao\repay\DealRepayOplogModel;
use core\service\deal\DealRepayAccountService;
use core\service\deal\DealService;
use core\service\user\UserCarryService;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;
use core\service\deal\P2pIdempotentService;

/**
 * 订单还款计划
 *
 * Class DealRepayService
 * @package core\service
 */
class DealRepayService extends RepayBaseService {

    /**
     * 执行还款
     *
     * @param $deal_repay_id 还款计划ID
     * @return mixed
     * @throws \Exception
     */
    public function repay($dealRepayId,$repayAccountType,$admin = array(), $submitUid = 0, $auditType = 0, $orderId = '') {
        $dealRepayId = intval($dealRepayId);
        if (empty($dealRepayId)) {
            throw new \Exception("参数错误");
        }

        $dealRepayModel = new DealRepayModel();
        $dealRepay = $dealRepayModel->find($dealRepayId);
        if (empty($dealRepay)) {
            throw new \Exception("获取还款计划失败[$dealRepayId]");
        }
        $userCarryService = new UserCarryService();

        $deal = DealModel::instance()->find($dealRepay['deal_id']);

        $totalRepayMoney = 0.00;
        $rs = $dealRepay->repay($totalRepayMoney, $repayAccountType, $orderId);

        if($rs === false){
            throw new \Exception("还款失败[$dealRepayId]");
        }

        // 部分还款逻辑
        $partRepayInfo = DealPartRepayService::getPartRepayMoneyByOrderId($orderId);
        $partRepayType = $partRepayInfo['partRepayType'];
        if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
            $repayMoney = $partRepayInfo['totalRepayMoney'];
        } else {
            $repayMoney = $dealRepay['repay_money'];
        }

        $rs = $userCarryService->updateWithdrawLimitAfterRepalyMoney($dealRepayId, $repayMoney);
        if($rs === false){
            throw new \Exception("更新金额限制失败[".$dealRepay['user_id']."]");
        }

        $dealModel = new DealModel();
        $deal = $dealModel->find($dealRepay['deal_id']);
        $dealService = new DealService();
        $isPartRepayDealND = $dealService->isPartRepayDealND($deal['id'],$repayAccountType);
        if ($isPartRepayDealND) {
            $this->addNdDealRepayOplog($dealRepay,$deal,$admin,$submitUid,$auditType);
        } else {

            // 代扣里面新增主动还款 jira wxph-178
            if (!empty($orderId)) {
                $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
                if (!empty($orderInfo['params'])){
                    $params = json_decode($orderInfo['params'],true);
                    if (!empty($params['dKSubType'])){
                        $repayAccountType = DealRepayEnum::DEAL_REPAY_TYPE_ZHUDONG_DAIKOU;
                    }
                }
            }
            //添加还款操作记录
            $repayOpLog = new DealRepayOplogModel();
            $repayOpLog->operation_type = DealRepayOpLogEnum::REPAY_TYPE_NORMAL;//正常还款
            $repayOpLog->operation_time = get_gmtime();
            $repayOpLog->operation_status = 1;
            $repayOpLog->operator = isset($admin['adm_name']) ? $admin['adm_name'] : '';
            $repayOpLog->operator_id = isset($admin['adm_id']) ? $admin['adm_id'] : '';
            //标的信息
            $repayOpLog->deal_id = $deal['id'];
            $repayOpLog->deal_name = $deal['name'];
            $repayOpLog->borrow_amount = $deal['borrow_amount'];
            $repayOpLog->rate = $deal['rate'];
            $repayOpLog->loantype = $deal['loantype'];
            $repayOpLog->repay_period = $deal['repay_time'];
            $repayOpLog->user_id = $deal['user_id'];

            //存管&&还款方式
            if ($partRepayType == DealPartRepayService::REPAY_TYPE_PART) {
                $repayOpLog->repay_type = DealRepayEnum::DEAL_REPAY_TYPE_NORMAL_PART;
            } else {
                $repayOpLog->repay_type = $repayAccountType;
            }
            $repayOpLog->report_status = $deal['report_status'];

            //还款的信息
            $repayOpLog->deal_repay_id = $dealRepay['id'];
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
        $borrowerRepayMoney = $partialRepayModel->getRepayMoney($deal_repay['id'],PartialRepayEnum::REPAY_TYPE_BORROWER);
        $compensatoryRepayMoney = $partialRepayModel->getRepayMoney($deal_repay['id'],PartialRepayEnum::REPAY_TYPE_COMPENSATORY);
        $logArray=array();
        if((bccomp($borrowerRepayMoney,'0.00',2) ==1) &&(bccomp($compensatoryRepayMoney,'0.00',2) ==1)) {
            $logArray[] = array('repay_money'=>$borrowerRepayMoney,'repay_type'=>DealRepayEnum::DEAL_REPAY_TYPE_PART_SELF);
            $logArray[] = array('repay_money'=>$compensatoryRepayMoney,'repay_type'=>DealRepayEnum::DEAL_REPAY_TYPE_PART_DAICHANG);
        } else {
            if(bccomp($borrowerRepayMoney,'0.00',2) ==1) {
                $logArray[] = array('repay_money'=>$borrowerRepayMoney,'repay_type'=>DealRepayEnum::DEAL_REPAY_TYPE_SELF);
            } else {
                $logArray[] = array('repay_money'=>$compensatoryRepayMoney,'repay_type'=>DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG);
            }
        }
        foreach ($logArray as $logInfo) {
            //添加还款操作记录
            $repayOpLog = new DealRepayOplogModel();
            $repayOpLog->operation_type = DealRepayOpLogEnum::REPAY_TYPE_NORMAL;//正常还款
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

    public function checkCanRepay($deal,$repay){
        if($deal->deal_status != DealEnum::DEAL_STATUS_REPAY){
            throw new \Exception('标的还款中状态才可发起还款');
        }
        if($repay->status != DealRepayEnum::STATUS_WAITING){
            throw new \Exception('当前期数已还款完成');
        }
        if($deal->is_during_repay == DealEnum::DEAL_DURING_REPAY){
            throw new \Exception('当前标的正在还款，请勿重复操作');
        }
        return true;
    }

    /**
     * 开始还款处理逻辑
     * @param $repayId
     * @param $dealId
     * @return bool
     */
    public function doRepay($dealId,$repayId,$repayAccountType,$admInfo = array(),$submitUid=0){
        $admInfo = empty($admInfo) ? array('adm_name' => 'system','adm_id' => 0) : $admInfo;
        $submitUid = empty($submitUid) ? 0 : $submitUid;

        $deal = DealModel::instance()->find($dealId);
        $repay = DealRepayModel::instance()->find($repayId);

        $param = array('deal_repay_id' => $repayId, 'admin' => $admInfo,'repayAccountType'=>$repayAccountType, 'submitUid' => 0,'auditType' => 3);

        $startTrans = false;
        try{
            $this->checkCanRepay($deal,$repay);
            $jobModel = new JobsModel();


            $startTrans = true;
            $GLOBALS['db']->startTrans();

            $orderId = Idworker::instance()->getId();
            $function = '\core\service\repay\P2pDealRepayService::dealRepayRequest';
            $param = array('orderId'=>$orderId,'dealRepayId'=>$repayId,'repayType'=>$repayAccountType,'params'=>$param);
            $jobModel->priority = JobsEnum::PRIORITY_P2P_REPAY_REQUEST;

            $res = $jobModel->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }

            $res = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
            if(!$res) {
                throw new \Exception("改变标的还款状态失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $startTrans && $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"deal_id:".$dealId,"repay_id:".$repayId)));
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"成功插入jobs并更改了还款状态","deal_id:".$dealId,"repay_id:".$repayId)));
        return true;
    }

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
     * 放款时根据标的判断还款类型
     * @param $deal
     * @return int
     */
    public function getRepayTypeByDeal($dealId){
        $deal = DealModel::instance()->find($dealId);
        $typeId = $deal['type_id'];

        $typeXFD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_XFD);
        $typeDSD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_DSD);
        $typeCDT = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_XJDCDT);
        $typeGFD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_XJDGFD);

        $typeDFD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_DFD);
        $typeHDD = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_HDD);

        if(in_array($typeId, array($typeXFD, $typeGFD, $typeCDT, $typeDFD, $typeHDD))){
            $repayType = DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU;
        }elseif($typeDSD == $typeId){
            $repayType = DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN;
        }else{
            $repayType = DealRepayEnum::DEAL_REPAY_TYPE_SELF;
        }
        return $repayType;
    }

    public function getMaxRepayTimeByDeal($deal){
        $res = DealRepayModel::instance()->getMaxRepayTimeByDealId($deal['id']);
        if(!$res->repay_time){
            $interest_time = $deal['repay_start_time'];
        }else{
            $interest_time = $res['repay_time'];
        }
        return $interest_time;
    }




    /**
     * 根据标ID获取最后一期还款时间
     * @param $deal_id 标ID
     */
    public function getFinalRepayTimeByDealId($deal_id) {
        return DealRepayModel::instance()->getFinalRepayTimeByDealId($deal_id);
    }

    /*
     * 新版还款公告，利用还款操作表
     */
    public function getRepayDealListV2($ps, $pn, $is_firstp2p=false) {
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
        $res['count'] = DealRepayOplogModel::instance()->getRepayDaysCount($is_firstp2p);
        return $res;
    }

    /*
     * 还款公告日历函数
     * $type (0:首页,1:列表页)
     */
    public function getRepayDaysV2($ps,$pn, $is_firstp2p=false) {
        $res = array();
        $limit = intval($ps);
        $offset = intval(($pn-1)*$ps);
        $ret = DealRepayOplogModel::instance()->getRepayDaysSite($is_firstp2p);
        foreach($ret as $one) {
            $tmp['time_readable'] = $one['ymd'];
            $tmp['time'] = str_replace('-','', $one['ymd']);
            $res['list'][] = $tmp;
        }
        return $res;
    }

    /**
     * 根据还款id查询还款信息
     * @param $repayId
     * @return mixed
     */
    public function getInfoById($repayId) {
        return DealRepayModel::instance()->find($repayId);
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
            $repay_list[$k] = $v->getRow();
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
        return array(
            "deal"=> !empty($deal) ? $deal->getRow() : array(),
            "repay_list"=>$repay_list,
            "applied_prepay"=>$applied_prepay,
            "overdue"=>$overdue,
            "cannot_prepay"=>$cannot_prepay,
            'deal_ext'=> !empty($deal_ext) ? $deal_ext->getRow() : array(),
        );
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
}
