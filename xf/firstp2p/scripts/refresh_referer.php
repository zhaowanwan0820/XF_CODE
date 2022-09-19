<?php
/**
 *刷firstp2p_user表的referer值：端统一标识.
 */
require_once dirname(__FILE__).'/../app/init.php';

use libs\utils\Logger;
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    define('DES_LOG_PATH','/tmp/referer/log/');
    error_reporting(0);
    if(!isset($argv[1])){
        echo '请输入操作序号:';
        echo PHP_EOL;
        echo '刷数据: 1 ';
        echo PHP_EOL;
        echo '修复: 2  minId maxId limit';
        echo PHP_EOL;
        echo '恢复: 3  ';
        echo PHP_EOL;
        echo '状态: 4  查看执行状态';
        echo PHP_EOL;
        exit;
    }
    make_dir();
    if($argv[1]==1){//刷数据
        $minId  =  $GLOBALS['db']->getInstance('firstp2p','master')->getOne("SELECT MIN(id) FROM `firstp2p_user`");
        $maxId =  $GLOBALS['db']->getInstance('firstp2p','master')->getOne("SELECT MAX(id) FROM `firstp2p_user`");
        if(!$minId){
            $minId=1;
        }
        if(!$maxId){
            exit;
        }
        $startId = $minId-1;
        $endId = $maxId;

        $bingFaNum = 1;
        $bingFaNo = 1;
        if(isset($argv[2]) && isset($argv[3])){//需同时存在
            $bingFaNum =  $argv[2];
            $bingFaNo = $argv[3];

            $count = floor(($maxId-$minId+1)/$bingFaNum);
            $startId = $minId+($bingFaNo-1)*$count-1;//sql语句 id>$startId AND id<=$endId
            $endId = $minId + $bingFaNo*$count-1;
            if($bingFaNo==$bingFaNum){
                $endId = $maxId;
            }

        }
        summery_log("刷数据:并发数{$bingFaNum}-{$bingFaNo},minId:{$minId},maxId:{$maxId},startId:{$startId},endId:{$endId},处理开始" .date("H:i:s")  );
        refresh_referer($startId,$endId,$bingFaNum,$bingFaNo);
    }
    if($argv[1]==2){//修复
        if(!isset($argv[2])||!isset($argv[3])||!isset($argv[4])){
            echo '2 minId maxId limit';
        }
        $minId = $argv[2];
        $maxId = $argv[3];
        $limit = $argv[4];
        repair_data($limit,$minId,$maxId);
        return ;
    }
    if($argv[1]==3){//恢复
        summery_log("恢复:处理开始" .date("H:i:s")  );
        recovery_data();
        summery_log("恢复:处理结束" .date("H:i:s")  );
        return ;
    }
    if($argv[1]==4){
        check_status();//查看状态
        return;
    }


function refresh_referer($startId=0,$endId=1000000000,$bingFaNum,$bingFaNo){

   $fileNameSuffix =  date('Y-m-d');

    $logStr = "刷标识";

    $tableStart = microtime(true);
    $limit = 10000;//每次取1w条

    $tableTotalRowNum = 0;
    $tableTotalUpdateRowNum = 0;
    $startTime = date("H:i:s");


    $fpwCheckFile = fopen(DES_LOG_PATH  ."status_{$fileNameSuffix}.csv", 'a') or die("Unable to open check file!");
    $recoveryDataFile = fopen(DES_LOG_PATH  ."recovery_data.csv", 'a') or die("Unable to open check file!");
    $flag = true;
    while($flag){
        $summary =  do_referer_handle($startId,$limit,$endId,$recoveryDataFile,$bingFaNo);
        $tableTotalRowNum = $tableTotalRowNum+$summary['rowNum'];
        $tableTotalUpdateRowNum = $tableTotalUpdateRowNum+$summary['updateRowNum'];
        $startId = $summary['id'];
        if($startId>=$endId){
            $flag = false;
        }
    }
    $cost = round(microtime(true) - $tableStart, 4);
    $costPs = round($cost/$tableTotalUpdateRowNum,4);
    $neiCun = round(memory_get_usage()/1024/1024, 2) . 'MB';
    fputcsv($fpwCheckFile,array('firstp2p_user',$bingFaNum,$bingFaNo,$tableTotalRowNum,$tableTotalUpdateRowNum,$cost));
    $tmpStr ="刷数据";
    summery_log("{$tmpStr} 并发数{$bingFaNum}-{$bingFaNo},".$logStr."处理结束-".$startTime .'|'. date('H:i:s') ." [读取行数：{$tableTotalRowNum},更新行数:{$tableTotalUpdateRowNum},耗时:{$cost},平均耗时:{$costPs},内存:{$neiCun}]"  );
    fclose($recoveryDataFile);
    fclose($fpwCheckFile);
}

