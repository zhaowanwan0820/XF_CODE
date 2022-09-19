<?php

namespace core\service;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use libs\utils\Logger;
use core\dao\ExchangeModel;

class ExchangeService extends BaseService {

    /**
     * 小数点后处理方式
     */
    private function _floorfix($number) {
        return floor(round($number, 5));
    }

    /**
     * 还款次数
     */
    private function _getRepayTimes($projectInfo) {
        $repayType  = $projectInfo['repay_type'];

        $repayTimes = 1;
        if ($repayType == 3) { // 按月支付收益到期还本
            $repayTimes = $projectInfo['repay_time'];
        } elseif ($repayType == 4) { //按季支付收益到期还本
            $repayTimes = ceil($projectInfo['repay_time'] / 3);
        }

        return $repayTimes;
    }

    /**
     * 手续费是否需要前收
     */
    private function _isNeedPrePay($projectInfo) {
        $fields = [
            'invest_adviser_type',
            'publish_server_type',
            'consult_type',
            'guarantee_type',
            'hang_server_type',
        ];

        $needPrePay = false;
        foreach ($fields as $field) {
            $needPrePay = $needPrePay || ($projectInfo[$field] == 1);
        }

        return $needPrePay;
    }

    /**
     * 保存手续费前收还款计划
     */
    private function _saveBatchPrePay($exchangeModel, $projectInfo, $batchInfo) {
        if (!$this->_isNeedPrePay($projectInfo)) {
            return true;
        }

        $row = [
            'batch_id' => $batchInfo['id'],
            'invest_adviser_fee' => $projectInfo['invest_adviser_type'] == 1 ? $batchInfo['invest_adviser_fee'] : 0,
            'publish_server_fee' => $projectInfo['publish_server_type'] == 1 ? $batchInfo['publish_server_fee'] : 0,
            'consult_fee'        => $projectInfo['consult_type']     == 1 ? $batchInfo['consult_fee']     : 0 ,
            'guarantee_fee'      => $projectInfo['guarantee_type']   == 1 ? $batchInfo['guarantee_fee']   : 0,
            'hang_server_fee'    => $projectInfo['hang_server_type'] == 1 ? $batchInfo['hang_server_fee'] : 0,
            'repay_time' => $batchInfo['repay_start_time'],
            'pay_time'   => $batchInfo['repay_start_time'],
            'status'      => 2,
            'is_plan'     => 1,
            'is_actually' => 1,
        ];

        $row['repay_money'] = $row['invest_adviser_fee'] + $row['publish_server_fee'] + $row['consult_fee'] + $row['guarantee_fee'] + $row['hang_server_fee'];
        $row['create_time'] = $row['update_time'] = time();

        return $exchangeModel->saveBatchRepay($row);
    }

    /**
     * num个月后的日期
     */
    public function _timeAddMonth($time, $num) {
        list($year, $month, $day) = explode('-', date('Y-m-d', $time));

        $targetMonth = $month + $num;
        $year += floor($targetMonth / 12);

        $month = $targetMonth % 12;
        if ($month == 0) {
            $month = 12;
            $year --;
        }

        $time = strtotime(sprintf("%s-%s-%s", $year, $month, $day));
        if ($day != date('d', $time)) {
            $time = strtotime(sprintf("%s-%s-%s", date('Y', $time), date('m', $time), 1));
        }

        return $time;
    }

