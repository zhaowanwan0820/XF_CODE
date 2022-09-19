<?php

/**
 * 60天后自动收货
 */
return;


if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/consignee.php';
if (file_exists($cron_lang))
{
    global $_LANG;

    include_once($cron_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'consignee_desc';

    /* 作者 */
    $modules[$i]['author']  = 'admin';

    /* 网址 */
    $modules[$i]['website'] = '';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.1';

    /* 配置信息 */
    $modules[$i]['config']  = array();
    return;
}

/*
 * 超过N天未收货则自动收货
 * */

$sql = " select type,suppliers_id from " . $GLOBALS['ecs']->table('suppliers') . " where is_delete = 0";
$suppliers= $GLOBALS['db']->getAll($sql);



$suppliers[] = ['type'=>99,'suppliers_id'=>0];//兼容历史数据

foreach ($suppliers as $supplier) {
	$timeLimit = strtotime('-30day midnight');
	if($supplier['type']==2) $timeLimit = strtotime('-14day midnight');
    dealConfirm($supplier['suppliers_id'],$timeLimit);

}


function dealConfirm($suppliers_id,$timeLimit){
	$model = "select order_id,order_status,pay_status,mlm_id from " . $GLOBALS['ecs']->table('order_info') . " where suppliers_id = $suppliers_id and  shipping_time <= $timeLimit and shipping_status = 1";
	$data = $GLOBALS['db']->getAll($model);
	file_put_contents(ROOT_PATH.'temp/common/common.log',date('Y-m-d H:i:s',time()),FILE_APPEND);
	if (!empty($data)) {
		file_put_contents(ROOT_PATH.'temp/common/common.log','successData'.date('Y-m-d H:i:s',time()).json_encode($data),FILE_APPEND);
		foreach ($data as $k => $v) {
			$GLOBALS['db']->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
			$GLOBALS['db']->query('START TRANSACTION');//开启事务
			$shipping_status = 2;
			$order_id = $v['order_id'];
			$order_status = $v['order_status'];
			$pay_status = $v['pay_status'];
			$time = time();
			$sql = "update " . $GLOBALS['ecs']->table('order_info') . " set shipping_status = $shipping_status where order_id = $order_id";
			$updateRes = $GLOBALS['db']->query($sql);
			if(!$updateRes){
				$db->query("ROLLBACK"); //事务回滚

			}
			$insertSql = "INSERT INTO " . $GLOBALS['ecs']->table('order_action') . "
                (`order_id`,`action_user`,`order_status`,`shipping_status`,`pay_status`,`action_place`,`action_note`,`log_time`)
                VALUES($order_id,'脚本',$order_status,$shipping_status,$pay_status,0,'脚本执行',$time)";
			$GLOBALS['db']->query($insertSql);
		}
	}
	file_put_contents(ROOT_PATH.'temp/common/common.log','failData'.date('Y-m-d H:i:s',time()),FILE_APPEND);

}


?>
