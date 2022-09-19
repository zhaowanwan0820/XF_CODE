<?php

namespace libs\aerospike;
use libs\base\Component;
use libs\utils\Logger;
use libs\aerospike\AerospikeSaveObj;

class AerospikeClient extends Component{

    public $namespace;
    public $set;

    protected $conn = null;
    // 连接成功标识，为了节省一次请求
    protected $conSucc = false;
    // 确认扩展是否可用
    protected $fatalError = false;


    // 无用代码不删除的目的是，如果后续有迁移的时候可以继续用。双读代码。
    //protected $oldConn = null;

    public function init(){
        if (class_exists('\Aerospike')){

            /*
            // 无用代码不删除的目的是，如果后续有迁移的时候可以继续用。双读代码。
            $oldConf = array(
                "hosts"=> array(
                    array('addr'=>'172.31.1.32','port'=>3000),
                    array('addr'=>'172.31.1.33','port'=>3000),
                    array('addr'=>'172.31.1.34','port'=>3000),
                ),
            );
            */

            $config = array("hosts"=>$this->hosts);
            //persistent connection have bug in php-client
            $opt = array(\Aerospike::OPT_CONNECT_TIMEOUT => 200);
            $this->conn = new \Aerospike($config,false,$opt);
            $this->conSucc = true;
            $this->fatalError = false;

            //老集群链接
            //$this->oldConn = new \Aerospike($oldConf,false,$opt);

        }else{
            $this->writeLog("class Aerospike do not exist");
            $this->fatalError = true;
        }
    }
    /**
      * 连接重试
      */
    private function retryConn(){
        if( $this->fatalError == true ){
            $this->conSucc = false;
            return;
        }
        $times = 1;
        $i = 1;
        while($i<=$times){
            // retry connect to aerospike $i
            $this->writeLog("Connect Retry Times [ $i ] connect info: ".var_export($this->hosts,true));
            $this->conn->reconnect();

            if($this->checkConn()){
                $this->conSucc = true;
                break;
            }
            $i++;
        }
        $this->conSucc = false;
    }

    private function checkConn(){

        if (!empty($this->conn) && $this->conn->isConnected()) {
            return true;
        }else{
            $this->conSucc = false;
            return false;
        }
    }
    /**
     * 创建aerospike的key
     */
    public function createKey($key){
        if(!$this->checkConn()){
            $this->retryConn();
        }
        if( !$this->conSucc ){
            $this->writeLog("connection failed info: ".var_export($this->hosts,true));
            return false;
        }
        return $this->conn->initKey($this->namespace,$this->set,$key);
    }

    /**
     * 向key中写入数据
     */
    public function set($key,$bins){
        if(!$this->checkConn()){
            $this->retryConn();
        }
        if( !$this->conSucc ){
            $this->writeLog("connection failed info: ".var_export($this->hosts,true));
            return false;
        }
        $status = $this->conn->put($key,$bins,0,array(\Aerospike::OPT_POLICY_RETRY=>\Aerospike::POLICY_RETRY_ONCE));
        if ($status == \Aerospike::OK){
            return true;
        }else{
            // 记录错误信息
            $this->writeLog("error [{$this->conn->errorno()}] ".$this->conn->error());
            return false;
        }
    }

    /**
     * 读
     * 过滤条件 :filter = array('name1','name2');
     */
    public function get($key,$filter=array()){


        if(!$this->checkConn()){
            $this->retryConn();
        }

        if( !$this->conSucc ){
            $this->writeLog("new machine room connection failed info: ".var_export($this->hosts,true));
            return false;
        }
        if (empty($filter)){
            $status = $this->conn->get($key, $record);
        }else{
            $status = $this->conn->get($key, $record,$filter);
        }
        if ($status == \Aerospike::OK) {
            return $record['bins'];
        } elseif ($status == \Aerospike::ERR_RECORD_NOT_FOUND){
            // 记录日志
            $this->writeLog("new machine room - data does not exist  [ id:{$key['key']} ]");
            return array();
        } else {
            $this->writeLog("new machine room error [{$this->conn->errorno()}] ".$this->conn->error());
            return false;
        }
    }
    /**
    * 无用代码不删除的目的是，如果后续有迁移的时候可以继续用。双读代码。
    * 私有方法无需注释
    * 从老集群中读取数据
    */
    private function getFromOldMachine($key){
        /* 临时迁移过程中的逻辑*/
        $oldStatus = $this->oldConn->get($key, $oldRecord);
        if ($oldStatus == \Aerospike::OK) {
            $this->writeLog("oldmachineroom data get success [ id:{$key['key']} ]");
            return $oldRecord['bins'];
        }else{
            $this->writeLog("oldmachineroom data get error [ id:{$key['key']} ]  [{$this->oldConn->errorno()}] ".$this->oldConn->error());
            return false;
        }
    }

