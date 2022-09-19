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

function process500($startId,$offset=500,$createTime='9999999999'){
    $sql = "SELECT * FROM `firstp2p_contract` WHERE `create_time`<$createTime ORDER BY `id` ASC LIMIT $startId,$offset";
    $list = ContractModel::instance()->findAllBySql($sql,true,array(),true);
    if(empty($list)){
        //处理完了，停止
        return array('maxId'=>$maxId,'count'=>$count);
    }
    // 当前最大的ID
    $maxId = 0;
    $ret = array();
    foreach($list as $one){
        $maxId = intval($one['id']);
        $content = ContractContentModel::instance()->find($maxId);
        if(empty($content)){
            echo sprintf( "contract [ id:%s ] migrate failed -- get from db failed \n",$maxId );
            continue;
        }
        // 写入aerospike
        $key = \SiteApp::init()->aerospike->createKey($maxId);
        $saveObj = new AerospikeSaveObj;
        $saveObj->content = gzcompress($content);
        $bins = array(
            "id"=>$maxId,
            "content"=>$saveObj,
        );
        $ret = \SiteApp::init()->aerospike->set($key,$bins);
        if( $ret == false ){
            $sql = sprintf("INSERT INTO `firstp2p_contract_content_bak` (`contract_id`, `content`) VALUES ('%d', '%s')",
                            ContractContentModel::instance()->escape($maxId),
                            ContractContentModel::instance()->escape($content));
            $ret = ContractContentModel::instance()->execute($sql);
            if(empty($ret)){
                echo sprintf( "contract [ id:%s ] migrate failed \n",$one['id'] ); 
            }else{
                echo sprintf( "contract [ id:%s ] migrate failed, but insert in to table bak \n",$one['id'] ); 
            }
        }else{
            echo sprintf( "contract [ id:%s ] migrate success \n",$one['id'] );
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


$time = time();
$processCount = 500;
// 记录操作完成数量
$allCount = 0;
$count = 0;
do{
    $ret = process500($start,$processCount,$time);
    $count = $ret['count'];
    $allCount += $count;
    echo sprintf("current process [ max_id:%s ] migrated \n",$ret['maxId']);
    usleep(50);
    $start+=$processCount;
}while($count!=0);

echo sprintf("数据迁移完毕，共导出合同 %s 份\n",$allCount);

