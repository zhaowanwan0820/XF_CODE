<?php

require(dirname(__DIR__) . '/app/init.php');


parse_str(@$argv[1], $params);
$batchId = intval(@$params['batchId']);
$runType = @$params['type'];

if ($batchId > 0) {

    $service = new core\service\ExchangeService();

    //批次生成还款计划
    if ($runType == 'batch') {
        $result = $service->genBatchRepayPlan(['batchId' => $batchId]);
    }

    //批次生成回款计划
    if ($runType == 'load') {
        $result = $service->genLoadRepayPlan(['batchId' => $batchId]);
    }

    // 重置还款计划金额
    if ($runType == 'reset') {
        $result = $service->resetBatchRepayMoney(['batchId' => $batchId]);
    }

    // 生成还款和回款计划
    if ($runType == 'gen') {
        $result = $service->genBatchLoadRepayPlan(['batchId' => $batchId]);
    }

    // 重新生成款和回款计划
    if ($runType == 'regen') {
        $result = $service->regenBatchLoadRepayPlan(['batchId' => $batchId]);
    }

    echo ($result ? 'SUCC !' : 'FAIL !') . PHP_EOL;
}
