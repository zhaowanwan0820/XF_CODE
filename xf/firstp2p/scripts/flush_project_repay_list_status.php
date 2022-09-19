<?php

/**
 *刷新project repay list表已还状态
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';

use core\dao\DealProjectModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\ProjectRepayListModel;

use libs\utils\Logger;


set_time_limit(0);
ini_set('memory_limit', '2048M');

//获取project_repay_list记录数量

$countSql = "SELECT count(1) AS num FROM firstp2p_project_repay_list";
$prlModel = new ProjectRepayListModel();
$dpModel = new DealProjectModel();
$drModel = new DealProjectModel();
$dModel = new DealModel();

$count = $prlModel->countBySql($countSql);
for($i = 1;$i<=$count;$i++){
    $prlSql = "SELECT * FROM firstp2p_project_repay_list LIMIT ".($i-1).",1";
    $prl = $prlModel->findBySql($prlSql);
    $projectId = $prl['project_id'];
    $repayTime = $prl['repay_time'];
    $deals = $dModel->getDealByProId($projectId);
    if(count($deals) == 0){
        continue;
    }
    $dealArr = array();
    foreach($deals as $deal){
        $dealArr[] = $deal['id'];
    }

    $dealIdsStr = implode(',',$dealArr);
    $drRecordSql = "SELECT * FROM firstp2p_deal_repay WHERE deal_id in (".$dealIdsStr.")  AND repay_time = '".$repayTime."' limit 1";
    $repayRecord = $drModel->findBySql($drRecordSql);

    if($repayRecord['status'] > 0){
        $dpModel->changeProjectRepayList($projectId,array($repayRecord['id']));
    }
}
