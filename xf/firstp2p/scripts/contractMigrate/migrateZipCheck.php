<?php

set_time_limit(0);
/**
 * firstp2p_contract 合同数据迁移
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__) . '/../../system/utils/logger.php';

use core\dao\ContractModel;
use core\dao\ContractContentModel;
use libs\aerospike\AerospikeSaveObj;
// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep contract_move.php | grep -v grep | grep -v {$pid} | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}

function process500($startId,$offset=500){
    $sql = "SELECT * FROM `firstp2p_contract` ORDER BY `id` ASC LIMIT $startId,$offset";
    $list = ContractModel::instance()->findAllBySql($sql,true,array(),true);
    if(empty($list)){
        //处理完了，停止
        return array('maxId'=>-1,'count'=>$count);
    }
    $maxId = 0;
    $ret = array();
    $keys = array();

    foreach($list as $one){
        $maxId = intval($one['id']);
        $content = ContractContentModel::instance()->findFromDB($maxId);
        $key = \SiteApp::init()->aerospike->createKey($maxId);
        $data = \SiteApp::init()->aerospike->get($key);
        if(!empty($data)){
            $aeroContent = gzuncompress($data['content']->content);
            if ($maxId == $data['id'] && $content == $aeroContent){
                echo sprintf("check [ maxId:%s ] success\n",$maxId);
            }else{
                echo sprintf("check [ id:%s ] failed\n",$maxId);
            }
        }else{
            echo sprintf("file do not exist [ id:%s ]\n",$maxId);
        }
    }
    $count = count($list);
    return array('maxId'=>$maxId,'count'=>$count);

}


//=====================================================================================

$start = 1;

if ( count($argv)!=2 ){
    echo '`which php` migrate.php ${startId}'."\n"; 
    exit(0);
}else{
    if(intval($argv[1]) < 0){
        echo '${startId} must big equal to 0'."\n"; 
        exit(0);
    }else{
        $start = intval($argv[1]);
    }
}


$processCount = 500;
// 记录操作完成数量
$allCount = 0;
$count = 0;
do{
    $ret = process500($start,$processCount);
    $count = $ret['count'];
    $allCount += $count;
    echo sprintf("current process [ max_id:%s ] checked \n",$ret['maxId']);
    usleep(100);
    $start+=$processCount;
}while($count!=0);



echo sprintf("数据迁移完毕，共导出合同 %s 份\n",$allCount);

