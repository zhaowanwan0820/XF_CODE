<?php

/**
 *  线下交易所待还款列表
 */
use libs\utils\Logger;
use core\dao\JobsModel;

class ExchangeRepayListAction extends CommonAction {

    private function _floorfix($number) {
        return floor(round($number, 5));
    }

    private function _queryProjectIdsFromProject() {
        $projectWhere = '';

        $proName = trim($_REQUEST['pro_name']);
        if (strlen($proName)) {
            $projectWhere .= " AND name LIKE '%" . addslashes($proName) . "%'";
        }

        $jysNum = trim($_REQUEST['jys_num']);
        if (strlen($jysNum)) {
            $projectWhere .= " AND jys_number LIKE '%" . addslashes($jysNum) . "%'";
        }

        $repayType = trim($_REQUEST['repay_type']);
        if (strlen($repayType)) {
            $projectWhere .= " AND repay_type = " . intval($repayType);
        }

        $fxUid = trim($_REQUEST['fx_uid']);
        if (strlen($fxUid)) {
            $projectWhere .= " AND fx_uid = " . intval($fxUid);
        }

        $jysId = trim($_REQUEST['jys_id']);
        if(strlen($jysId)) {
            $projectWhere .= " AND jys_id = " . intval($jysId);
        }

        $fxName = trim($_REQUEST['fx_name']);
        if (strlen($fxName)) {
            $sql = "SELECT GROUP_CONCAT(DISTINCT(fx_uid)) FROM firstp2p_exchange_project WHERE is_ok = 1";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if ($ids) {
                $sql = "SELECT GROUP_CONCAT(id) FROM firstp2p_user WHERE id IN (" . $ids . ") AND real_name LIKE '%" . addslashes($fxName) . "%'";
                $ids = $GLOBALS['db']->get_slave()->getOne($sql);
                $projectWhere .= ($ids ? sprintf(" AND fx_uid IN(%s)", $ids) : " AND 1 < 0");
            } else {
                $projectWhere .= " AND 1 < 0";
            }
        }

        $consultName = trim($_REQUEST['consult_name']);
        if (strlen($consultName)) {
            $sql = "SELECT GROUP_CONCAT(id) FROM firstp2p_deal_agency WHERE name LIKE '%" . addslashes($consultName) . "%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $projectWhere .= ($ids ? sprintf(" AND consult_id IN(%s)", $ids) : " AND 1 < 0");
        }

        if ($projectWhere) {
            $sql = "SELECT GROUP_CONCAT(id) FROM firstp2p_exchange_project WHERE deal_status IN(2, 3) AND is_ok = 1 " . $projectWhere;
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            return $ids ? $ids : '-1';
        }

        return '';
    }

    private function _queryBatchIdsFromBatchRepay() {
        $loadRepayWhere = '';

        $repayTimeStart = trim($_REQUEST['repay_time_start']);
        if (strlen($repayTimeStart)) {
            $loadRepayWhere .= " AND temp.repay_time >= " . strtotime($repayTimeStart);
        }

        $repayTimeEnd = trim($_REQUEST['repay_time_end']);
        if (strlen($repayTimeEnd)) {
            $loadRepayWhere .= " AND temp.repay_time <= " . strtotime($repayTimeEnd);
        }

        if ($loadRepayWhere) {
            $sql = "SELECT GROUP_CONCAT(DISTINCT batch_id) FROM (
                      SELECT batch_id, repay_time FROM firstp2p_exchange_batch_repay AS tmp WHERE status = 1 AND  1 > (
                        SELECT COUNT(id) FROM firstp2p_exchange_batch_repay WHERE status = 1 AND tmp.batch_id = batch_id AND tmp.id > id
                      )
                    ) AS temp WHERE 1 = 1 " . $loadRepayWhere;
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            return $ids ? $ids : '-1';
        }

