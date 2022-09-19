<?php
require(dirname(__FILE__) . '/../app/init.php');

$sql = "SELECT id, `desc`, status FROM `firstp2p_user_carry` WHERE update_time_step1 between UNIX_TIMESTAMP('2014-09-19 00:00:00') - 28800 and UNIX_TIMESTAMP('2014-09-19 12:00:00') - 28800";
$result = $GLOBALS['db']->query($sql);

while($result && $data = mysql_fetch_assoc($result)) {

    if (!$data['desc']) {
        continue;
    }

    $descArray = explode('<p>', $data['desc']);
    $filterArray = array();
    foreach ($descArray as $key => $value) {
        if ($value) {
            $filterArray[$value] = $value;
        }
    }

    $desc = '<p>' . implode('<p>', $filterArray);
    $now = get_gmtime();
    // update process
    $res = $GLOBALS['db']->query("UPDATE firstp2p_user_carry SET `desc` = '".$desc."', update_time = $now WHERE id = " . $data['id']);

    if ($res === false) {
        echo "FAILED ID:{$data["id"]}" . PHP_EOL;
    } else if ($GLOBALS['db']->affected_rows() > 0) {
        echo "SUCCESS ID:{$data["id"]}" . PHP_EOL;
    }
}