function check_status(){

   $fileNameSuffix =  date('Y-m-d');

    $fpr = fopen(DES_LOG_PATH . "status_{$fileNameSuffix}.csv", 'r') or die("Unable to open status_{$fileNameSuffix}.csv!");
    $tables = array();
    while(!feof($fpr)) {
        $oneLine = fgetcsv($fpr);
        if(!empty($oneLine)){
            $tmpTableName = $oneLine[0];
            if($tables[$tmpTableName]){
                $tables[$tmpTableName][]=$oneLine;
            }else{
                $tables[$tmpTableName] = array();
                $tables[$tmpTableName][] = $oneLine;
            }
        }
    }

    $summery = array();
    $notCompleteNum = 0;
    $totalNum = 0;
    $totalUpdateNum = 0;
    $maxCost = 0;
    $maxCostTable = '';
    foreach($tables as $tableName=>$tableValues){
        $readNum = 0;
        $updateNum = 0;
        $bingFaNum = 1;
        $i=0;
        $bingFaNos = array();
        $tableMaxCost = 0;

        foreach($tableValues as $value){
            $readNum +=$value[3];
            $updateNum +=$value[4];
            if($tableMaxCost<$value[5]){
                $tableMaxCost=$value[5];
            }
            $bingFaNum = $value[1];
            $bingFaNos[]=$value[2];
            $i++;
        }
        sort($bingFaNos);
        $bingFaNoStr = implode(',',$bingFaNos);
        if($i!=$bingFaNum){
            $notCompleteNum++;
        }

        $totalNum +=$readNum;
        $totalUpdateNum +=$updateNum;
        if($maxCost<$tableMaxCost){
            $maxCost = $tableMaxCost;
            $maxCostTable = $tableName;
        }

        $summery[]=array($tableName,$bingFaNum,$i."[{$bingFaNoStr}]",$readNum,$updateNum,$tableMaxCost,round($tableMaxCost/$updateNum,4));
    }
    fclose($fpr);
    print_r(json_encode($summery));
    print_r(PHP_EOL);
    print_r("共迁移".count($tables)."个表,".$notCompleteNum . "个未完成,"."读取总数".$totalNum.",更新总数".$totalUpdateNum.",最大耗时".$maxCost."s,最大耗时表".$maxCostTable.",平均耗时" .round($maxCost/$totalUpdateNum,4)."s");
    print_r(PHP_EOL);
}
/*
 *
 * */
