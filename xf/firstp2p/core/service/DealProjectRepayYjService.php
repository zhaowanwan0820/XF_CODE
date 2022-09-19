<?php
/**
 * 盈嘉项目还款相关
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\JobsModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealProjectModel;
use core\dao\ProjectYjRepayOplogModel;
use core\dao\ProjectRepayListModel;
use core\service\DealRepayService;
use core\service\ZxDealRepayService;
use core\service\DealProjectService;
use libs\utils\Logger;
use libs\utils\Finance;

class DealProjectRepayYjService extends BaseService
{

    const OFFLINE_STATUS_UNCHARGE   = 1; //1未充值
    const OFFLINE_STATUS_CHARGED    = 2; //2已充值
    const OFFLINE_STATUS_REPAY_CALC = 3; //3未确认代发 还款计算完成
    const OFFLINE_STATUS_REPAYED    = 4 ;//4已确认代发
    const OFFLINE_STATUS_CHANGE_REPAY_STATUS    = 5 ;//5更改还款状态

    /**
     * 根据项目id 获取还款信息
     * @param int $project_id
     * @return array
     */
    public function getProjectRepayInfo($project_id)
    {
        if (empty($project_id)) {
            return array();
        }

        $deal_list = DealModel::instance()->getDealByProId($project_id);
        $repay_collection = array(
            'repay_list' => array(),
            'applied_prepay' => 0,
            'overdue' => 0,
            'cannot_prepay' => 0,
            'prepay_show' => null,
            'total_repay_money' => 0,
            'deal' => array(),
        );
        $deal_repay_service = new DealRepayService();
        foreach ($deal_list as $deal) {
            $deal_repay_info = $deal_repay_service->getDealRepayInfo($deal['id'], $deal['user_id']);
            // repay_list
            foreach ($deal_repay_info['repay_list'] as $key => $one_repay) {
                if (!isset($repay_collection['repay_list'][$key]['status'])) {
                    $repay_collection['repay_list'][$key]['status'] = $one_repay['status'];
                }
                if (!isset($repay_collection['repay_list'][$key]['repay_time'])) {
                    $repay_collection['repay_list'][$key]['repay_time'] = $one_repay['repay_time'];
                }

                $repay_collection['repay_list'][$key]['repay_money'] = Finance::addition(array($one_repay['repay_money'], $repay_collection['repay_list'][$key]['repay_money']));
                $repay_collection['repay_list'][$key]['principal'] = Finance::addition(array($one_repay['principal'], $repay_collection['repay_list'][$key]['principal']));
                $repay_collection['repay_list'][$key]['interest'] = Finance::addition(array($one_repay['interest'], $repay_collection['repay_list'][$key]['interest']));
                $repay_collection['repay_list'][$key]['consult_fee'] = Finance::addition(array($one_repay['consult_fee'], $repay_collection['repay_list'][$key]['consult_fee']));
                $repay_collection['repay_list'][$key]['guarantee_fee'] = Finance::addition(array($one_repay['guarantee_fee'], $repay_collection['repay_list'][$key]['guarantee_fee']));
                $repay_collection['repay_list'][$key]['loan_fee'] = Finance::addition(array($one_repay['loan_fee'], $repay_collection['repay_list'][$key]['loan_fee']));
                $repay_collection['repay_list'][$key]['pay_fee'] = Finance::addition(array($one_repay['pay_fee'], $repay_collection['repay_list'][$key]['pay_fee']));
                $repay_collection['repay_list'][$key]['fee_of_overdue'] = Finance::addition(array($one_repay['fee_of_overdue'], $repay_collection['repay_list'][$key]['fee_of_overdue']));

                $repay_collection['repay_list'][$key]['can_repay'] = $one_repay['can_repay'];
                $repay_collection['repay_list'][$key]['fee_of_overdue'] = Finance::addition(array($one_repay['fee_of_overdue'], $repay_collection['repay_list'][$key]['fee_of_overdue']));
                $repay_collection['repay_list'][$key]['printerest'] = Finance::addition(array($one_repay['printerest'], $repay_collection['repay_list'][$key]['printerest']));
            }

            // 带着项目的一个标
            if (empty($repay_collection['deal'])) {
                $repay_collection['deal'] = $deal_repay_info['deal'];
            }

            // 总共要还的钱
            $repay_collection['total_repay_money'] = Finance::addition(array($deal_repay_info['deal']['total_repay_money'], $repay_collection['total_repay_money']));

            // 标识已申请或完成提前还款
            $repay_collection['applied_prepay'] = $deal_repay_info['applied_prepay'];

            // 标识逾期
            $repay_collection['overdue'] = $deal_repay_info['overdue'];

            // 标识不能进行提前还款
            $repay_collection['cannot_prepay'] = $deal_repay_info['cannot_prepay'];

            // 标识是否可以提前还款
            if (is_null($repay_collection['prepay_show'])) {
                $type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal_repay_info['deal']->type_id);
                $today = to_timespan(date('Y-m-d'));
                $interest_time =  $deal_repay_service->getLastRepayTimeByDealId($deal_repay_info['deal']);
                $repay_collection['prepay_show'] = !($type_tag == DealLoanTypeModel::TYPE_XFFQ || $deal_repay_info['deal']->deal_type == DealModel::DEAL_TYPE_COMPOUND || $today >= $interest_time);
            }
        }

        return $repay_collection;
    }

    /**
     * 获取盈嘉项目Id
     */
    public function getYjProjectIds() {
        // 盈嘉项目线下还款项目ID配置
        $yjIdsStr = app_conf('PROJECT_YJ_OFFLINE_REPAY_IDS');
        $yjProjectIds = array();
        if(!empty($yjIdsStr)) {
            $yjProjectIds = explode(',',$yjIdsStr);
        }else{
            return false;
        }
        return $yjProjectIds;
    }

    /**
     * 充值完成
     * @param $project_repay_id 项目还款id
     */
    public function charge($project_repay_id) {

        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)) {
            return false;
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            return false;
        }

        try{
            $GLOBALS['db']->startTrans();

            $dealProjectService = new DealProjectService();
            if(!$dealProjectService->isProjectYJ175(intval($project_repay_info['project_id']))) {
                throw new \Exception("非盈嘉项目，不允许线下还款");
            }

            $project_repay_info->offline_status = self::OFFLINE_STATUS_CHARGED;
            $save_res = $project_repay_info->save();
            if(!$save_res) {
                throw new \Exception("更改线下已充值状态失败");
            }

            $this->_addRepayOplog($project_repay_info,$project,ProjectYjRepayOplogModel::OPERATION_TYPE_CHARGED);

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * 还款计算
     * @param $project_repay_id 项目还款id
     */
    public function repayCalc($project_repay_id) {

        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)) {
            throw new \Exception("项目还款信息不存在");
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            throw new \Exception("项目信息不存在");
        }

        try{
            $GLOBALS['db']->startTrans();

            $project_repay_info->offline_status = self::OFFLINE_STATUS_REPAY_CALC;
            $save_res = $project_repay_info->save();
            if(!$save_res) {
                throw new \Exception("更改线下还款状态失败");
            }

            $zxDealRepayService = new ZxDealRepayService();
            $dfRes = $zxDealRepayService->genRepayDfRecord($project_repay_info['project_id'],$project_repay_id);
            if(!$dfRes) {
                throw new \Exception("调用代发接口失败");
            }

            $this->_addRepayOplog($project_repay_info,$project,ProjectYjRepayOplogModel::OPERATION_TYPE_REPAY_CALC);

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            throw $ex;
        }
        return true;
    }

    /**
     * 还款
     * @param $project_repay_id 项目还款id
     */
    public function repay($project_repay_id) {
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)) {
            throw new \Exception("项目还款信息不存在");
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            throw new \Exception("项目信息不存在");
        }

        try{
            $GLOBALS['db']->startTrans();

            $project_repay_info->offline_status = self::OFFLINE_STATUS_REPAYED;
            $save_res = $project_repay_info->save();
            if(!$save_res) {
                throw new \Exception("更改线下已还款状态失败");
            }

            $zxDealRepayService = new ZxDealRepayService();
            $dfRes = $zxDealRepayService->projectTrans($project_repay_id);
            if(!$dfRes) {
                throw new \Exception("调用代发接口失败");
            }

            $this->_addRepayOplog($project_repay_info,$project,ProjectYjRepayOplogModel::OPERATION_TYPE_REPAYED);

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            throw $ex;
        }
        return true;
    }

    /**
     * 查看代发金额
     * @param $project_repay_id 项目还款id
     */
    public function checkRepayInfo($project_repay_id) {
        $repayInfo = array();
        try{
            $zxDealRepayService = new ZxDealRepayService();
            $transMoneySummary = $zxDealRepayService->getTransMoneySummary($project_repay_id);

            $repayInfo['normal_money'] = $transMoneySummary['repay'];
            $repayInfo['yxt_money'] = $transMoneySummary['yxt'];
            $repayInfo['sd_money'] = $transMoneySummary['sudai'];
        }catch (\Exception $ex) {
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            return array();
        }
        return $repayInfo;
    }

    /**
     * 更改还款状态
     * @param $project_repay_id 项目还款id
     */
    public function changeRepayStatus($project_repay_id,$admInfo=array()) {
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)) {
            return false;
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            return false;
        }

        //判断状态
        if($project_repay_info['offline_status'] != DealProjectRepayYjService::OFFLINE_STATUS_REPAYED){
            return false;
        }

        if(in_array($project['business_status'],array(
            DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay'],
            DealProjectModel::$PROJECT_BUSINESS_STATUS['repaid'],
        ))){
            throw new \Exception("项目正在还款中或已还清");
        }

        try{
            $GLOBALS['db']->startTrans();

            $project_repay_info->offline_status = self::OFFLINE_STATUS_CHANGE_REPAY_STATUS;
            $save_res = $project_repay_info->save();
            if(!$save_res) {
                throw new \Exception("变更项目正在还款状态失败");
            }

            $admInfo = empty($admInfo) ? \es_session::get(md5(conf("AUTH_KEY"))) : $admInfo;
            $function = '\core\service\DealRepayService::projectRepay';
            $param = array(
                'project_id' => $project["id"],
                'ignore_impose_money' => 1,
                'admin' => $admInfo,
                'negative' => 0,
                'repayType' => 0,
                'submitUid' => $admInfo['adm_id'],
                'auditType' => 3
            );

            $job_model = new JobsModel();
            $job_model->priority = 110;
            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("项目还款加入jobs失败");
            }

            if(!DealProjectModel::instance()->changeProjectStatus($project["id"],DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay'])){
                throw new \Exception("变更项目正在还款状态失败");
            }

            $this->_addRepayOplog($project_repay_info,$project,ProjectYjRepayOplogModel::OPERATION_TYPE_CHANGE_STATUS,$admInfo);

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * 添加还款操作记录
     * @param $project_repay_info 项目还款信息
     * @param $project 项目信息
     * @param $operation_type 操作类型
     * @throws \Exception
     */
    private function _addRepayOplog($project_repay_info,$project,$operation_type,$adminInfo=array()) {
        $adminInfo = empty($adminInfo) ?  \es_session::get(md5(conf("AUTH_KEY"))) : $adminInfo;

        $repayOpLog = new ProjectYjRepayOplogModel();
        $repayOpLog->operation_type = $operation_type;
        $repayOpLog->operation_time = get_gmtime();
        $repayOpLog->operation_status = 1;
        $repayOpLog->operator = $adminInfo['adm_name'];
        $repayOpLog->operator_id = $adminInfo['adm_id'];

        $repayOpLog->project_id = $project_repay_info['project_id'];
        $repayOpLog->project_name = $project['name'];
        $repayOpLog->borrow_amount = $project['borrow_amount'];
        $repayOpLog->rate = $project['rate'];
        $repayOpLog->loantype = $project['loantype'];
        $repayOpLog->repay_period = $project['repay_time'];
        $repayOpLog->user_id = $project['user_id'];

        $repayOpLog->deal_repay_id = $project_repay_info['id'];
        $repayOpLog->repay_money = $project_repay_info['repay_money'];
        $repayOpLog->repay_principal = $project_repay_info['principal'];
        $repayOpLog->repay_interest = $project_repay_info['interest'];
        $repayOpLog->loan_fee = $project_repay_info['loan_fee'];
        $repayOpLog->consult_fee = $project_repay_info['consult_fee'];
        $repayOpLog->guarantee_fee = $project_repay_info['guarantee_fee'];
        $repayOpLog->real_repay_time = time();

        $save_res = $repayOpLog->save();
        if(!$save_res) {
            throw new \Exception("插入还款操作记录失败");
        }
        return true;
    }

}
