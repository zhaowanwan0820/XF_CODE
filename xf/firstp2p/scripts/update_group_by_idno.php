<?php
/**
 *
 * @desc 修改用户组脚本
 *
 */


set_time_limit(0);

ini_set('memory_limit', '512M');
require_once(dirname(dirname(__FILE__)).'/app/init.php');
die('如果使用该脚本请调用$idno = \libs\utils\DBDes::encryptOneValue($idno);对idno加密后查询!');//add by lvbaosong 2016.4.29
fwrite(STDOUT, "Please input file path: ");

$file = trim(fgets(STDIN));
if (!is_file($file)) {
    exit("Input file error.\n");
}

fwrite(STDOUT, "Please input old group ID: ");
$old_group_id = intval(trim(fgets(STDIN)));

if ($old_group_id <= 0) {
    exit("Error old group id.\n");
}

fwrite(STDOUT, "Please input new group ID: ");
$group_id = intval(trim(fgets(STDIN)));

if ($group_id <= 0) {
    exit("Error group id.\n");
}

if ($old_group_id == $group_id) {
    exit("old group id can not equal new group id.\n");
}


$host = app_conf('DB_HOST');
$port = app_conf('DB_PORT');
$user = app_conf('DB_USER');
$pass = app_conf('DB_PWD');
$dbname = app_conf('DB_NAME');

$con = mysql_connect("$host:$port", $user, $pass);
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_query("set names utf8", $con);
mysql_select_db($dbname, $con);


function validateId($idno) {
    if (!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idno)) {
        if (!preg_match("/^[a-zA-Z0-9]+$/",$idno)) {
            return false;
        }
        return true;
    }
    return true;
}

$handle = fopen($file, 'r');

if (!$handle) {
    exit("Please input file.");
}

while(!feof($handle)) {
    $idno = trim(fgets($handle, 4096));
    if (empty($idno)) {
        continue;
    }
    $idno = strtoupper($idno);
    $is_validate = validateId($idno);
    if ($is_validate) {
        usleep(10000);
        $query = mysql_query("SELECT `group_id` FROM `firstp2p_user` WHERE `idno`='$idno';");
        $result = mysql_fetch_assoc($query);
        if (!isset($result['group_id'])) {
            echo "$idno notexist\n";
            continue;
        }

        if ($result['group_id'] != $old_group_id) {
            echo "$idno group_id is error\t{$result['group_id']}\n";
            continue;
        }

        $sql = "UPDATE `firstp2p_user` SET `group_id`=$group_id WHERE `idno` ='$idno';";
        $count = mysql_query($sql, $con);
        if ($count) {
            echo "$idno success\t{$result['group_id']}\n";
        } else {
            echo "$idno failed\t{$result['group_id']}\t$sql\n";
        }
    } else {
        echo "$idno error idno\n";
    }
}
exit("done\n");
