<?php

require_once dirname(__FILE__).'/../app/init.php';

set_time_limit(0);
ini_set('memory_limit', '1024M');

$cnt = $argv[1];
if (!$cnt) {
    //die('input error');
    $cnt = 1;
}

$id = \SiteApp::init()->cache->get('user_log_move');
$id = intval($id);

$max_id = \SiteApp::init()->cache->get('max_user_log_id');

$n = ceil($cnt / 1000);

for ($i=0; $i<$n; $i++) {
    $sql = "select * from firstp2p_user_log where id > '{$id}' and id < '{$max_id}' limit 1000";

    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list as $row) {
        $obj = new \core\dao\UserLogModel();
        $obj->isSplit = 3;
        $obj->setRow($row);
        $r = $obj->insert();
        if ($r === false) {
            echo "error: " . $row['id'] . "\n";
        }       
    }

    $id = $row['id'];
}

\SiteApp::init()->cache->set('user_log_move', $id, 86400*30);
