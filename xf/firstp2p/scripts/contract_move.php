<?php
/**
 * firstp2p_contract 合同数据迁移
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__) . '/../system/utils/logger.php';

use core\dao\ContractModel;
use core\dao\ContractContentModel;

set_time_limit(0);
// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep contract_move.php | grep -v grep | grep -v {$pid} | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}

$stime = microtime(true); //获取程序开始执行的时间
$key = 'copy_contract_cnt';
$cnt = \SiteApp::init()->cache->get($key);   //从redis中得到计数
$max_id = $cnt = empty($cnt) ? 0 :$cnt;
//每分钟处理数据量
$num = 1000;

$sql = "SELECT `id`, `content` FROM `firstp2p_contract` where `id` > {$cnt} order by `id` limit {$num}";
//$cont_list = ContractModel::instance()->findAll("content != '' limit ".$num, false, 'id,content');
$cont_list = ContractModel::instance()->findAllBySql($sql, false);

$id_arr = array();
if($cont_list){
    $content_model = new ContractContentModel();

    try {
        $content_model->db->startTrans();
        foreach($cont_list as $cont){
            if ($cont->id > $max_id) {
                $max_id = $cont->id;
            }
            if ($cont->content != "") {
                $add = $content_model->add($cont->id, $cont->content);

                if($add){
                    $id_arr[] = $cont->id;
                    //$cont->content = '';
                    //$cont->save();
                    //保险起见还是一个一个的update吧
                } else{
                    throw new Exception(" add content contract_id:".$cont->id." faild!");
                }
            }
        }

        \SiteApp::init()->cache->set($key,$max_id);
        //记录跑到哪里了。
        $info['start_contract_id'] = $cnt;
        $info['max_id'] = $max_id;
        logger::wLog($info);    

        $content_model->db->commit();
    } catch (Exception $e) {
        $content_model->db->rollback();
        $info['start_contract_id'] = $cnt;
        $info['max_id'] = $max_id;
        logger::wLog($info,logger::ERR);
    }

}

echo "\n ----------------------------------\n",count($id_arr),"个合同content已保存 {$key}:{$cnt}-{$max_id}";

$etime = microtime(true);//获取程序执行结束的时间
echo "\n用时",number_format($etime - $stime, 2),"秒\n";
