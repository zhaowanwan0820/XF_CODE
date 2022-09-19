<?php

/**
 *  线下交易批次还款相关业务
 */
use libs\utils\Logger;

class ExchangeBatchRepayAction extends CommonAction {

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

        $condition = ['batch_id' => $batchId, 'is_plan' => 1];
        $this->_list(M('ExchangeBatchRepay'), $condition, 'id', true);

        $projectInfo = $queryRes['projectInfo'];
        $consultInfo = M('DealAgency')->find($projectInfo['consult_id']);
        $this->assign('consultInfo', $consultInfo);

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
        $repayList = M('ExchangeBatchRepay')->where($condition)->order('id ASC')->findAll();
        if (!$repayList) {
            return $this->error("找不到还款计划!");
        }

        $batchInfo   = $queryRes['batchInfo'];
        $projectInfo = $queryRes['projectInfo'];
        $publishInfo = $queryRes['publishInfo'];

        $agencyIds   = [$projectInfo['consult_id'], $projectInfo['business_manage_id'], $projectInfo['invest_adviser_id'], $projectInfo['guarantee_id'], $projectInfo['jys_id']];
        $agencyInfo  = M('DealAgency')->where(['id' => ['IN', $agencyIds]])->findAll();
        $agencyList = [];
        foreach ($agencyInfo as $item) {
            $agencyList[$item['id']] = $item;
        }

        $filename = sprintf("还款计划_%s_%s_%s.csv", $projectInfo['id'], $batchInfo['id'], date("YmdHis"));
        header('Content-Type: application/vnd.ms-excel;charset=GBK');
        header("Content-Disposition: attachment; filename={$filename}");
        $title = [
            '交易所备案编号', '批次编号', '发行人名称',
            '还款日', '待还金额', '待还本息',
            '业务管理方', '发行服务费', '投资顾问机构',
            '投资顾问费', '咨询机构', '咨询费',
            '担保机构', '担保费', '交易所', '挂牌费'
        ];

        $handle = fopen('php://output', 'w+');
        $this->_outCsv($handle, $title);

        foreach ($repayList as $item) {
            if ($item['principal'] <= 0 && $item['interest'] <= 0) {
                continue; // 第一条放款前收手续费不导出
            }
            $row = [
                $projectInfo['jys_number'], $batchInfo['id'], $publishInfo['real_name'],
                date("Y-m-d", $item['repay_time']),  $item['repay_money'] / 100, ($item['principal'] + $item['interest']) / 100,
                $agencyList[$projectInfo['business_manage_id']]['name'], $item['publish_server_fee'] / 100, $agencyList[$projectInfo['invest_adviser_id']]['name'],
                $item['invest_adviser_fee']/100, $agencyList[$projectInfo['consult_id']]['name'], $item['consult_fee']/100,
                $agencyList[$projectInfo['guarantee_id']]['name'], $item['guarantee_fee']/100, $agencyList[$projectInfo['jys_id']]['name'], $item['hang_server_fee']/100
            ];
            $this->_outCsv($handle, $row);
        }

        $this->_outCsv($handle, []);
        $this->_outCsv($handle, ['注：发行人需于还款日前一工作日将还款资金支付至交易所账户']);

        fclose($handle);

        return exit(0);
    }

}
