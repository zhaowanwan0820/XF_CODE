<?php

require(dirname(__FILE__) . '/../../app/init.php');
require(APP_ROOT_PATH.'libs/utils/PhalconRPCInject.php');

error_reporting(E_ERROR);
ini_set('display_errors', 1);
\libs\utils\PhalconRpcInject::init();

/**
 * 格式化处理地址--递归
 * @param $array
 * @param int $pid 父id
 * @param int $depth 层级深度
 * @return array
 */
function formatTreeByRecursion($array, $pid = 1, $depth = 1) {
    // 默认只取四级数据
    if ($depth == 4) {
        return array();
    }

    $arr = array();
    $item  = array();
    foreach ($array as $val) {
        if ($val['pid'] == $pid) {


            $item = formatTreeByRecursion($array, $val['id'], $depth + 1);
            // 判断是否存在子数组
            $item && $val['s'] = $item;
            $arr[] = $val;
        }
    }
    return $arr;
}

$sql = "select id,pid,name from firstp2p_delivery_region";
$result = $GLOBALS['db']->get_slave()->getAll($sql);
$formatRes = formatTreeByRecursion($result, 1);
$res = array('Jsonlist'=>$formatRes);

$jsonData = json_encode($res, JSON_UNESCAPED_UNICODE);
//file_put_contents('cityjson.json', $jsonData);
echo $jsonData;