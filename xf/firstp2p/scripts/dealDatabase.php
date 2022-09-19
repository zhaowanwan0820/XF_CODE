<?php 
/**
 * 短信模板表 处理 短信平台 一次性脚本数据 插入
 * @author  caolong
 * @date    2014-3-11
 */
require(dirname(__FILE__) . '/../app/init.php');
require_once(dirname(__FILE__) . '/../system/utils/logger.php');
require(dirname(__FILE__) . '/../system/utils/es_mail.php');
use libs\db\MysqlDb;

class DealDatabase{
    private $config = array();
    private $db;
	public function __construct() {
		$this->db     = new MysqlDb(app_conf('DB_HOST') . ":" . app_conf('DB_PORT'), app_conf('DB_USER'), app_conf('DB_PWD'), app_conf('DB_NAME'), 'utf8');
	}

	//批量修改表名
	public function moveTable() {
		$sql = 'show tables';
		$result = $this->db->getAll($sql);
		print_r(count($result));exit('xxx');
		
		if(!empty($result)) {
			foreach ($result as $key=>$val) {
			    if(!empty($val['Tables_in_flow'])) {
			        $r = explode('_', $val['Tables_in_flow']);
			        $newTableName = $this->getTableName($r);
			    	$sql = 'ALTER  TABLE '.$val['Tables_in_flow'].' RENAME TO flow_'.$newTableName;
			    	$this->db->query($sql);
			    }
			}
		}
	}
	
	public function delTabel() {
		$allow = '`flow_article`,`flow_crawler_rate`';
		$r = explode(',', $allow);
		$i = 0;
		if(!empty($r)) {
			foreach ($r as $key=>$val) {
			    if(!empty($val)) {
			        $sql = 'drop table '.$val;
			        $this->db->query($sql);
			        $i++;
			    }
			}
		}
		echo '操作--'.$i.'--次';
		
	}
	
	//获取表名
	private function getTableName($arr = array()) {
	   array_shift($arr);
	   $string = implode( '_',$arr);
	   return $string;
	}
	
	//处理
	public function deal() {
	    $num = 0;
	    if(!empty($this->config)) {
	    	foreach ($this->config as $key=>$val ) {
	    	    $num++;
	    		$sql = 'SELECT id FROM `firstp2p_msg_template` WHERE NAME ="'.$key.'" and sms_template_id = 0 LIMIT 1';
	    	    $id  = $this->db->getOne($sql);
	    	    logger::info($sql." \n");
	    	    if(!empty($id)) {
                    $sql = 'UPDATE `firstp2p_msg_template` SET sms_template_id = "'.$val.'"  WHERE id ='.intval($id);
                    $this->db->query($sql);	
                    logger::info($sql." \n");
	    	    }
	    	}
	    	echo '已执行 '.$num.'条 sql';
	    	$this->db->close();
	    }
	}
	
	
	public function __destruct(){
		unset($this->db);
		unset($this->config);
	}
}

$p = new DealDatabase();
//$p->deal();
$p->delTabel();
//$p->delTabel();
?>