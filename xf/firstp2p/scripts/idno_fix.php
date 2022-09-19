<?php
/**
 *刷firstp2p_user表的 idno值，统一转换为大写
 */
require_once dirname(__FILE__).'/../app/init.php';

use libs\utils\Logger;
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    define('DES_LOG_PATH','/tmp/idnofix/log/');
    error_reporting(E_ALL);
    if(!isset($argv[1])){
        echo '请输入操作序号:';
        echo PHP_EOL;
        echo '捞数据: 1 ';
        echo PHP_EOL;
        echo '重复数据检查: 2 tableName';
        echo PHP_EOL;
        echo 'fix idno: 3  ';
        echo PHP_EOL;
        echo '状态: 4  查看执行状态';
        echo PHP_EOL;
        echo '回滚数据: 5  文件名';
        echo PHP_EOL;

        exit;
    }
    make_dir();
    if($argv[1]==1){//捞数据
        if(!isset($argv[2])||!isset($argv[3]) || !isset($argv[4])){
            echo '捞数据: 1 tableName bingFaNum bingFaNo';
        }
        $tableName =  $argv[2];
        $minId  =  $GLOBALS['db']->getInstance('firstp2p','master')->getOne("SELECT MIN(id) FROM `{$tableName}`");
        $maxId =  $GLOBALS['db']->getInstance('firstp2p','master')->getOne("SELECT MAX(id) FROM `{$tableName}`");
        if(!$minId){
            $minId=1;
        }
        if(!$maxId){
            exit;
        }
        $startId = $minId-1;
        $endId = $maxId;
        $bingFaNum =  $argv[3];
        $bingFaNo = $argv[4];

        $count = floor(($maxId-$minId+1)/$bingFaNum);
        $startId = $minId+($bingFaNo-1)*$count-1;//sql语句 id>$startId AND id<=$endId
        $endId = $minId + $bingFaNo*$count-1;
        if($bingFaNo==$bingFaNum){
            $endId = $maxId;
        }

        summery_log("捞数据:并发数{$bingFaNum}-{$bingFaNo},minId:{$minId},maxId:{$maxId},startId:{$startId},endId:{$endId},处理开始" .date("H:i:s")  );
        get_idno($tableName,$startId,$endId,$bingFaNum,$bingFaNo);
    }
    if($argv[1]==2){//检查是否有重复数据
        if(!isset($argv[2])){
            echo '2 tableName';
        }

        has_repeat_idno($argv[2]);
        return ;
    }

    if($argv[1]==3){//更新
        do_fix_idno($argv[2]);
        return ;
    }
    if($argv[1]==4){
        check_status($argv[2]);//查看状态
        return;
    }
    if($argv[1]==5){//恢复，回滚数据
        recovery_data($argv[2]);
        return;
    }


function has_repeat_idno($tableName){
    $fpr = fopen(DES_LOG_PATH . "{$tableName}.csv", 'r') or die("Unable to open {$tableName}.csv!");
    $data = array();
    $repeatData = array();
    while(!feof($fpr)) {
        $oneLine = fgetcsv($fpr);

        if(!empty($oneLine)){
            $lowValue = strtolower(trim($oneLine[1]));
            $upValue = strtoupper(trim($oneLine[1]));

           if(array_key_exists($lowValue,$data)){
               $repeatData[]=array($data[$lowValue],$lowValue,$oneLine[0],$oneLine[1]);//获取已存在的数据 获取重复的数据
           }else if(array_key_exists($upValue,$data)){
               $repeatData[]=array($data[$upValue],$upValue,$oneLine[0],$oneLine[1]);//获取已存在的数据获取重复的数据
           }else{
               $data[$oneLine[1]] = $oneLine[0];
           }
        }
    }
    fclose($fpr);
   var_export($repeatData);
   echo PHP_EOL;
}

