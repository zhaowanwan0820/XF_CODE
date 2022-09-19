<?php

/**
 * ECSHOP 支付接口函数库
 * ============================================================================
 * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: lib_activity.php 17218 2011-01-24 04:10:41Z yehuaixiao $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 活动类型
 * @param int $type
 * @return array|mixed
 */
function getActivityType($type = 0){
	$typeArr = [
		1=>'折扣活动',
		2=>'满N元减活动',
		3=>'满N件减活动',
		4=>'预售活动',
		5=>'满N元送活动',
		6=>'满N件送活动',
	];
	if($type){
		return $typeArr[$type];
	}
	return $typeArr;
}


/**
 * 活动状态
 * @param int $status
 * @return array|mixed
 */
function getActivityStatus($status = 0){
	$statusArr = [
		1=>'未开始',
		2=>'进行中',
		3=>'已下线',
		4=>'已结束',
	];
	if($status){
		return $statusArr[$status];
	}
	return $statusArr;
}

/**
 * 根据属性获取活动
 * @param $attribute
 * @return array
 */
function getActivityInfoByAttribute($attribute){
	$activityInfo = [];
	$where = " 1 ";
	if($attribute['act_type']){
		$where.= ' AND type = '.$attribute['act_type'];
	}
	if($attribute['act_name']){
		$where.= " AND  name like '%".trim($attribute['act_name'])."%'";
	}
	$sql = "SELECT act_id,`name`,act_desc,start_time,end_time,`type` FROM " . $GLOBALS['ecs']->table('activity') . " WHERE ".$where;

	$arr = $GLOBALS['db']->getAll($sql);
	if(!empty($arr)){
		foreach ($arr as $item) {
			$activityInfo[$item['act_id']] = $item;
		}
	}
	return $activityInfo;

}