function do_referer_handle($id,$limit,$endId,$recoveryDataFile,$bingFaNo){
  // var_dump($id);var_dump($limit);var_dump($endId);exit;
    $summary = array();

//    $whereStr = " WHERE id>{$id} AND id<={$endId} AND `referer`<>''";
    $whereStr = " WHERE id>{$id} AND id<={$endId} ";
    $sql = "SELECT id,referer  FROM `firstp2p_user` {$whereStr} ORDER BY id ASC  limit 0,{$limit}";
    select_sql_log($sql);
    $res =  $GLOBALS['db']->getInstance('firstp2p','master')->getAll($sql);

    $rowNum = count($res);
    $updateRowNum=0;
    $summary['rowNum']=$rowNum;
    if($rowNum == 0){
        $summary['updateRowNum']=$updateRowNum;
        $summary['id']= $id+$limit;
        return $summary;
    }
    foreach($res as $key =>$row){
            $summary['id']=$row['id'];
            $fieldValue =  $row['referer'];
            if($fieldValue=='3'){//ios不处理
                continue;
            }
            if($fieldValue=='1'||$fieldValue=='5'||$fieldValue=='6'||$fieldValue=='7'){//1:PC  5:后台添加  6:后台批量注册  7.批量注册(前台)
                $newValue='0';//pc
            }else if($fieldValue=='4'){//移动
                $newValue='5';//unknown
            }else if($fieldValue=='9'){//H5
                $newValue='8';//wap
            }else if($fieldValue=='2'){//Android
                $newValue='4';//android
            }else{
                $newValue='5';//unknown
            }
            $sql = "UPDATE `firstp2p_user`  SET `referer`='{$newValue}' WHERE id = {$row['id']}";
            $flag =  $GLOBALS['db']->getInstance('firstp2p','master')->query($sql);
            if(!$flag){
                Logger::wLog($sql .PHP_EOL,Logger::ERR,Logger::FILE,DES_LOG_PATH."error_" .date('y_m_d') .'.log');
            }else{
                fputcsv($recoveryDataFile,array($row['id'],$fieldValue));
            }
            update_sql_log("{$bingFaNo}",$sql . PHP_EOL);//详细更新记录
            $updateRowNum++;
    }
    $summary['updateRowNum']=$updateRowNum;
    return $summary;
}

function recovery_data(){
    $fpr = fopen(DES_LOG_PATH . "recovery_data.csv", 'r') or die("Unable to open recovery_data.csv!");
    while(!feof($fpr)) {
        $oneLine = fgetcsv($fpr);
        if(!empty($oneLine)){
            $sql = "UPDATE `firstp2p_user`  SET `referer`='{$oneLine[1]}' WHERE id = {$oneLine[0]}";
            $flag =  $GLOBALS['db']->getInstance('firstp2p','master')->query($sql);
            update_sql_log('recovery',$sql . PHP_EOL);
            if(!$flag){
                Logger::wLog($sql .PHP_EOL,Logger::ERR,Logger::FILE,DES_LOG_PATH."error_" .date('y_m_d') .'.log');
            }
        }
    }
    fclose($fpr);
}
function select_sql_log($data){
    Logger::wLog($data,Logger::INFO,Logger::FILE,DES_LOG_PATH."select_" .date('y_m_d') .'.log');
}
function update_sql_log($table,$str){
    $fileName = DES_LOG_PATH . "update_{$table}_" .date('y_m_d') .'.log';
    file_put_contents($fileName,$str,FILE_APPEND);

}
function summery_log($data){
    if(is_array($data)){
        Logger::wLog(json_encode($data,JSON_UNESCAPED_UNICODE),Logger::INFO,Logger::FILE,DES_LOG_PATH."referer_migrate_sum_" .date('y_m_d') .'.log');
    }else{
        Logger::wLog($data,Logger::INFO,Logger::FILE,DES_LOG_PATH . "summary_" .date('y_m_d') .'.log');
    }
}

function make_dir(){
    if (!file_exists(DES_LOG_PATH)) {
        @mkdir(DES_LOG_PATH,0755,true);
    }

}


function repair_data($limit=100,$minId,$maxId){
    $recoveryDataFile = fopen(DES_LOG_PATH  ."recovery_data.csv", 'a') or die("Unable to open check file!");
    $flag = true;
    $startId = $minId;
    while($flag){
        $summary =  do_referer_handle($startId,$limit,$maxId,$recoveryDataFile,'repair_'.$minId.'_'.$maxId);
        $startId = $summary['id'];
        if($startId>=$maxId){
            $flag = false;
        }
    }
    fclose($recoveryDataFile);
}