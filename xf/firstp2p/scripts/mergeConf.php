<?php
require(dirname(__FILE__) . '/../app/init.php');
$idArray = array(2,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50);
$searchStr = 'name IN ("BONUS_SHARE_DETAIL_LINK", "BONUS_SHARE_DETAIL_DESC", "BONUS_SHARE_DETAIL_TITLE")';
$GLOBALS['db']->startTrans();
try {
    $sql = 'SELECT * FROM firstp2p_conf WHERE ' . $searchStr;
    echo $sql . "\n";
    $needUpdates = $GLOBALS['db']->getAll($sql);
    if (empty($needUpdates) || count($needUpdates) != 3) {
        throw new \Exception('待更新的数据有误');
    }
    $sql = 'UPDATE firstp2p_conf SET site_id = 1 WHERE ' . $searchStr;
    $res = $GLOBALS['db']->query($sql);
    echo $sql . "\n";
    if (!$res || $GLOBALS['db']->affected_rows() != 3) {
        throw new \Exception('更新主站失败');
    }
    foreach ($needUpdates as $data) {
        unset($data['id']);
        $fields = array_keys($data);
        $fieldStr = implode($fields, ',');
        $valueStr = '';
        foreach ($idArray as $site_id) {
            $valueStr .= '(';
            foreach ($fields as $field) {
                if ($field == 'site_id') {
                    $valueStr .= "'$site_id',";
                } else {
                    $valueStr .= "'{$data[$field]}',";
                }
            }
            $valueStr = trim($valueStr, ',') . '),';
        }
        $valueStr = trim($valueStr, ',');
        $sql = 'INSERT INTO firstp2p_conf ('.$fieldStr.') values ' .$valueStr. '';
        echo $sql . "\n";
        $res = $GLOBALS['db']->query($sql);
        if (!$res) {
            throw new \Exception('插入' .$data['name']. '失败');
        }
    }
    //$GLOBALS['db']->rollback();
    $GLOBALS['db']->commit();
} catch(\Exception $e) {
    $GLOBALS['db']->rollback();
    echo $e->getMessage() . '\n';
}