function do_fix_idno($tableName){
    $fpr = fopen(DES_LOG_PATH . "{$tableName}.csv", 'r') or die("Unable to open {$tableName}.csv!");
    summery_log("fix idno 开始:" .date("H:i:s")  );
    while(!feof($fpr)) {
        $oneLine = fgetcsv($fpr);
        if(!empty($oneLine)&&$oneLine[1]!==strtoupper($oneLine[1])){//大写的不处理
            $dbData =  $GLOBALS['db']->getInstance('firstp2p','master')->getRow("SELECT id,idno FROM {$tableName} WHERE id = {$oneLine[0]}");

            if(empty($dbData)||$dbData['idno']!==$oneLine[1]){//没查到或者查到的值不一致，不更新
                continue;
            }
            $newIdno=strtoupper($dbData['idno']);

            $recoverySql =  "UPDATE `{$tableName}`  SET `idno`='{$oneLine[1]}' WHERE id = {$oneLine[0]};";
            update_sql_log("{$tableName}_recovery_sql",$recoverySql . PHP_EOL);


            $updateSql = "UPDATE `{$tableName}`  SET `idno`='{$newIdno}' WHERE id = {$oneLine[0]};";
            $flag =  $GLOBALS['db']->getInstance('firstp2p','master')->query($updateSql);
            update_sql_log("{$tableName}_update_sql",$updateSql . PHP_EOL);
            if(!$flag){
                Logger::wLog($updateSql .PHP_EOL,Logger::ERR,Logger::FILE,DES_LOG_PATH."error_" .date('y_m_d') .'.log');
            }
        }
    }
    summery_log("fix idno 结束:" .date("H:i:s")  );
    fclose($fpr);
}

function get_idno($tableName,$startId=0,$endId=1000000000,$bingFaNum,$bingFaNo){

    $fileNameSuffix =  $tableName . date('Y-m-d');
    $tableStart = microtime(true);
    $limit = 10000;//每次取1w条

    $tableTotalRowNum = 0;
    $tableTotalUpdateRowNum = 0;
    $startTime = date("H:i:s");


    $fpwCheckFile = fopen(DES_LOG_PATH  ."status_{$fileNameSuffix}.csv", 'a') or die("Unable to open check file!");
    $idnoDataFile = fopen(DES_LOG_PATH  ."{$tableName}.csv", 'a') or die("Unable to open {$tableName} idno data file!");
    $flag = true;
    while($flag){
        $summary =  do_get_data($tableName,$startId,$limit,$endId,$idnoDataFile,$bingFaNo);
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
    fputcsv($fpwCheckFile,array($tableName,$bingFaNum,$bingFaNo,$tableTotalRowNum,$tableTotalUpdateRowNum,$cost));

    summery_log("捞数据:并发数{$bingFaNum}-{$bingFaNo},{$tableName}:处理结束-".$startTime .'|'. date('H:i:s') ." [读取行数：{$tableTotalRowNum},记录行数:{$tableTotalUpdateRowNum},耗时:{$cost},平均耗时:{$costPs},内存:{$neiCun}]"  );
    fclose($idnoDataFile);
    fclose($fpwCheckFile);
}

function check_status($tableName){

   $fileNameSuffix =  $tableName . date('Y-m-d');

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
    print_r("共迁移".count($tables)."个表,".$notCompleteNum . "个未完成,"."读取总数".$totalNum.",记录总数".$totalUpdateNum.",最大耗时".$maxCost."s,最大耗时表".$maxCostTable.",平均耗时" .round($maxCost/$totalUpdateNum,4)."s");
    print_r(PHP_EOL);
}
/*
 *
 * */
function do_get_data($tableName,$id,$limit,$endId,$idnoDataFile,$bingFaNo){
  // var_dump($id);var_dump($limit);var_dump($endId);exit;
    $summary = array();

//    $whereStr = " WHERE id>{$id} AND id<={$endId} AND `referer`<>''";
    $whereStr = " WHERE id>{$id} AND id<={$endId} ";
    $sql = "SELECT id,idno  FROM `{$tableName}` {$whereStr} ORDER BY id ASC  limit 0,{$limit}";
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
            $fieldValue =  $row['idno'];
            if(!empty($fieldValue) && stripos($fieldValue,"x")){
                fputcsv($idnoDataFile,array($row['id'],$fieldValue));
                $updateRowNum++;
            }
    }
    $summary['updateRowNum']=$updateRowNum;
    return $summary;
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

function recovery_data($fileName){
    $fpr = fopen( DES_LOG_PATH . $fileName, 'r') or die("Unable to open recovery {$fileName}.log!");
    while (!feof($fpr)) {
        $oneLine = fgets($fpr);
        if(!empty($oneLine)){
            $flag =  $GLOBALS['db']->getInstance('firstp2p','master')->query($oneLine);
            if(!$flag){
                Logger::wLog($flag .PHP_EOL,Logger::ERR,Logger::FILE,DES_LOG_PATH."error_" .date('y_m_d') .'.log');
            }
        }
        echo $oneLine;
    }
    fclose($fpr);
}
