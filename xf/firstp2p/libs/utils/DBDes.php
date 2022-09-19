<?php
namespace libs\utils;


/**
 * 底层DB数据加解密服务：对查询数据库的sql语句加密过滤，对返回结果集解密过滤.
 * 需加解密的表及具体字段通过common.conf进行配置
 *
 * @author 吕宝松 <lvbaosong@ucfgroup.com>
 */
use \libs\utils\Logger;
class DBDes{
    public static $DES_OFF = false;
    /**
     *  对SQL语句中涉及到需加密的字段进行加密
     *  提取表名 根据表名获取待加密字段，无加密字段返回
     *  插入操作不做过滤  不是DELETE UPDATE SELECT 不过滤
     * @param string $sql  sql语句
     * @return  array array(sql,desFields) 返回过滤后的SQL语句和加解密字段
     */
    public static function encryptFilter($sql){
        if(self::$DES_OFF){
            return array($sql,array());
        }
        $sql = trim($sql);
        if(empty($sql)){
            return array($sql,array());
        }

        $_sql = $sql;
        $start = microtime(true);

        $tableNames = self::extractTableName($sql);
        if(empty($tableNames)){//1.提取表名
            return array($sql,array());
        }

        $desFields = self::getConfigFields($tableNames);
        if(empty($desFields)){  //2.检查是否有需要过滤的字段
            return array($sql,array());
        }

        $sqlOperator = self::extractSqlOperator($sql);

        if(!isset($GLOBALS['sys_config']['DB_DES_OPERATOR'][$sqlOperator])){ //3.不是 DELETE SELECT UPDATE不过滤
            return array($sql,array());
        }

        $sql =  self::analysisAndEncryptValue($sql,self::extractFieldAndValues($sql,$desFields)); //4.加密处理

        self::doLog( array('c'=>round(microtime(true) - $start, 4),'filter'=> md5($sql)==md5($_sql)?false:true,'sql'=>$sql,'_sql'=>$_sql,'fields'=>$desFields,'tables'=>$tableNames));
        return   array($sql,$desFields);
    }

    /**
     *  解密数据库字段
     * @param array $data  待解密的数据
     * @param array $desFields  需要解密的字段
     * @return  $data 返回解密后的数据
     */
    public static function decryptFilter($data,$desFields){
        if(self::$DES_OFF){
            return $data;
        }
        if(empty($data) ||empty($desFields)){
            return $data;
        }
        $start = microtime(true);
        $logData=array();
        foreach($desFields as $toDesField=>$value){
            if(isset($data[$toDesField]) ){
                if(strlen($data[$toDesField])<=20){//兼容没有加密过的数据:没有加密的内容直接过
                    continue;
                }
                $decryptedValue = self::decryptOneValue($data[$toDesField]);
                if($decryptedValue){
                    $data[$toDesField] =$decryptedValue;
                }else{
                    \libs\utils\Monitor::add('DATA_DECRYPT_FAIL');
                    self::doLog(array('filter'=>'des_fail', 'value'=>$data[$toDesField],'field'=>$toDesField));
                }
                $logData[]=$data[$toDesField];
            }
        }
        self::doLog(array('c'=>round(microtime(true) - $start, 4),'filter'=> count($logData)>0?true:false, 'data'=>$logData, 'fields'=>$desFields));
        return $data;
    }

    /**
     *  提取SQL语句中的表名（不兼容大写表名）
     *  表名特征:  firstp2p_xxxx[`][空格][,]
     * @param string $sql  SQL语句
     * @param string $tablePrefix 表名前缀 默认值:firstp2p_
     * @return  array 表名数组
     */
    public static function extractTableName($sql,$tablePrefix='firstp2p_'){
        preg_match_all("/{$tablePrefix}[^\s,`]+/", $sql, $matches);
        $tableNames = array();
        if(!empty($matches)){
            $tableNames = $matches[0];
        }
        return $tableNames;
    }
    /**
     *  获取配置中需要加解密的字段
     * @param array $tableNames  表名
     * @return  array 配置中需加密的字段
     */
    public static function getConfigFields($tableNames){
        $desFields = array();
        foreach($tableNames as $value){
            if(isset($GLOBALS['sys_config']['DB_DES_MODELS'][$value])){
                $desFields =array_merge($GLOBALS['sys_config']['DB_DES_MODELS'][$value],$desFields);
            }
        }
        return $desFields;
    }