    /**
     * 保存每期还款计划
     */
    private function _saveBatchTurnPay($params) {
        $projectInfo = $params['projectInfo']; // 项目信息
        $batchInfo   = $params['batchInfo'];   // 批次信息
        $repayTimes  = $params['repayTimes'];  // 还款次数
        $nowTimes    = $params['nowTimes'];    // 当前的还款期数

        $row = [
            'batch_id'    => $batchInfo['id'],
            'principal'   => 0,
            'status'      => 1,
            'is_plan'     => 1,
            'is_actually' => 2,
        ];

        $serviceFee = 0;
        if ($repayTimes == $nowTimes) { //最后一期
            $row['principal'] = $batchInfo['amount'];
            $row['consult_fee'] = $projectInfo['consult_type'] == 2 ? $batchInfo['consult_fee'] : 0;
            $row['guarantee_fee'] = $projectInfo['guarantee_type'] == 2 ? $batchInfo['guarantee_fee'] : 0;
            $row['hang_server_fee'] = $projectInfo['hang_server_type'] == 2 ? $batchInfo['hang_server_fee'] : 0;
            $row['invest_adviser_fee'] = $projectInfo['invest_adviser_type'] == 2 ? $batchInfo['invest_adviser_fee'] : 0;
            $row['publish_server_fee'] = $projectInfo['publish_server_type'] == 2 ? $batchInfo['publish_server_fee'] : 0;
            $serviceFee = $row['invest_adviser_fee'] + $row['publish_server_fee'] + $row['consult_fee'] + $row['guarantee_fee'] + $row['hang_server_fee'];
        }

        $repayTime = $projectInfo['repay_time'];  // 借款时长
        $repayStartTime = $batchInfo['repay_start_time']; // 放款时间
        $yearRate = $projectInfo['expect_year_rate'] / 10000000; // 借款利率

        if ($projectInfo['repay_type'] == 3) {       // 3 按月支付收益到期还本
            $row['interest'] = $this->_floorfix($batchInfo['amount'] * $yearRate * $repayTime / 12 / $repayTimes);
            $row['repay_time']  = $this->_timeAddMonth($repayStartTime, $nowTimes);
        } elseif ($projectInfo['repay_type'] == 4) { // 4 按季支付收益到期还本
            $row['interest'] = $this->_floorfix($batchInfo['amount'] * $yearRate * $repayTime / 12 / $repayTimes);
            $row['repay_time']  = $this->_timeAddMonth($repayStartTime, $nowTimes * 3);
        } elseif ($projectInfo['repay_type'] == 2) { // 2 到期支付本金收益（月）
            $row['interest'] = $this->_floorfix($batchInfo['amount'] * $yearRate * $repayTime / 12);
            $row['repay_time']  = $this->_timeAddMonth($repayStartTime, $repayTime);
        } elseif ($projectInfo['repay_type'] == 1) { // 1 到期支付本金收益（天）
            $row['interest'] = $this->_floorfix($batchInfo['amount'] * $yearRate * $repayTime / 360);
            $row['repay_time']  = strtotime(sprintf("+ %s day", $repayTime), $repayStartTime);
        } else {
            throw new \Exception("暂时还不支持的还款方式!");
        }

        $row['repay_money'] = $row['principal'] + $row['interest'] + $serviceFee;
        $row['create_time'] = $row['update_time'] = time();

        return $params['exchangeModel']->saveBatchRepay($row);
    }