    /**
     * 批量读
     * 过滤条件 :filter = array('name1','name2');
     */
     /*
    public function multiGet($keys,$filter=array()){
        if(!$this->checkConn()){
            $this->retryConn();
        }
        if( !$this->conSucc ){
            $this->writeLog("connection failed info: ".var_export($this->hosts,true));
            return false;
        }
        $record = array();
        if (empty($filter)){
            $status = $this->conn->getMany($keys, $record);
        }else{
            $status = $this->conn->getMany($keys, $record,$filter);
        }
        if ($status == \Aerospike::OK) {
            return $record;
        } else {
            $this->writeLog("error [{$this->conn->errorno()}] ".$this->conn->error());
            return false;
        }
    }
    */

    //Aerospike::Query have bug .
    /*
    public function queryByEquale($binName,$value){
        if(!$this->checkConn()){
            $this->connect();
        }
        $result = array();
        $where = $this->conn->predicateEquals($binName, $value);
        $status = $this->conn->query($this->namespace, $this->set, $where, function($record) use (&$result){
            $result[] = $record['bins'];
        },array(),array(\Aerospike::OPT_READ_TIMEOUT=>0));
        if ($status !== \Aerospike::OK) {
            echo "An error occured while querying[{$this->conn->errorno()}] {$this->conn->error()}\n";
        } else {
            echo sprintf("the num of record is %s\n",count($result));
            return $result;
        }
    }
    */
    /*
    public function getNSRecordCount(){
        if(!$this->checkConn()){
            $this->connect();
        }
        $result = array();
        $count = 0;
        $status = $this->conn->scan($this->namespace, $this->set, function($record) use (&$count){
            $count++;
        },array(),array(\Aerospike::OPT_READ_TIMEOUT=>0,\Aerospike::OPT_SCAN_NOBINS=>true));
        if ($status !== \Aerospike::OK) {
            echo "An error occured while querying[{$this->conn->errorno()}] {$this->conn->error()}\n";
        } else {
            echo sprintf("the num of record is %s",$count);
        }
    }
    */
    public function createIndexForQuery($binName,$indexName,$type='string'){
        if(!$this->checkConn()){
            $this->retryConn();
        }
        if( !$this->conSucc ){
            $this->writeLog("connection failed info: ".var_export($this->hosts,true));
            return false;
        }
        $indexType = ($type=='string')?\Aerospike::INDEX_TYPE_STRING:\Aerospike::INDEX_TYPE_INTEGER;
        $status = $this->conn->createIndex($this->namespace, $this->set, $binName, $indexType, $indexName);
        if ($status == \Aerospike::OK) {
            $this->writeLog("Index $indexName created on $this->namespace.$this->set.$binName success");
            return true;
        }else if ($status == \Aerospike::ERR_INDEX_FOUND) {
            $this->writeLog("index [ $indexName ]  has already been created");
            return true;
        } else {
            $this->writeLog("error [{$this->conn->errorno()}] ".$this->conn->error());
            return false;
        }
    }

    /**
     *  验证key值是否存在
     */
    public function exist($key){
        if(!$this->checkConn()){
            $this->retryConn();
        }
        if( !$this->conSucc ){
            $this->writeLog("connection failed info: ".var_export($this->hosts,true));
            return false;
        }
        if ($this->conn->exists($key, $metadata) == \Aerospike::OK){
            return true;
        }else{
            return false;
        }
    }

    /*
     *  打日志
     */
    public function writeLog( $str ){
        $str = sprintf("Aerospike: [namespace=%s,set=%s] info:%s",$this->namespace,$this->set,$str);
        Logger::wLog($str . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH. "/logger/" ."aerospike_" . date('Y_m_d') .'.log');
    }
}


?>