    /**此方法只针对字符串类型数据过滤.
     * 特征识别:   [`]|[空格]|[(]字段名[`][空格*]=[空格*]'|"数值
     *       注:   []表示可存在    *表示多个   |表示两边的值至少有一个存在
     *       例:   WHERE (`idno`="14500000888" OR `mobile`='14500000888')
     *            u.`idno`="14500000888" OR u.`mobile`='14500000888')
     * @param string $sql  sql语句
     * @param array $desFields  需要解密的字段
     * @return  array  匹配到的  field=value 字符串数组
     */
    public static function extractFieldAndValues($sql,$desFields){
        $fieldAndValues = array();
        foreach($desFields as $toDesField=>$value) {
            preg_match_all("/(?:`|\s|\(|\.){$toDesField}`?\s*\!?=\s*(?:\'|\")[^\'\"]+/", $sql, $matches);
            if (!empty($matches)) {
                $matchResult = $matches[0];
                if (is_array($matchResult)) {
                    $fieldAndValues = array_merge($fieldAndValues,$matchResult);
                } else {
                    $fieldAndValues[] = $matchResult;
                }
            }
        }

        return $fieldAndValues;
    }
    /*
     * @param string $sql            原SQL语句
     * @param array  $fieldAndValues 匹配到的field=value多个字符串
     * 例:
     * array(
     *   [0] =>  `mobile`="15618680683
     *   [1] =>  (idno="111111111
     *   [2] =>  bankcard = '2222222
     *  )
     * @return string 替换为加密结果的SQL
    */
    public  static function analysisAndEncryptValue($sql,$fieldAndValues){
        foreach ($fieldAndValues as $fieldAndValue) {
            $_matchedSqlStr = $fieldAndValue;

            // 获取需要加密的值并加密
            $fieldAndValue =explode('=',$fieldAndValue);
            $toEncryptValue = trim($fieldAndValue[1]);
            $toEncryptValue = ltrim($toEncryptValue,"'");
            $toEncryptValue = ltrim($toEncryptValue,'"');

            if(!empty($toEncryptValue)&&strpos($toEncryptValue,'\\')===false){
                $encryptedValue=self::encryptOneValue($toEncryptValue);
                //在匹配到的字符串的value部分查找被加密的原值，替换为加密后的结果
                $newValue  =  str_replace($toEncryptValue,$encryptedValue,$fieldAndValue[1]);
                $encryptedStr =implode('=',array($fieldAndValue[0],$newValue));
                //在原sql中查找匹配到的字符串,替换为新的加密后的字符串
                $sql  =  str_replace($_matchedSqlStr,$encryptedStr,$sql);

                self::doLog(array('search'=>$toEncryptValue,'replace'=>$encryptedValue,'subject'=>$_matchedSqlStr,'result'=>$encryptedStr));
            }

        }
        return $sql;
    }


    /**
     *  主要用于插入操作，对$data加密过滤
     * @param string $tableName 表名称
     * @param array $data 数组数据
     * @return  string
     */
    public static function encryptOneRow($tableName,$data){
        if(self::$DES_OFF){
            return $data;
        }
        $start = microtime(true);
        $toEncryptFields = DBDes::getConfigFields(array($tableName));
        if(empty($toEncryptFields)){
            return $data;
        }
        $logData = array();

        foreach($toEncryptFields as $key=>$value){
            if(isset($data[$key])){
                $_value = $data[$key];
                $data[$key]=DBDes::encryptOneValue($data[$key]);
                $logData[]=array('field'=>$key,'data'=>$data[$key] . '|' .$_value);
            }
        }
        $logData[]=array('c'=>round(microtime(true) - $start, 4),'m'=>'encryptOneRow','t'=>$tableName);

        self::doLog($logData);
        return $data;
    }
    /*
     * 主要用于插入操作，对$data加密过滤
     * @param string $tableName 表名
     * @param array  $data      待插入的数据
     * */
    public static function encryptMultiRow($tableName,$data){
        if(self::$DES_OFF){
            return $data;
        }
        $start = microtime(true);

        $toEncryptFields = DBDes::getConfigFields(array($tableName));
        if(empty($toEncryptFields)){
            return $data;
        }
        $logData = array();
        foreach($toEncryptFields as $key=>$value){
            foreach ($data as $index=>$item) {
                if (isset($item[$key])) {
                    $_value = $item[$key];
                    $item[$key] = DBDes::encryptOneValue($item[$key]);
                    $data[$index] = $item;
                    $logData[]=array('field'=>$key,'data'=>$item[$key] . '|' .$_value);
                }
            }
        }
        $logData[]=array('c'=>round(microtime(true) - $start, 4),'m'=>'encryptMultiRow','t'=>$tableName);
        self::doLog($logData);
        return $data;
    }

