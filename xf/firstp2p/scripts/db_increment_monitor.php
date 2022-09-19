<?php
require(dirname(__FILE__) . '/../app/init.php');

class DbIncrementMonitor{
    private static $db_list = array(
        'firstp2p','firstp2p_deleted','firstp2p_moved','firstp2p_payment',
        'duotou',
        'contract',
        'ncfph',
        'msg_box',
        'profile','itil','vip',
        'candy',
        'ncfwx_div','ncfph_div','ncfph_moved_div'
    );

    public function run($isNeedEmpty = 0){
        foreach(self::$db_list as $db_name) {
            try{
                $this->db = $GLOBALS["db"]::getInstance($db_name);
                $sql = "SHOW tables";
                $tableList = $this->db->getAll($sql);
            }catch (\Exception $e){
                echo "db异常".$db_name;
                continue;
            }
            if($isNeedEmpty == 1){
                \SiteApp::init()->dataCache->getRedisInstance()->Del($db_name);
            }
            if(empty($tableList)){
                continue;
            }
            foreach ($tableList as $table) {
                $tableInfo = array();
                $table = array_shift($table);
                $primaryKey = $this->getPrimaryKey($table);
                if(!$primaryKey){
                    continue;
                }
                $getMaxsql =" SELECT MAX(".$primaryKey.") as primary_key FROM `".$table.'`';
                $max = $this->db->getAll($getMaxsql);
                $tableInfo = \SiteApp::init()->dataCache->getRedisInstance()->hGet($db_name,$table);
                if($tableInfo && $isNeedEmpty == 0){
                    $tableInfo = json_decode($tableInfo,true);
                    $tableInfo['addCount'] = bcsub($max[0]['primary_key'],$tableInfo['baseCount']);
                }else{
                    $tableInfo =array(
                        'dbName' => $db_name,
                        'tableName' => $table,
                        'baseCount' => intval($max[0]['primary_key']),
                        'addCount' => 0,
                    );
                }
                $tableInfo = json_encode($tableInfo);
                \SiteApp::init()->dataCache->getRedisInstance()->hSet($db_name,$table,$tableInfo);
            }
        }
    }

    public function getPrimaryKey($tableName){
        if(empty($tableName)){
            return false;
        }
        $sql = "DESC ".'`'.$tableName.'`';
        $result = $this->db->getAll($sql);
        foreach ($result as $value ){
            if(strtolower($value['Key']) == 'pri' && strtolower($value['Extra']) == 'auto_increment'){
                return $value['Field'];
            }
        }
        return false;
    }
}

$isNeedEmpty = empty($argv[1]) ? 0 : $argv[1];
$service = new DbIncrementMonitor();
$service->run($isNeedEmpty);

