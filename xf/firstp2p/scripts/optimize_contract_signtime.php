<?php
/**
 * 迁移合同签署时间
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__) . '/../system/utils/logger.php';

use core\dao\ContractModel;
use core\dao\AgencyContractModel;

set_time_limit(0);

// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep optimize_contract_signtime.php | grep -v grep | grep -v {$pid} | grep -v /bin/sh";
if(fread(popen($cmd, "r"), 1024)) {
    echo "进程已经启动\n";
    exit;
}
$stime = microtime(true); //获取程序开始执行的时间
$key = 'move_contract_signtime';
$start_id = \SiteApp::init()->cache->get($key);//从redis中得到计数
$end_id = $start_id = empty($start_id) ? 0 : intval($start_id);

//每分钟处理数据量
$num = 1000;
$condition = sprintf("`id` > %d order by `id` ASC limit %d", $start_id, $num);
$sign_list = AgencyContractModel::instance()->findAllViaSlave($condition, true, '*');

if($sign_list){
    $content_model = new ContractModel();
    try {
        $content_model->db->startTrans();
        foreach($sign_list as $sign){
            $end_id = max($sign['id'], $end_id);
            $contract = $content_model->find($sign['contract_id']);
            if($contract){
                $contract->sign_time = $sign['create_time'];
                $contract->resign_status = $sign['sign_pass'];
                $contract->resign_time = $sign['sign_time'];
                if($contract->save() === false){
                    throw new Exception("agency_contact_id:{$sign['id']}, contract_id: {$sign['contract_id']}, start_id:{$start_id} save faild!");
                }
            }
        }
        \SiteApp::init()->cache->set($key,$end_id);
        $log['contract_sign_time_copy'] = 'success';
        $log['info'] = $start_id.' to '.$end_id;
        logger::wLog($log);
        $content_model->db->commit();
    } catch (Exception $e) {
        $content_model->db->rollback();
        $log['contract_sign_time_copy'] = 'faild';
        $log['info'] = $e->getMessage();
        logger::wLog($log,logger::ERR);
    }
}
$etime = microtime(true);//获取程序执行结束的时间
echo count($sign_list),"条合同数据已保存 agency_contract_id:{$start_id}-{$end_id}";
echo "\n用时",number_format($etime - $stime, 2),"秒\n";
