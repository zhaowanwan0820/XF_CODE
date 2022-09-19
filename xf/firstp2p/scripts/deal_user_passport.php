<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

$db = $GLOBALS["db"];
$tableName = DB_PREFIX . "user_passport";
$start = 0;
$limit = 1000;
$total = $db->getOne("SELECT COUNT(*) FROM $tableName");
echo "TOTAL COUNT : $total" . PHP_EOL;
echo "-----------------------------" . PHP_EOL;
$needUpdateCount = 0;
$success = 0;
$fail = 0;
while ($start <= $total) {
    $sql = "SELECT id, file FROM $tableName LIMIT $start, $limit";
    $result = $db->query($sql);
    while($result && $data = mysql_fetch_assoc($result)) {
        $data["file"] = unserialize($data["file"]);
        $needUpdate = false;

        foreach ($data["file"] as $key => $file) {
            if (strpos($file, "./attachment") === 0 && strrpos($file, ".jpg") === strlen($file) - 4) {
                $needUpdate = true;
                $data["file"][$key] = "http://www.firstp2p.com/attachment-view?file=".trim($file, "./") ."&f=1";
            }
        }

        if ($needUpdate) {
            ++$needUpdateCount;
            $data["file"] = serialize($data["file"]);
            $res = $db->query("UPDATE $tableName SET file = '{$data["file"]}' WHERE id = {$data["id"]}");

            if ($res === false) {
                ++$fail;
                echo "FAILED ID:{$data["id"]}" . PHP_EOL;
            } else {
                ++$success;
                echo "SUCCESS ID:{$data["id"]}" . PHP_EOL;
            }
        }
    }

    unset($result);
    $start+=1000;
}
echo "-------------------------------------" .PHP_EOL;
echo "NEED UPDATE COUNT:$needUpdateCount" . PHP_EOL;
echo "SUCCESS COUNT:$success" . PHP_EOL;
echo "FAILED COUNT::$fail" . PHP_EOL;