        return '';
    }

    public function index() {
        $agencyInfo = M('deal_agency')->where(" `type` = 9 ")->findAll();
        $jysList = array();
        foreach ($agencyInfo as $item) {
        $jysList[$item['id']] = $item;
        }
        $this->assign('jysList', $jysList);

        $where = "deal_status = 2";

        $batchId = trim($_REQUEST['batch_id']);
        if (strlen($batchId)) {
            $where .= " AND id = " . intval($batchId);
        }

        $projectIds = $this->_queryProjectIdsFromProject();
        if ($projectIds) {
            $where .= sprintf(" AND pro_id IN(%s)", $projectIds);
        }

        $batchIds = $this->_queryBatchIdsFromBatchRepay();
        if ($batchIds) {
            $where .= sprintf(" AND id IN(%s)", $batchIds);
        }

        $this->_list(M('ExchangeBatch'), $where);
        $list = $this->view->tVar['list'];
        if (empty($list)) {
            return $this->display();
        }

        $projectIds = $batchIds = [];
        foreach ($list as $item) {
            $projectIds[$item['pro_id']] = $item['pro_id'];
            $batchIds[$item['id']] = $item['id'];
        }

        $consultUids = $publishUids = $projectList = [];
        $projectInfo = M('ExchangeProject')->where(['id' => ['IN', $projectIds]])->findAll();
        foreach ($projectInfo as $item) {
            $projectList[$item['id']] = $item;
            $consultUids[$item['consult_id']] = $item['consult_id'];
            $publishUids[$item['fx_uid']] = $item['fx_uid'];
        }

        $publishList = [];
        $userList = $GLOBALS['db']->get_slave()->getAll(sprintf("SELECT id, real_name FROM firstp2p_user WHERE id IN(%s)", implode(",", $publishUids)));
        foreach ($userList as $item) {
            $publishList[$item['id']] = $item;
        }

        $agencyList = [];
        $agencyInfo = M('DealAgency')->where(['id' => ['IN', $consultUids]])->findAll();
        foreach ($agencyInfo as $item) {
            $agencyList[$item['id']] = $item;
        }

        $repayList = [];
        $sql = "SELECT * FROM firstp2p_exchange_batch_repay AS tmp WHERE status = 1 AND  1 > (
                    SELECT COUNT(id) FROM firstp2p_exchange_batch_repay WHERE status = 1 AND tmp.batch_id = batch_id AND tmp.id > id
                )";
        $repayInfo = $GLOBALS['db']->get_slave()->getAll($sql);
        foreach ($repayInfo as $item) {
            $repayList[$item['batch_id']] = $item;
        }

        $this->assign("projectList", $projectList);
        $this->assign("publishList", $publishList);
        $this->assign("agencyList", $agencyList);
        $this->assign("repayList", $repayList);

        return $this->display();
    }

    private function _setProjectAndBatchInfo($batchId) {
        $batchInfo = M('ExchangeBatch')->find($batchId);
        if (!$batchInfo) {
            $this->error("找不到批次信息!");
            return false;
        }

        $projectInfo = M('ExchangeProject')->find($batchInfo['pro_id']);
        if (!$projectInfo) {
            $this->error("找不到项目信息!");
            return false;
        }

        $this->assign("batchInfo", $batchInfo);
        $this->assign("projectInfo", $projectInfo);

        return ['projectInfo' => $projectInfo, 'batchInfo' => $batchInfo];
    }

    public function normalPay() {
        $batchId = intval($_REQUEST['batch_id']);
        $queryRes = $this->_setProjectAndBatchInfo($batchId);
        if (!$queryRes) {
            Logger::error("查询项目或批次信息错误, batchId: " . $batchId);
            return false;
        }

        $batchInfo = $queryRes['batchInfo'];
        if ($batchInfo['deal_status'] != 2) {
            return $this->error('批次必须处在还款中才可以强制还款!');
        }

        $repayList = M('ExchangeBatchRepay')->where(['is_plan' => 1, 'batch_id' => $batchId])->order('id ASC')->findAll();
        if (!$repayList) {
            return $this->error("找不到还款计划!");
        }

        $this->assign('repayList', $repayList);
        return $this->display();
    }

    public function doNormalPay() {
        $repayIds  = array_map('intval', @$_REQUEST['repay_ids']);
        if (empty($repayIds)) {
            return $this->error("没有选择要强制还款的项目！");
        }

        $batchId = intval($_REQUEST['batch_id']);
        $queryRes = $this->_setProjectAndBatchInfo($batchId);
        if (!$queryRes) {
            Logger::error("查询项目或批次信息错误, batchId: " . $batchId);
            return $this->error("强制还款失败！");
        }

        $batchInfo = $queryRes['batchInfo'];
        if ($batchInfo['deal_status'] != 2) {
            return $this->error('批次必须处在还款中才可以强制还款!');
        }

        $minRepayId = min($repayIds);
        $repayIds   = implode(",", $repayIds);
        $projectId  = $queryRes['projectInfo']['id'];
        $db = \libs\db\Db::getInstance('firstp2p', 'master');

        $sql = "SELECT id FROM firstp2p_exchange_batch_repay WHERE id < %s AND batch_id = %s AND status = 1";
        $result = $db->getAll(sprintf($sql, $minRepayId, $batchId));
        if ($result) {
            return $this->error('选择强制还款项目之前存在未还项目!');
        }

        // 判断批次、项目是否还清
        $leftBatchCount = PHP_INT_MAX;
        $leftBatchRepayCount = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_exchange_batch_repay WHERE batch_id = %s AND status = 1 AND id NOT IN (%s)", $batchId, $repayIds)); // 剩余批次的还款计划数
        if ($leftBatchRepayCount <= 0) {
            $leftBatchCount = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_exchange_batch WHERE deal_status != 3 AND pro_id = %s AND id != %s", $projectId, $batchId)); // 剩余项目的其它批次数
        }
        if (!is_numeric($leftBatchRepayCount) || !is_numeric($leftBatchCount)) {
            Logger::error("查询剩余还款计划或查询剩余批次信息错误, batchId: " . $batchId);
            return $this->error("强制还款失败！");
        }

        // 开始还款操作
        $logs = ['batch_repay_ids' => $repayIds];
        $db->startTrans();

        // 更新还款计划
        $sql = "UPDATE firstp2p_exchange_batch_repay SET status = 2, update_time = %s, is_actually = 1, pay_time = %s WHERE status = 1 AND batch_id = %s AND id IN(%s)";
        $result = $db->query(sprintf($sql, time(), time(), $batchId, $repayIds));
        if (!$result) {
            Logger::error("更新还款状态失败, repayIds: " . $repayIds);
            $db->rollback();
            return $this->error("强制还款失败!");
        }

        // 更新回款计划
        $sql = "UPDATE firstp2p_exchange_load_repay SET status = 2, update_time = %s, is_actually = 1, pay_time = %s WHERE status = 1 AND batch_id = %s AND repay_id IN(%s)";
        $result = $db->query(sprintf($sql, time(), time(), $batchId, $repayIds));
        if (!$result) {
            Logger::error("更新回款状态失败, repayIds: " . $repayIds);
            $db->rollback();
            return $this->error("强制还款失败!");
        }

        // 还款计划已经还清
        if ($leftBatchRepayCount <= 0) {
            $logs['batch_id'] = $batchId;
            $result = $db->query(sprintf("UPDATE firstp2p_exchange_batch SET deal_status = 3, utime = '%s' WHERE deal_status = 2 AND id = %s", date("Y-m-d H:i:s"), $batchId));
            if (!$result) {
                Logger::error("更新批次状态为已还清失败, repayIds: " . $repayIds);
                $db->rollback();
                return $this->error("强制还款失败!");
            }
        }

        // 批次已还清
        if ($leftBatchCount <= 0) {
            $logs['project_id'] = $projectId;
            $result = $db->query(sprintf("UPDATE firstp2p_exchange_project SET deal_status = 4, utime = '%s' WHERE deal_status = 3 AND id = %s", date("Y-m-d H:i:s"), $projectId));
            if (!$result) {
                Logger::error("更新项目状态为已还清失败, repayIds: " . $repayIds);
                $db->rollback();
                return $this->error("强制还款失败!");
            }
        }

        $db->commit();

        save_log("强制还款成功", 1, [], $logs);
        if ($leftBatchCount <= 0) {
            $function  = '\core\service\ExchangeProjectService::synProjectStatus';
            JobsModel::instance()->addJob($function, array(array('projectId' => $projectId)));
        }
        return $this->success("强制还款成功!", 0, u("ExchangeRepayList/index"));
    }

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

    public function prePay() {
        $batchId = intval($_REQUEST['batch_id']);
        $queryRes = $this->_setProjectAndBatchInfo($batchId);
        if (!$queryRes) {
            Logger::error("查询项目或批次信息错误, batchId: " . $batchId);
            return false;
        }

        $projectInfo = $queryRes['projectInfo'];
        $batchInfo   = $queryRes['batchInfo'];
        if ($batchInfo['deal_status'] != 2) {
            return $this->error('批次必须处在还款中才可以提前还款!');
        }

        $sql = "SELECT SUM(repay_money) AS total_repay_money, SUM(principal) AS total_principal, SUM(interest) AS total_interest, SUM(invest_adviser_fee) AS total_invest_adviser_fee,
                SUM(publish_server_fee) AS total_publish_server_fee,SUM(consult_fee) AS total_consult_fee, SUM(guarantee_fee) AS total_guarantee_fee, SUM(hang_server_fee) AS total_hang_server_fee
                FROM firstp2p_exchange_batch_repay WHERE status = 1 AND batch_id = " . $batchId;
        $batchStat = $GLOBALS['db']->get_slave()->getRow($sql);
        if (!$batchStat) {
            return $this->error("查询批次信息失败!");
        }

        if ($projectInfo['repay_type'] == 1) {
            $endBatchRepayTime = strtotime(sprintf(" + %s day", $projectInfo['repay_time']), $batchInfo['repay_start_time']);
        } else {
            $endBatchRepayTime = $this->_timeAddMonth($batchInfo['repay_start_time'], $projectInfo['repay_time']);
        }
        $endBatchRepayTime = strtotime(date("Y-m-d", $endBatchRepayTime));

        $startTime = strtotime(date("Y-m-d", $batchInfo['repay_start_time'] + $projectInfo['lock_days'] * 24 * 60 * 60));

        $this->assign('batchStat', $batchStat);
        $this->assign('endBatchRepayTime', $endBatchRepayTime);
        $this->assign('startTime',   $startTime);

        $selectedRepayTime = trim($_REQUEST['repay_time']);
        if (empty($selectedRepayTime)) {
            if (isset($_REQUEST['calculate']) || isset($_REQUEST['download']) || isset($_REQUEST['preRepay'])) {
                return $this->error('参数计息结束日不能为空!');
            } else {
                return $this->display();  // 不是按钮进入
           }
        }

        if ($selectedRepayTime != date("Y-m-d", strtotime($selectedRepayTime))) {
            $this->error("参数计息结束日错误!");
            return false;
        }

        $selectedRepayTime = strtotime($selectedRepayTime);
        if ($selectedRepayTime >= $endBatchRepayTime) {
            $this->error("计息结束日不能大于到期日期!");
            return false;
        }

        $sql = "SELECT MAX(repay_time) AS last_repay_time  FROM firstp2p_exchange_batch_repay WHERE batch_id = %s AND is_actually = 1 AND status != 1";
        $lastBatchRepayTime = $GLOBALS['db']->get_slave()->getOne(sprintf($sql, $batchInfo['id']));
        if (empty($lastBatchRepayTime)) {
            $lastBatchRepayTime = $batchInfo['repay_start_time'];
        }
        if ($selectedRepayTime <= $lastBatchRepayTime) {
            $this->error(sprintf("计息结束日不能小于上次还款日(%s)!", date("Y-m-d", $lastBatchRepayTime)));
            return false;
        }

        $this->assign('selectedRepayTime',   $selectedRepayTime);
        $this->assign('lastBatchRepayTime',  strtotime(date("Y-m-d", $lastBatchRepayTime)));
        $this->assign('startBatchRepayTime', strtotime(date("Y-m-d", $batchInfo['repay_start_time'])));

        // 回款明细下载
        if (!empty($_REQUEST['download'])) {
            return $this->_download();
        }

        // 提前还款
        if (!empty($_REQUEST['preRepay'])) {
            return $this->_doPrePay();
        }

        // 计算
        return $this->_calculate();
    }

    private function _calculate($returnData = false) {
        $tplVars = $this->view->tVar;
        $batchInfo   = $tplVars['batchInfo'];
        $projectInfo = $tplVars['projectInfo'];
        $batchStat   = $tplVars['batchStat'];
        $selectedRepayTime   = $tplVars['selectedRepayTime'];   // 用户选择的最后计息日
        $endBatchRepayTime   = $tplVars['endBatchRepayTime'];   // 还款计划最后一期时间
        $lastBatchRepayTime  = $tplVars['lastBatchRepayTime'];  // 上一次还款的时间
        $startBatchRepayTime = $tplVars['startBatchRepayTime']; // 放款时间

        $loadStat = $this->_download(true);
        if (false == $loadStat) {
            Logger::error(sprintf("查询用户回款数据错误, data:", json_encode($batchInfo)));
            return false;
        }

        $principal = $interest = 0;
        foreach ($loadStat as $loadId => $loadInfo) {
            $principal = $principal + $loadInfo['principal'];
            $interest  = $interest  + $loadInfo['interest'];
        }

        $remainDay  = ($selectedRepayTime - $lastBatchRepayTime) / (24 * 60 * 60); // 剩余时间
        $penaltyFee = $principal * $projectInfo['ahead_repay_rate'] / 10000000;

        $diffDay = ($selectedRepayTime - $startBatchRepayTime) / (24 * 60 * 60);
        $investAdviserFee = $projectInfo['invest_adviser_type'] == 1 ? 0 : $batchInfo['amount'] * $diffDay * $batchInfo['invest_adviser_rate'] / 10000000 / 360;
        $publishServerFee = $projectInfo['publish_server_type'] == 1 ? 0 : $batchInfo['amount'] * $diffDay * $batchInfo['publish_server_rate'] / 10000000 / 360;
        $guaranteeFee  = $projectInfo['guarantee_type']   == 1 ? 0 : $batchInfo['amount'] * $diffDay * $batchInfo['guarantee_rate']   / 10000000 / 360;
        $consultFee    = $projectInfo['consult_type']     == 1 ? 0 : $batchInfo['amount'] * $diffDay * $batchInfo['consult_rate']     / 10000000 / 360;
        $hangServerFee = $projectInfo['hang_server_type'] == 1 ? 0 : $batchInfo['amount'] * $diffDay * $batchInfo['hang_server_rate'] / 10000000 / 360;

        $calculate = [
            'remainDay'  => $remainDay,
            'principal'  => $this->_floorfix($principal),
            'interest'   => $this->_floorfix($interest),
            'penaltyFee' => $this->_floorfix($penaltyFee),
            'investAdviserFee'  => $this->_floorfix($investAdviserFee),
            'publishServerFee'  => $this->_floorfix($publishServerFee),
            'guaranteeFee'      => $this->_floorfix($guaranteeFee),
            'consultFee'        => $this->_floorfix($consultFee),
            'hangServerFee'     => $this->_floorfix($hangServerFee),
            'selectedRepayDay'  => date("Y-m-d", $selectedRepayTime),
        ];
        $calculate['repayMoney'] = $calculate['principal'] + $calculate['interest'] + $calculate['penaltyFee'] + $calculate['investAdviserFee'] +
                                   $calculate['publishServerFee'] + $calculate['guaranteeFee'] + $calculate['consultFee'] + $calculate['hangServerFee'];

        if ($returnData) {
            return $calculate;
        }

        $this->assign('calculate', $calculate);
        return $this->display();
    }

    private function _download($returnData = false) {
        $tplVars = $this->view->tVar;
        $batchInfo   = $tplVars['batchInfo'];
        $projectInfo = $tplVars['projectInfo'];
        $batchStat   = $tplVars['batchStat'];
        $selectedRepayTime   = $tplVars['selectedRepayTime'];   // 用户选择的最后计息日
        $endBatchRepayTime   = $tplVars['endBatchRepayTime'];   // 还款计划最后一期时间
        $lastBatchRepayTime  = $tplVars['lastBatchRepayTime'];  // 上一次还款的时间
        $startBatchRepayTime = $tplVars['startBatchRepayTime']; // 放款时间

        $sql = sprintf("SELECT load_id, SUM(principal) AS total_principal FROM firstp2p_exchange_load_repay WHERE is_plan = 1 AND status = 1 AND batch_id = %s GROUP BY load_id", $batchInfo['id']);
        $loadRepayStat = $GLOBALS['db']->get_slave()->getAll($sql);
        if (!$loadRepayStat) {
            $this->error("查询用户回款数据错误!");
            return false;
        }

        $loadRepayInfo = [];
        foreach ($loadRepayStat as $item) {
            $loadRepayInfo[$item['load_id']] = $item['total_principal'];
        }
        $loadInfo = M("ExchangeLoad")->where(['status' => 1, 'id' => ['IN', array_keys($loadRepayInfo)]])->findAll();
        if (!$loadInfo) {
            $this->error("查询投资数据失败!");
            return false;
        }

        // 剩余时间
        $remainDay = ($selectedRepayTime - $lastBatchRepayTime) / (24 * 60 * 60);
        $loadReapy = $loadList = [];
        foreach ($loadInfo as $item) {
            $loadList[$item['id']] = $item;
        }
        foreach ($loadRepayInfo as $loadId => $principal) {
            $interest = $this->_floorfix($principal * $remainDay * $projectInfo['expect_year_rate'] / 10000000 / 360);
            $loadReapy[$loadId] = ['loadId'    => $loadId, 'principal' => $principal, 'interest'  => $interest];
        }

        if ($returnData) {
            return $loadReapy;
        }

        $title = ['借款标题', '收款方账号', '收款方户名', '收款方开户行所在省', '收款方开户行所在市', '收款方开户行名称', '金额', '用途'];
        $filename = sprintf("回款计划_%s_%s_%s.csv", $projectInfo['id'], $batchInfo['id'], date("YmdHis"));
        header('Content-Type: application/vnd.ms-excel;charset=GBK');
        header("Content-Disposition: attachment; filename={$filename}.csv");

        $handle = fopen('php://output', 'w+');
        $this->_outCsv($handle, $title);

        foreach ($loadReapy as $loadId => $item) {
            $row = [
                $projectInfo['jys_number'],
                sprintf("%s", $loadList[$loadId]['bank_no']),
                $loadList[$loadId]['real_name'],
                $loadList[$loadId]['bank_province'],
                $loadList[$loadId]['bank_city'],
                $loadList[$loadId]['bank_name'],
                $this->_floorfix($item['principal'] + $item['interest']) / 100,
                sprintf("%s第%s期%s", $projectInfo['jys_number'], $batchInfo['batch_number'], '付本息'),
            ];
            $this->_outCsv($handle, $row);
        }

        fclose($handle);
        exit(0);
    }

    private function _outCsv($handle, $row) {
        foreach ($row as $index => $item) {
            $row[$index] = sprintf("\t%s", iconv('UTF-8', 'GBK', $item));
        }
        fputcsv($handle, $row);
    }

    public function _doPrePay() {
        $tplVars = $this->view->tVar;
        $batchInfo   = $tplVars['batchInfo'];
        $projectInfo = $tplVars['projectInfo'];
        $batchStat   = $tplVars['batchStat'];
        $selectedRepayTime   = $tplVars['selectedRepayTime'];   // 用户选择的最后计息日
        $endBatchRepayTime   = $tplVars['endBatchRepayTime'];   // 还款计划最后一期时间
        $lastBatchRepayTime  = $tplVars['lastBatchRepayTime'];  // 上一次还款的时间
        $startBatchRepayTime = $tplVars['startBatchRepayTime']; // 放款时间

        $calculate = $this->_calculate(true);
        if (!$calculate) {
            Logger::error("提前还款, 计算还款数据失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        $download = $this->_download(true);
        if (!$download) {
            Logger::error("提前还款, 计算回款数据失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        // 剩余项目的其它批次数
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $leftBatchCount = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_exchange_batch WHERE deal_status != 3 AND pro_id = %s AND id != %s", $projectInfo['id'], $batchInfo['id']));
        if (!is_numeric($leftBatchCount)) {
            Logger::error("提前还款, 查询剩余批次失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        $db->startTrans();

        // 改还款计划
        $sql = "UPDATE firstp2p_exchange_batch_repay SET status = 3, update_time = %s, pay_time = %s WHERE batch_id = %s AND status = 1";
        $result = $db->query(sprintf($sql, time(), time(), $batchInfo['id']));
        if (!$result) {
            $db->rollback();
            Logger::error("更新批次还款计划失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        // 新增提前还款记录
        $batchRepay = [
            'batch_id'    => $batchInfo['id'],
            'repay_time'  => $selectedRepayTime,
            'repay_money' => $calculate['repayMoney'],
            'principal'   => $calculate['principal'],
            'interest'    => $calculate['interest'],
            'consult_fee' => $calculate['consultFee'],
            'guarantee_fee'      => $calculate['guaranteeFee'],
            'hang_server_fee'    => $calculate['hangServerFee'],
            'invest_adviser_fee' => $calculate['investAdviserFee'],
            'publish_server_fee' => $calculate['publishServerFee'],
            'status'      => 3,
            'is_plan'     => 2,
            'is_actually' => 1,
            'create_time' => time(),
            'update_time' => time(),
            'pay_time'    => time(),
            'penalty_fee' => $calculate['penaltyFee'],
        ];
        $batchRepayId = $db->insert('firstp2p_exchange_batch_repay', $batchRepay);
        if (!$batchRepayId) {
            $db->rollback();
            Logger::error("提前还款, 插入数据到还款表失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        // 改回款计划
        $sql = "UPDATE firstp2p_exchange_load_repay SET status = 3, update_time = %s, pay_time = %s WHERE batch_id = %s AND status = 1";
        $result = $db->query(sprintf($sql, time(), time(), $batchInfo['id']));
        if (!$result) {
            $db->rollback();
            Logger::error("更新回款计划失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        $row = [
            'batch_id'    => $batchInfo['id'],
            'repay_id'    => $batchRepayId,
            'repay_time'  => $selectedRepayTime,
            'status'      => 3,
            'is_plan'     => 2,
            'is_actually' => 1,
            'pay_time'    => time(),
            'create_time' => time(),
            'update_time' => time(),
        ];
        // 新增提前回款记录
        foreach ($download as $loadId => $item) {
            $row['load_id']   = $loadId;
            $row['principal'] = $item['principal'];
            $row['interest']  = $this->_floorfix($item['interest']);
            $row['repay_money'] = $row['principal'] + $row['interest'];
            $result = $db->insert('firstp2p_exchange_load_repay', $row);
            if (!$result) {
                $db->rollback();
                Logger::error("提前还款, 插入数据到回款表失败, 批次信息:" . json_encode($batchInfo));
                return $this->error("提前还款失败!");
            }
        }

        // 改批次信息
        $sql = "UPDATE firstp2p_exchange_batch SET deal_status = 3, utime = '%s' WHERE id = %s AND deal_status = 2";
        $result = $db->query(sprintf($sql, date("Y-m-d H:i:s"), $batchInfo['id']));
        if (!$result) {
            $db->rollback();
            Logger::error("更新回款计划失败, 批次信息:" . json_encode($batchInfo));
            return $this->error("提前还款失败!");
        }

        // 改项目信息
        $logs['batch_id'] = $batchInfo['id'];
        if ($leftBatchCount <= 0) {
            $logs['project_id'] = $projectInfo['id'];
            $result = $db->query(sprintf("UPDATE firstp2p_exchange_project SET deal_status = 4, utime = '%s' WHERE id = %s AND deal_status = 3", date("Y-m-d H:i:s"), $projectInfo['id']));
            if (!$result) {
                $db->rollback();
                Logger::error("更新项目状态为已还清失败, 批次信息:" . json_encode($batchInfo));
                return $this->error("提前还款失败!");
            }
        }

        $db->commit();
        save_log("提前还款成功", 1, [], $logs);
        if ($leftBatchCount <= 0) {
            $function  = '\core\service\ExchangeProjectService::synProjectStatus';
            JobsModel::instance()->addJob($function, array(array('projectId' => $projectInfo['id'])));
        }

        return $this->success("提前还款成功", 0, u("ExchangeRepayList/index"));
    }

}
