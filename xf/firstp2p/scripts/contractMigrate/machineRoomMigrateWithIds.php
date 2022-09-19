<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');
/**
 * firstp2p_contract 合同数据迁移
 */
require_once dirname(__FILE__).'/../../app/init.php';
// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep contract_move.php | grep -v grep | grep -v {$pid} | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}
use core\dao\ContractModel;
use core\service\ContractService;
use core\dao\ContractContentModel;
use libs\aerospike\AerospikeSaveObj;
use libs\aerospike\AerospikeClient;

class machineRoomMigrate{

    private static $oldConf = array(
        'hosts' => array(
            array('addr'=>'172.31.1.32','port'=>3000),
            array('addr'=>'172.31.1.33','port'=>3000),
            array('addr'=>'172.31.1.34','port'=>3000),
        ),
    );

    private static $newConf = array(
        'hosts' => array(
            array('addr'=>'172.21.12.32','port'=>3000),
            array('addr'=>'172.21.12.33','port'=>3000),
            array('addr'=>'172.21.12.34','port'=>3000),
        ),
    );

    private static $namespace = "contract";

    private static $set = "data";

    // 老机房链接
    private $oldConn = null;
    // 新集群连接
    private $newConn = null;

    public function __construct(){
        $this->aerospikeInit();
    }

    private function aerospikeInit(){
        if (class_exists('\Aerospike')){
            $opt = array(\Aerospike::OPT_CONNECT_TIMEOUT => 200);
            // old newConnect
            $this->oldConn = new \Aerospike(self::$oldConf,false,$opt);
            if(empty($this->oldConn)){
                $this->writeLog("old machineRoom conf wrong ,newConnect failed");
            }
            // new newConnect
            $this->newConn = new \Aerospike(self::$newConf,false,$opt);
            if(empty($this->newConn)){
                $this->writeLog("new machineRoom conf wrong ,newConnect failed");
            }
        }else{
            $this->writeLog("class Aerospike do not exist");
        }
    }

    public function doProcess($id){
        $contId = intval($id);
        // 从老集群拿
        $data = $this->getData($contId,$this->oldConn);
        if(!empty($data)){
            $this->setDataToNew($contId,$data['content']->content,true);
            $this->writeLog("old machineRoom to new machineRoom succ. contractId: $contId");
        }else{
            $this->writeLog("old machineRoom 's data do not exist. contractId: $contId");
        }
        return;
    }

    /*
    * 用万M机器生成Key节约原来环境资源
    * key 是合同的id生成的key
    */
    private function createKey($id){
        $ret = $this->newConn->initKey(self::$namespace,self::$set,$id);
        if(empty($ret)){
            $this->writeLog("createKey failed ,newConnect failed");
            return false;
        }
        return $ret;
    }
    private function getData($id,$conn,$from="old"){
        $key = $this->createKey($id);
        $status = $conn->get($key, $record);
        if ($status == \Aerospike::OK) {
            return $record['bins'];
        } elseif ($status == \Aerospike::ERR_RECORD_NOT_FOUND){
            // 记录日志
            $this->writeLog("$from _ $from _ $from .data does not exist  [ id:{$key['key']} ]");
            return false;
        } else {
            $this->writeLog("error [{$conn->errorno()}] ".$conn->error());
            return false;
        }
        if( empty($data) || empty($data['content']) ){
            return false;
        }
    }

    /**
    * isCompressed：数据是否已经压缩
    */
    private function setDataToNew($id,$content,$isCompressed=true){
        $id = intval($id);
        $key = $this->createKey($id);
        $saveObj = new AerospikeSaveObj;
        // 如果没有压缩
        if($isCompressed !== true){
            // 压缩之
            $saveObj->content = gzcompress($content);
        }else{
            $saveObj->content = $content;
        }
        $bins = array(
            "id"=>$id,
            "content"=>$saveObj,
        );
        $status = $this->newConn->put($key,$bins,0,array(\Aerospike::OPT_POLICY_RETRY=>\Aerospike::POLICY_RETRY_ONCE));
        if ($status == \Aerospike::OK){
            return true;
        }else{
            // 记录错误信息
            $this->writeLog("error [{$this->newConn->errorno()}] ".$this->newConn->error());
            return false;
        }
    }
    /*
     *  打日志
     */
    public function writeLog( $str ){
        $str = sprintf("machineRoomMigrate: [namespace=%s,set=%s] info:%s\n",self::$namespace,self::$set,$str);
        echo $str;
    }
}


// 开始迁移
$start = 1;
if ( count($argv)!=3 ){
    echo '`which php` machineRoomMigrateWithIds.php ${startId} ${endId}'."\n";
    exit(0);
}
$start = intval($argv[1]);
$end = intval($argv[2]);

$mrm = new machineRoomMigrate();
for($i=$start;$i<=$end;$i++){
     $mrm->doProcess($i);
}
