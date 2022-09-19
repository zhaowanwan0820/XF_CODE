<?php
namespace core\service\report;


use core\dao\repay\DealRepayModel;
use core\dao\repay\DealPrepayModel;
use core\enum\MsgbusEnum;
use core\service\report\ReportBase;
use core\enum\DealRepayEnum;
use core\dao\deal\DealModel;

class ReportRepay extends ReportBase
{
    public $topic;
    public $params;
    public $dealId;
    public $repayId;
    public $nextRepayId;
    public $prepayId;
    public $repayInfo;
    public $prepayInfo;
    public $lastRepayPlan;
    public $dealInfo;

    public function __construct($topic,$params){
        $this->topic = $topic;
        $this->params = $params;
        $this->dealId = $params['dealId'];
        $this->dealInfo = DealModel::instance()->getDealInfo($this->dealId);
    }

    public function collectData(){
        if ($this->topic == MsgbusEnum::TOPIC_DEAL_REPAY_FINISH){
            $this->repayId = $this->params['repayId'];
            $this->nextRepayId = $this->params['nextRepayId'];
            $this->repayInfo = DealRepayModel::instance()->find($this->repayId);
            $data = [
                'deal_id' => $this->dealId,
                'repay_id' => 'R'.$this->repayId,
                'term_no' => $this->getTermNo(),
                'term_status' => $this->repayInfo['status'],
                'target_repayment_time' => $this->repayInfo['repay_time']+28800,
                'real_repayment_time' => $this->repayInfo['true_repay_time']+28800,
                'planned_payment' => $this->repayInfo['repay_money'],
                'real_repayment' => $this->repayInfo['repay_money'],
                'remaining_amount' => $this->getRemainingAmount(),
                'loan_status' =>$this->nextRepayId ? self::LOAN_STATUS_NORMAL :self::LOAN_STATUS_CLEARED,
                'repay_type' => $this->repayInfo['repay_type'],
                'create_time' => time(),
                'update_time' => time(),
            ];
        }elseif($this->topic == MsgbusEnum::TOPIC_DEAL_PREPAY_FINISH){
            $this->prepayId = $this->params['repayId'];
            $this->prepayInfo = DealPrepayModel::instance()->find($this->prepayId);
            $this->lastRepayPlan = $this->getPrepayLastRepayPlan();
            $data = [
                'deal_id' => $this->dealId,
                'repay_id' => 'P'.$this->prepayId,
                'term_no' => $this->getPrepayTermNo(),
                'term_status' => 4,  //提前
                'target_repayment_time' => $this->lastRepayPlan['repay_time']+28800,
                'real_repayment_time' => $this->dealInfo->update_time+28800,
                'planned_payment' => $this->lastRepayPlan['repay_money'],
                'real_repayment' => $this->prepayInfo['prepay_money'],
                'remaining_amount' => 0,
                'loan_status' =>self::LOAN_STATUS_CLEARED,
                'repay_type' => $this->prepayInfo['repay_type'],
                'create_time' => time(),
                'update_time' => time(),
            ];
        }else{
            throw new \Exception('topic error');
        }

        return $data;
    }

    //获取当前还款期数
    public function getTermNo(){
        return DealRepayModel::instance()->getDealRepayPeriodsOrderByUserId($this->dealId,$this->repayInfo['user_id']);
    }
    //获取在途金额
    public function getRemainingAmount(){
        $sql = "SELECT sum(repay_money) AS `sum` FROM %s WHERE `deal_id`='%d' AND `status`='%d'";
        $sql = sprintf($sql, DealRepayModel::instance()->tableName(), $this->dealId,DealRepayEnum::STATUS_WAITING);
        $res = DealRepayModel::instance()->findBySql($sql);
        return $res['sum'];
    }
    //获取提前还款当前还款期数
    public function getPrepayTermNo(){
        $condition = "`deal_id` = %d AND `status` != %d";
        $condition = sprintf($condition, $this->dealId, DealRepayEnum::STATUS_PREPAID);
        return DealRepayModel::instance()->count($condition)+1;
    }
    //获取提前还款类 近一期的还款计划
    public function getPrepayLastRepayPlan(){
        $sql = "SELECT * FROM %s WHERE `deal_id`='%d' AND `status`='%d'ORDER BY repay_time ASC LIMIT 1";
        $sql = sprintf($sql, DealRepayModel::instance()->tableName(),$this->dealId, DealRepayEnum::STATUS_PREPAID);
        return DealRepayModel::instance()->findBySql($sql);

    }


}