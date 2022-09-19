<?php
require(dirname(__FILE__) . '/../app/init.php');
ini_set('display_errors','on');
error_reporting(E_ALL);
class BatchRename {

    public $db;

    public function __construct() {
        $this->db = $GLOBALS["db"];
    }

    public function process() {

        try {
            // 获取上次的时间点和余额
            $sqls = array(
            "UPDATE firstp2p_article SET content=REPLACE(content,'第一P2P','网信理财') WHERE content LIKE '%第一P2P%'",
            "UPDATE firstp2p_article SET content=REPLACE(content,'第一p2p','网信理财') WHERE content LIKE '%第一p2p%'",

            "UPDATE firstp2p_article SET title=REPLACE(title,'第一P2P','网信理财') WHERE title LIKE '%第一P2P%'",
            "UPDATE firstp2p_article SET title=REPLACE(title,'第一p2p','网信理财') WHERE title LIKE '%第一p2p%'",
 
            "UPDATE firstp2p_adv SET `code`=REPLACE(`code`,'第一P2P','网信理财') WHERE `code` LIKE '%第一P2P%'",
            "UPDATE firstp2p_adv SET `code`=REPLACE(`code`,'第一p2p','网信理财') WHERE `code` LIKE '%第一p2p%'",
            "UPDATE  firstp2p_msg_template SET content=REPLACE(content,'第一p2p','网信理财') WHERE  content like '%第一p2p%' ;",
            "UPDATE  firstp2p_msg_template SET content=REPLACE(content,'第一P2P','网信理财') WHERE  content like '%第一P2P%' ;",
            "UPDATE firstp2p_conf SET `value`=REPLACE(`value`,'第一P2P','网信理财') WHERE `value` LIKE '%第一P2P%';",
            "UPDATE firstp2p_conf SET `value`=REPLACE(`value`,'第一p2p','网信理财') WHERE `value` LIKE '%第一p2p%';",
            );

            foreach ($sqls as $sql){
                $result = $this->db->query($sql);
                    if (!$result) {
                    throw new Exception ($sql);
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage() ,"Update fail\n";
        }
    }

}

$handle = new BatchRename();
$handle->process();