   /**
    * 工具类函数，用于手动调用加解密
    * @param string|array $$specifiedFields 指定要解密的字段名
    * @param boolean $encrypt 默认加密 true:加密;false:解密
    * @param array  $data  待加解密的数组，一维(单行数据)或二维(多行数据)
   */
    public static function encryptOrDecryptBySpecifiedFields($specifiedFields,$data,$encrypt=true){
        if(!$specifiedFields||empty($data)){
            return $data;
        }
        if(!is_array($specifiedFields)){
            $specifiedFields = array($specifiedFields);
        }
        foreach($specifiedFields as $fieldName){
            if(count($data)==count($data,1)){
                if(isset($data[$fieldName])){
                    if($encrypt){
                        $data[$fieldName]=DBDes::encryptOneValue($data[$fieldName]);
                    }else{
                        $data[$fieldName]=DBDes::decryptOneValue($data[$fieldName]);
                    }
                }
            }else{
                foreach ($data as $index=>$item) {
                    if (isset($item[$fieldName])) {
                        if($encrypt){
                            $item[$fieldName] = DBDes::encryptOneValue($item[$fieldName]);
                        }else{
                            $item[$fieldName] = DBDes::decryptOneValue($item[$fieldName]);
                        }
                        $data[$index] = $item;
                    }
                }
            }
        }
        return $data;
    }

    /**
     *  加密一个数值
     * @param string $value  待解密的值
     * @return  string
     */
    public static function encryptOneValue($value){
        if(!empty($value)){
//            $value =  Aes::encode($value, self::getKey());
            $value =  GibberishAES::enc($value, self::getKey());
        }
        return $value;
    }
    /**
     *  解密一个数值
     * @param string $value  待解密的值
     * @return  string|false 解密失败则返回false
     */
    public static function decryptOneValue($value){
        if(!empty($value)){
//            $value = Aes::decode($value,self::getKey());
            $value = GibberishAES::dec($value,self::getKey());
        }
        return $value;
    }
    /**
     *  工具函数:解密一组加密的数据
     * @param array $data  待解密数组
     * @return array
     */
    public static function decryptMultiValue($data){
        if(empty($data)){
            return $data;
        }
        foreach($data as $key =>$value){
            $decryptValue = self::decryptOneValue($value);
            if($decryptValue){
                $data[$key]=$decryptValue;
            }
        }
        return $data;
    }
    /**
     *  工具函数:加密一组数据
     * @param array $data  待加密数组
     * @return array
     */
    public static function encryptMultiValue($data){
        if(empty($data)){
            return $data;
        }
        foreach($data as $key =>$value){
            $encryptValue = self::encryptOneValue($value);
            if($encryptValue){
                $data[$key]=$encryptValue;
            }
        }
        return $data;
    }
    /*
       *   获取SQL操作符
       *   @param string $SQL
        *  @return SQL语句前六个字符
        *   INSERT  DELETE   UPDATE   SELECT
       */
    public static function extractSqlOperator($sql){
        $operator = substr(ltrim($sql),0,6);
        return strtoupper($operator);
    }
    private static function getKey(){
        $key =  get_cfg_var('p2p_db_des_key');
        if(!isset($key) || empty($key)){
            throw new \Exception("db des key is null");
        }
        return $key;
    }

    private static function doLog($arr){
        return true;//调试用,上线后关闭日志输出
        try{
            $logFilePath = '/tmp/des/log/';
            if (!file_exists($logFilePath)) {
                @mkdir($logFilePath,0755,true);
            }
            $fileName ='des_'. date('y_m_d') .'.log';
            Logger::wLog(json_encode($arr,JSON_UNESCAPED_UNICODE),Logger::INFO,Logger::FILE,$logFilePath .$fileName );
        }catch (\Exception $e){

        }

    }

    /*
     * 数据初始化时候用，初始化后废弃
     * 功能：过滤查询操作
     *      如果是查询操作，看redis中是否有标记这个要加密的值，如果有则返回true，否则返回false.
     *     (通过扫配置涉密表的SQL，在关闭注册和修改信息情况下不存在用户触发的与涉密相关的INSERT，UPDATE操作)
     * */
    private static function isEncryptInit($sql,$toEncryptValue){
        $sqlOperator = self::extractSqlOperator($sql);
        if($sqlOperator!='SELECT'){
            return true;
        }
     
        $tableName = '';
        $tableNames = self::extractTableName($sql);
        foreach($tableNames as $value){//通过扫涉密表的SQL一般有一个表符合条件的表
            if(isset($GLOBALS['sys_config']['DB_DES_MODELS'][$value])){
                $tableName = $value;
            }
        }
        if($tableName!=''){
            try{
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                if ($redis) {
                    $ret =  $redis->get($tableName.$toEncryptValue);
                    if($ret){
                        return true;
                    }
                }else{
                       Logger::wLog("redis:null" .PHP_EOL,Logger::INFO,Logger::FILE,"/tmp/db_des_redis_" .date('y_m_d') .'.log');
                }
            }catch (\Exception $e){
                  Logger::wLog("redis:".$e->getTraceAsString() .PHP_EOL,Logger::INFO,Logger::FILE,"/tmp/db_des_redis_" .date('y_m_d') .'.log');

            }
        }
        return false;
    }

}
