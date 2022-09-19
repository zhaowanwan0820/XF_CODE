<?php
namespace core\service\deal;

use core\service\DealProjectService;
use core\service\CouponService;
use core\service\ReservationMatchService;
use core\service\DealService;

use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\dao\DealQueueModel;
use core\dao\JobsModel;

/**
 * WaitingState
 * 标的新建（包括信贷导入和上标时）需要进行的初始化操作
 *
 */
class WaitingState extends State{

    function work($sm) {
        $this->deal = $sm->getDeal();
        $deal_model = $sm->getDealModel();
        $this->deal['deal_status'] = 0;
        $deal_id = $this->deal['id'];

        //1.更新项目信息
        $deal_pro_service = new DealProjectService();
        $result = $deal_pro_service->updateProBorrowed($this->deal['project_id']);
        if(!$result){
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "line:" . __LINE__, "init waiting deal fail deal_id:".$deal_id, "update project borrow fail")));
        }

        $GLOBALS['db']->startTrans();
        try {

            //3.初始化标的信息并加入队列
            if ($this->_initDeal() === false) {
                throw new \Exception('init deal fail');
            }

            $GLOBALS['db']->commit();
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init waiting deal success. deal_id:".$deal_id)));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "line:" . __LINE__, "init waiting deal fail. deal_id:" . $deal_id, $e->getMessage())));

            throw $e;
        }
    }

    private function _initDeal()
    {
        $deal_loan_type = DealLoanTypeModel::instance()->getLoanTagByTypeId($this->deal['type_id']);
        if (!$deal_loan_type) {
            return true;
        }

        //变更:根据deal loan type表中的auto start 判断是否进入队列

        $dealLoanType = DealLoanTypeModel::instance()->find($this->deal['type_id']);

        if ( $deal_loan_type == DealLoanTypeModel::TYPE_XFD
            || $deal_loan_type == DealLoanTypeModel::TYPE_XFFQ
            || $deal_loan_type == DealLoanTypeModel::TYPE_DSD
            || $deal_loan_type == DealLoanTypeModel::TYPE_XJDCDT ) {

            $this->deal['is_effect'] = 0;
            if ($this->deal->save() === false) {
                return false;
            }

            // 改为普通标
            \FP::import("app.deal");
            $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST']['普通标(3个月及以上)'];
            update_deal_site($this->deal['id'], array($site_id));
        }


        if($dealLoanType['auto_start'] == 1){
            if ($this->_insertDealQueue() === false) {
                return false;
            }
        }

        // 检查标的是否符合匹配规则并打TAG
        $reservationMatch = new ReservationMatchService();
        $matchRet = $reservationMatch->checkDealAndSetTag($this->deal['id'], true, true, true);
        // 0:匹配成功并打TAG成功    1:未匹配到规则
        if (!isset($matchRet['respCode']) || ($matchRet['respCode'] !== 0 && $matchRet['respCode'] !== 1)) {
            return false;
        }
        return true;
    }

    private function _insertDealQueue() {
        // 如果属于消费贷，则加入队列，并变更状态
        // 增加对期限的匹配
        $deal_queue = DealQueueModel::instance()->getQueueByTypeId($this->deal['type_id'], $this->deal['repay_time'], $this->deal['loantype']);
        if (!$deal_queue) {
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "line:" . __LINE__, "getQueueByTypeId cannot find queue. deal_id:" . $this->deal['id'] ."type_id:".$this->deal['type_id'])));
            return true;
        }

        $function = '\core\dao\DealQueueModel::insertDealQueue';
        $param = array($deal_queue['id'], $this->deal['id']);
        $job_model = new JobsModel();
        $job_model->priority = 75;
        if (!$job_model->addJob($function, $param)) {
             \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "line:" . __LINE__, "addJob fail.deal_id:" . $this->deal['id'] ." function:".$function."; param:".json_encode($param))));
            return false;
        }
        return true;
    }
}
?>
