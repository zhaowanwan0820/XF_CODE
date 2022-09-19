<?php
/**
 * generate_reg_tag.php
 *
 * @date 2014年11月26日
 * @author yangqing <yangqing@ucfgroup.com>
 * 生成用户注册tag
 */

namespace scripts;

use libs\db\MysqlDb;
use core\service\UserTagService;

echo "\t*** App start ".date('Y-m-d H:i:s')." ***\n";

set_time_limit(0);
//error_reporting(0);
//ini_set('display_errors', 1);

require(dirname(__FILE__) . '/../app/init.php');
class GenerateRegTag {
    private $_db = null;
    private $_YTag = 'REG_Y_';
    private $_MTag = 'REG_M_';
    private $_tagService = null;

    public function __construct() {
        $this->_log("Init mysql");
        // 从库
        //$this->_db = new MysqlDb(app_conf('DB_SLAVE_HOST').":".app_conf('DB_SLAVE_PORT'), app_conf('DB_SLAVE_USER'),app_conf('DB_SLAVE_PWD'),app_conf('DB_NAME'),'utf8', 0, 1);
        $this->_db = MysqlDb::getInstance('firstp2p', 'slave');
        if($this->_db->link_id === false){
            $this->_error("Can't Connect MySQL Server !!!");
            exit();
        }
        $this->_tagService = new UserTagService();
    }

    public function process() {
        $this->_log("start process ...");
        $userSql = "SELECT `id` as `uid`,DATE_FORMAT(FROM_UNIXTIME(a.create_time),'%Y') as `reg_y`,DATE_FORMAT(FROM_UNIXTIME(a.create_time),'%m')  as `reg_m` FROM `firstp2p_user` AS `a` WHERE  is_delete = 0 ORDER BY `id` ASC ";
        $countSql = 'SELECT COUNT(*) FROM `firstp2p_user` WHERE is_delete = 0';

        $count = $this->_db->getOne($countSql);
        $limit = 3000;
        $list = array();
        for($offset=0;$offset<$count;$offset+=$limit){
            $findUserSql = $userSql." LIMIT {$offset},{$limit}";
            $list = $this->_db->getAll($findUserSql);
            if($list){
                foreach($list as $item){
                    $this->_addTag($item['uid'],array($this->_YTag.$item['reg_y'],$this->_MTag.$item['reg_m']));
                }
                unset($list);
            }
        }
        $this->_log("Success!!!");
        exit("\r\n\t###### FINISH ######\t\n");
    }

    private function _addTag($uid, $taglist){
        foreach($taglist as $tag){
            $ret = $this->_tagService->addUserTagsByConstName($uid,$tag);
            if($ret){
                $this->_log("UID:".$uid.",TAG:".$tag);
            }else{
                $this->_error("ADD TAG FAIL - UID:".$uid.",TAG:".$tag);
            }
        }
    }

    // 输出日志
    private function _log($msg) {
        echo "[".date('Y-m-d H:i:s')."]$msg\n";
    }

    // 输出错误日志
    private function _error($msg){
        echo "\n**** ERROR : $msg ****\n";
    }
}

(new GenerateRegTag)->process();