    /**
     * 生成批次还款计划
     */
    public function genBatchRepayPlan($params) {
        $exchangeModel = new ExchangeModel();
        $batchInfo = $exchangeModel->getBatchInfoById($params['batchId']);
        if ($batchInfo['is_ok'] != 1 || $batchInfo['deal_status'] != 2) {
            Logger::error(sprintf("非正常状态批次信息, 不能生成还款计划, 数据: %s", json_encode($batchInfo)));
            return false;
        }

        $projectInfo = $exchangeModel->getProjectInfoById($batchInfo['pro_id']);
        if ($projectInfo['is_ok'] != 1) {
            Logger::error(sprintf("非正常状态项目信息, 不能生成还款计划, 数据: %s", json_encode($projectInfo)));
            return false;
        }

        $batchRepayList = $exchangeModel->getBatchRepayPlanByBatchId($batchInfo['id']);
        if ($batchRepayList) {
            Logger::error(sprintf("已生成过还款计划, 无需再生成, 数据: %s", json_encode($batchRepayList)));
            return true;
        }

        $repayTimes = $this->_getRepayTimes($projectInfo);
        Logger::info(sprintf("需要进行 %s 期还款, data: %s", $repayTimes, json_encode($params)));

        for ($time = 0; $time <= $repayTimes; $time ++) {
            if ($time == 0) { // 这里只处理前收手续费
                if(!$this->_saveBatchPrePay($exchangeModel, $projectInfo, $batchInfo)) {
                    Logger::error(sprintf("生成还款计划错误, 保存手续费前收失败, data: %s", json_encode($params)));
                    return false;
                }
            } else {
                $data = ['exchangeModel' => $exchangeModel, 'projectInfo' => $projectInfo, 'batchInfo' => $batchInfo, 'repayTimes' => $repayTimes, 'nowTimes' => $time];
                if (!$this->_saveBatchTurnPay($data)) {
                    Logger::error(sprintf("生成还款计划错误, 保存第%s期还款计划失败, data: %s", $time, json_encode($params)));
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 保存每期回款计划
     */
    private function _saveLoadTurnPay($params) {
        $projectInfo = $params['projectInfo']; // 项目信息
        $batchInfo   = $params['batchInfo'];   // 批次信息
        $repayTimes  = $params['repayTimes'];  // 还款次数
        $repayTime   = $projectInfo['repay_time'];  // 借款时长
        $yearRate    = $projectInfo['expect_year_rate'] / 10000000; // 借款利率

        $row = ['batch_id' => $batchInfo['id'], 'status' => 1, 'is_plan' => 1, 'is_actually' => 2];
        $row['create_time'] = $row['update_time'] = time();

        foreach ($params['loadList'] as $loadItem) {
            $maxTimes = count($params['batchRepayList']);
            foreach ($params['batchRepayList'] as $index => $batchRepayItem) {
                if ($batchRepayItem['principal'] <= 0 && $batchRepayItem['interest'] <= 0) { // 前收手续费
                    continue;
                }

                $row['repay_id'] = $batchRepayItem['id'];
                $row['load_id']  = $loadItem['id'];
                $row['repay_time'] = $batchRepayItem['repay_time'];
                $row['principal'] = ($maxTimes == $index + 1) ? $loadItem['pay_money'] : 0;

                if ($projectInfo['repay_type'] == 3) {       // 3 按月支付收益到期还本
                    $row['interest'] = $this->_floorfix($loadItem['pay_money'] * $yearRate * $repayTime / 12 / $repayTimes);
                } elseif ($projectInfo['repay_type'] == 4) { // 4 按季支付收益到期还本
                    $row['interest'] = $this->_floorfix($loadItem['pay_money'] * $yearRate * $repayTime / 12 / $repayTimes);
                } elseif ($projectInfo['repay_type'] == 2) { // 2 到期支付本金收益（月）
                    $row['interest'] = $this->_floorfix($loadItem['pay_money'] * $yearRate * $repayTime / 12);
                } elseif ($projectInfo['repay_type'] == 1) { // 1 到期支付本金收益（天）
                    $row['interest'] = $this->_floorfix($loadItem['pay_money'] * $yearRate * $repayTime / 360);
                } else {
                    throw new \Exception("暂时还不支持的还款方式!");
                }

                $row['repay_money'] = $row['principal'] + $row['interest'];
                if (!$params['exchangeModel']->saveLoadRepay($row)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 生成批次回款计划
     */
    public function genLoadRepayPlan($params) {
        $exchangeModel = new ExchangeModel();
        $batchInfo = $exchangeModel->getBatchInfoById($params['batchId']);
        if ($batchInfo['is_ok'] != 1 || $batchInfo['deal_status'] != 2) {
            Logger::error(sprintf("非正常状态批次信息, 不能生成回款计划, 数据: %s", json_encode($batchInfo)));
            return false;
        }

        $projectInfo = $exchangeModel->getProjectInfoById($batchInfo['pro_id']);
        if ($projectInfo['is_ok'] != 1) {
            Logger::error(sprintf("非正常状态项目信息, 不能生成回款计划, 数据: %s", json_encode($projectInfo)));
            return false;
        }

        $oneLoadRepay = $exchangeModel->getOneLoadRepayPlanByBatchId($batchInfo['id']);
        if ($oneLoadRepay) {
            Logger::error(sprintf("该批次已经生成过回款计划, 不能再次生成回款计划, 数据: %s", json_encode($oneLoadRepay)));
            return true;
        }

        $batchRepayList = $exchangeModel->getBatchRepayPlanByBatchId($batchInfo['id']);
        if (!$batchRepayList) {
            Logger::error(sprintf("该批次还没有生成还款计划, 因此不能生成回款计划, 数据: %s", json_encode($batchInfo)));
            return false;
        }

        $loadList = $exchangeModel->getLoadListByBatchId($batchInfo['id']);
        if (!$loadList) {
            Logger::error(sprintf("该批次还没导入投资明细, 因此不能生成回款计划, 数据: %s", json_encode($batchInfo)));
            return false;
        }

        $repayTimes = $this->_getRepayTimes($projectInfo);
        Logger::info(sprintf("需要进行 %s 期回款, data: %s", $repayTimes, json_encode($params)));

        return $this->_saveLoadTurnPay([
              'exchangeModel'  => $exchangeModel,
              'projectInfo'    => $projectInfo,
              'batchInfo'      => $batchInfo,
              'repayTimes'     => $repayTimes,
              'loadList'       => $loadList,
              'batchRepayList' => $batchRepayList,
        ]);
    }

    /**
     * 将回款金额重置为还款金额
     */
    public function resetBatchRepayMoney($params) {
        $exchangeModel = new ExchangeModel();
        $loadRepayList = $exchangeModel->getLoadRepayMoneyList($params['batchId']);
        if (empty($loadRepayList)) {
            Logger::error(sprintf("查询回写金额错误, 数据: %s", json_encode($params)));
            return false;
        }

        foreach ($loadRepayList as $item) {
            $result = $exchangeModel->batchMoneyWriteBack($item['repay_id'], $item['total_principal'], $item['total_interest']);
            if (!$result) {
                Logger::error(sprintf("回写还款金额错误, 数据: %s", json_encode($params)));
                return false;
            }
        }

        return true;
    }

    //保证生成还款计划和回款计划的顺序
    public function genBatchLoadRepayPlan($params){
        Logger::info(sprintf("开始生成还款和回款计划, 数据: %s", json_encode($params)));
        $GLOBALS['db']->startTrans();

        if (!$this->genBatchRepayPlan($params)) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf("生成还款计划错误, 数据: %s", json_encode($params)));
            return false;
        }

        if (!$this->genLoadRepayPlan($params)) {
            Logger::error(sprintf("生成回款计划错误, 数据: %s", json_encode($params)));
            $GLOBALS['db']->rollback();
            return false;
        }

        if (!$this->resetBatchRepayMoney($params)) {
            Logger::error(sprintf("回写还款金额错误, 数据: %s", json_encode($params)));
            $GLOBALS['db']->rollback();
            return false;
        }

        $GLOBALS['db']->commit();
        return true;
    }

    public function regenBatchLoadRepayPlan($params) {
        $model = new ExchangeModel();

        $GLOBALS['db']->startTrans();
        if (!$model->delBatchRepayByBatchId($params['batchId'])) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf("删除还款计划失败, 数据: %s", json_encode($params)));
            return false;
        }

        if (!$model->delLoadRepayByBatchId($params['batchId'])) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf("删除回款计划失败, 数据: %s", json_encode($params)));
            return false;
        }

        $GLOBALS['db']->commit();
        return $this->genBatchLoadRepayPlan($params);
    }

}
