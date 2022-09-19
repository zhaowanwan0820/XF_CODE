<?php

/**
 *  线下交易批次回款相关业务
 */
use libs\utils\Logger;

class ExchangeLoadRepayAction extends CommonAction {

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

        $publishInfo = M('User')->find($projectInfo['fx_uid']);
        if (!$publishInfo) {
            $this->error("找不到发行人信息!");
            return false;
        }

        $this->assign("batchInfo", $batchInfo);
        $this->assign("projectInfo", $projectInfo);
        $this->assign("publishInfo", $publishInfo);

        return ['projectInfo' => $projectInfo, 'batchInfo' => $batchInfo, 'publishInfo' => $publishInfo];
    }

    public function plan() {
        $batchId = intval($_REQUEST['batch_id']);
        $queryRes = $this->_setProjectAndBatchInfo($batchId);
        if (!$queryRes) {
            Logger::error("查询项目或批次信息错误, batchId: " . $batchId);
            return false;
        }

        $projectInfo = $queryRes['projectInfo'];
        $consultInfo = M('DealAgency')->find($projectInfo['consult_id']);
        $this->assign('consultInfo', $consultInfo);

        $condition = ['batch_id' => $batchId, 'is_plan' => 1];
        $this->_list(M('ExchangeLoadRepay'), $condition, 'repay_id', true);

        $loadIds = [];
        foreach ($this->view->tVar['list'] as $item) {
            $loadIds[$item['load_id']] = $item['load_id'];
        }

        if (!$loadIds) {
            return $this->display();
        }

        $condition = ['id' => ['IN', array_keys($loadIds)], 'status' => 1];
        $loadUsers = M('ExchangeLoad')->where($condition)->findAll();

        $loadList = [];
        foreach ($loadUsers as $item) {
            $loadList[$item['id']] = $item;
        }

        $this->assign('loadList', $loadList);
        return $this->display();
    }

    private function _outCsv($handle, $row) {
        foreach ($row as $index => $item) {
            $row[$index] = sprintf("\t%s", iconv('UTF-8', 'GBK', $item));
        }
        fputcsv($handle, $row);
    }

    public function planExport() {
        $batchId  = intval($_REQUEST['batch_id']);
        $queryRes = $this->_setProjectAndBatchInfo($batchId);
        if (!$queryRes) {
            Logger::error("查询项目或批次信息错误, batchId: " . $batchId);
            return false;
        }

        $condition = ['batch_id' => $batchId, 'is_plan' => 1];
        $repayId = intval($_REQUEST['repay_id']);
        if ($repayId) {
            $condition['repay_id'] = $repayId;
        }

        $repayList = M('ExchangeLoadRepay')->where($condition)->order('repay_id ASC')->findAll();
        if (!$repayList) {
            return $this->error("找不到还款计划!");
        }

        $loadIds = [];
        foreach ($repayList as $item) {
            $loadIds[$item['load_id']] = $item['load_id'];
        }

        $condition = ['id' => ['IN', array_keys($loadIds)], 'status' => 1];
        $loadUsers = M('ExchangeLoad')->where($condition)->findAll();

        $loadList = [];
        foreach ($loadUsers as $item) {
            $loadList[$item['id']] = $item;
        }

        $title = ['借款标题', '收款方账号', '收款方户名', '收款方开户行所在省', '收款方开户行所在市', '收款方开户行名称', '金额', '用途'];
        $batchInfo   = $queryRes['batchInfo'];
        $projectInfo = $queryRes['projectInfo'];

        $filename = sprintf("回款计划_%s_%s_%s.csv", $projectInfo['id'], $batchInfo['id'], date("YmdHis"));
        header('Content-Type: application/vnd.ms-excel;charset=GBK');
        header("Content-Disposition: attachment; filename={$filename}");

        $handle = fopen('php://output', 'w+');
        $this->_outCsv($handle, $title);

        foreach ($repayList as $item) {
            $row = [
                $projectInfo['jys_number'],
                sprintf("%s", $loadList[$item['load_id']]['bank_no']),
                $loadList[$item['load_id']]['real_name'],
                $loadList[$item['load_id']]['bank_province'],
                $loadList[$item['load_id']]['bank_city'],
                $loadList[$item['load_id']]['bank_name'],
                $item['repay_money'] / 100,
                sprintf("%s第%s期%s", $projectInfo['jys_number'], $batchInfo['batch_number'], $item['principal'] > 0 ? '付本息' : '付息'),
            ];
            $this->_outCsv($handle, $row);
        }
        fclose($handle);

        return exit(0);
    }

}
