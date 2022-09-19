<?php
/**
 * 新运营活动
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_activity.php');
include_once(ROOT_PATH . 'includes/Classes/PHPExcel.php');
include_once(ROOT_PATH . 'includes/Classes/PHPExcel/IOFactory.php');

$_REQUEST['to_vue'] = true;

/*------------------------------------------------------ */
//-- 活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
     /* 检查权限 */
     admin_priv('activity_list');

    /* 查询 */
    $result = activity_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['activity_list']); // 当前导航

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('activity_list',    $result['result']);
	$smarty->assign('type_list',    getActivityType());
	$smarty->assign('status_list',    getActivityStatus());

	$smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    //
    assign_query_info();
    $smarty->display('activity_list.htm');
}

elseif ($_REQUEST['act'] == 'coupon_list')
{
    /* 检查权限 */
    admin_priv('activity_coupon_list');

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['activity_coupon_list']); // 当前导航

    $smarty->display('activity_coupon_list.htm');
}

elseif ($_REQUEST['act'] == 'seckill_activity_signup')  // 秒杀活动报名
{
    /* 检查权限 */
    admin_priv('seckill_activity_signup');
    $smarty->display('seckill_activity_signup.htm');
}
elseif ($_REQUEST['act'] == 'seckill_activity_list')  // 秒杀活动管理
{
    /* 检查权限 */
    admin_priv('seckill_activity_list');
    $smarty->display('seckill_activity_list.htm');
}
elseif ($_REQUEST['act'] == 'first_red_cash_back')  	// 首单红包返现
{
    /* 检查权限 */
    admin_priv('first_red_cash_back');
    $smarty->display('first_red_cash_back.htm');
}
elseif ($_REQUEST['act'] == 'limited_sale') {   //限时折扣
    $smarty->display('limited_sale.htm');
}
/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'queryActivityList')
{
    check_authz_json('activity_list');

    $result = activity_list();
	make_json_result($result);

}
/*------------------------------------------------------ */
//-- 选择添加活动类型
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'activityTypeList'){
	/* 检查权限 */
	admin_priv('activity_edit');

	/* 模板赋值 */

	$smarty->assign('type_list',    getActivityType());

	assign_query_info();
	$smarty->display('activity_type_list.htm');
}
//获取活动类型列表
elseif($_REQUEST['act'] == 'getActivityTypeList'){
	/* 检查权限 */
	admin_priv('activity_edit');

	$result =   getActivityType();
	make_json_result($result);
}


/*------------------------------------------------------ */
//-- 添加、编辑活动
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('add', 'edit')))
{

	/* 检查权限 */
	admin_priv('activity_edit');

    $suppliers_type_list = shopTypeList();
    $suppliers_list = getSuppliers();
    $cat_list = cat_list();
    $brand_list = get_brand_list();

    if ($_REQUEST['act'] == 'add') {
        $activity = array();

        $smarty->assign('ur_here', $_LANG['add_activity']);
        $smarty->assign('form_action', 'insert');


        $smarty->assign('suppliers_type_list', $suppliers_type_list);
        $smarty->assign('suppliers_list', $suppliers_list);
        $smarty->assign('cat_list', $cat_list);
        $smarty->assign('brand_list', $brand_list);

        assign_query_info();

        $smarty->display('activity_info.htm');

    }
    elseif ($_REQUEST['act'] == 'edit') {

		$act_id = $_REQUEST['act_id'];
		if(empty($act_id)){
			make_json_error($_LANG['act_id_empty']);
		}


		$sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = " . intval($act_id);
		$activity = $db->getRow($sql);
        if (count($activity) <= 0)
        {
			sys_msg($_LANG['activity_not_exist']);
        }


        $activity['status'] = dealActivityStatus($activity);
		$activity['zh_status'] = getActivityStatus(dealActivityStatus($activity));
		$activity['start_time'] = local_date('Y-m-d H:i:s',$activity['start_time']);
		$activity['end_time'] 	= local_date('Y-m-d H:i:s',$activity['end_time']);
		$activity['ext_info'] = json_decode($activity['ext_info'],true);

		foreach ($activity['ext_info']['detail'] as $key => $item) {
			$activity['ext_info']['detail']['limit_'.$key] = $item['limit'];
			$activity['ext_info']['detail']['reduce_'.$key] = $item['reduce'];
			unset($rule['detail'][$key]);
		}

        $smarty->assign('ur_here', $_LANG['edit_suppliers']);

        $smarty->assign('form_action', 'update');

        $smarty->assign('activity_info', $activity);

		$smarty->assign('suppliers_type_list', $suppliers_type_list);
		$smarty->assign('suppliers_list', $suppliers_list);
		$smarty->assign('cat_list', $cat_list);
		$smarty->assign('brand_list', $brand_list);


        assign_query_info();

        $smarty->display('activity_info.htm');
    }

}
/*------------------------------------------------------ */
//-- 查看 活动
/*------------------------------------------------------ */
elseif(in_array($_REQUEST['act'],['activityInfo'])){

	/* 检查权限 */
	admin_priv('activity_list');


	$suppliers_type_list = shopTypeList();
	$suppliers_list = getSuppliers();
	$cat_list = cat_list();
	$brand_list = get_brand_list();

	$act_id = $_REQUEST['act_id'];

	if(empty($act_id)){
		sys_msg($_LANG['act_id_empty']);
	}


	$sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = " . intval($act_id);
	$activity = $db->getRow($sql);
	if (count($activity) <= 0)
	{
		sys_msg($_LANG['activity_not_exist']);
	}


	$activity['status'] = dealActivityStatus($activity);
	$activity['zh_status'] = getActivityStatus(dealActivityStatus($activity));
	$activity['start_time'] = local_date('Y-m-d H:i:s',$activity['start_time']);
	$activity['end_time'] 	= $activity['end_time']==0?'':local_date('Y-m-d H:i:s',$activity['end_time']);
	$activity['ext_info'] = json_decode($activity['ext_info'],true);

	foreach ($activity['ext_info']['detail'] as $key => $item) {
		$activity['ext_info']['detail']['limit_'.$key] = $item['limit'];
		$activity['ext_info']['detail']['reduce_'.$key] = $item['reduce'];
		unset($rule['detail'][$key]);
	}
	$activity+=getActivityData($act_id);
	if($activity['offline_user_id']){
		$adminUser = $db->getRow("select phone from ". $ecs->table('admin_user')." where user_id=".intval($activity['offline_user_id']));
		$activity['offline_user_phone'] = $adminUser['phone']?:'';
	}
	$smarty->assign('activity_info', $activity);

	$smarty->assign('suppliers_type_list', $suppliers_type_list);
	$smarty->assign('suppliers_list', $suppliers_list);
	$smarty->assign('cat_list', $cat_list);
	$smarty->assign('brand_list', $brand_list);

	assign_query_info();

	$smarty->display('activity_info.htm');
}

elseif(in_array($_REQUEST['act'],['getActivityInfo'])){

	/* 检查权限 */
	admin_priv('activity_list');


	$suppliers_type_list = shopTypeList();
	$suppliers_list = getSuppliers();
	$cat_list = cat_list();
	$brand_list = get_brand_list();

	$act_id = $_REQUEST['act_id'];

	if(empty($act_id)){
		make_json_error($_LANG['act_id_empty']);
	}


	$sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = " . intval($act_id);
	$activity = $db->getRow($sql);
	if (count($activity) <= 0)
	{
		make_json_error($_LANG['activity_not_exist']);
	}


	$activity['status'] = dealActivityStatus($activity);
	$activity['zh_status'] = getActivityStatus(dealActivityStatus($activity));
	$activity['start_time'] = local_date('Y-m-d H:i:s',$activity['start_time']);
	$activity['end_time'] 	= $activity['end_time']==0?'':local_date('Y-m-d H:i:s',$activity['end_time']);
	$activity['ext_info'] = json_decode($activity['ext_info'],true);
	$activity['offline_user_phone'] = '';
	if($activity['offline_user_id']){
		$adminUser = $db->getRow("select phone from ". $ecs->table('admin_user')." where user_id=".intval($activity['offline_user_id']));
		$activity['offline_user_phone'] = $adminUser['phone']?:'';
	}
	$activity+=getActivityData($act_id);


	foreach ($activity['ext_info']['detail'] as $key => $item) {
		$activity['ext_info']['detail']['limit_'.$key] = $item['limit'];
		$activity['ext_info']['detail']['reduce_'.$key] = $item['reduce'];
		unset($activity['ext_info']['detail'][$key]);
	}
	make_json_result($activity);
}


/*------------------------------------------------------ */
//-- 提交添加、编辑活动
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('insert', 'update')) && in_array($_REQUEST['type'],[2,3]) )
{

	check_authz_json('activity_edit');

	$_POST = trimData($_POST);
	$now   = time();

	$type 		   = $_REQUEST['type'];//满减活动
	$rule['sub_type']  = $_POST['sub_type'];

	if($_POST['limit_1']&&$_POST['reduce_1']){
		if(!is_numeric($_POST['limit_1'])||!is_numeric($_POST['reduce_1'])){
			make_json_error('规则数据格式有误');
		}
		$rule['detail'][1] = [
			'limit'  =>$_POST['limit_1'],
			'reduce' =>$_POST['reduce_1'],
		];
	}
	if($_POST['limit_2']&&$_POST['reduce_2']){
		if(!is_numeric($_POST['limit_2'])||!is_numeric($_POST['reduce_2'])){
			make_json_error('规则数据格式有误');
		}
		$rule['detail'][2] = [
			'limit'  =>$_POST['limit_2'],
			'reduce' =>$_POST['reduce_2'],
		];
	}
	if($_POST['limit_3']&&$_POST['reduce_3']){
		if(!is_numeric($_POST['limit_3'])||!is_numeric($_POST['reduce_3'])){
			make_json_error('规则数据格式有误');
		}
		$rule['detail'][3] = [
			'limit'  =>$_POST['limit_3'],
			'reduce' =>$_POST['reduce_3'],
		];
	}
	if(!empty($_POST['start_time'])){
		$_POST['start_time'] = strtotime($_POST['start_time']);
	}
	if(!empty($_POST['end_time'])){
		$_POST['end_time'] = strtotime($_POST['end_time']);
		if($_POST['end_time']<$_POST['start_time']){
			make_json_error('开始时间不得小于结束时间');
		}
	}



	//$goods_ids = $_POST['goods_id'];

	if(empty($rule['sub_type'])){
		make_json_error($_LANG['sub_type_empty']);
	}
	if(empty($_POST['name'])){
		make_json_error($_LANG['act_name_empty']);
	}
	if(empty($_POST['act_desc'])){
		make_json_error($_LANG['act_desc_empty']);
	}
	if(empty($_POST['start_time'])){
		make_json_error($_LANG['start_time_empty']);
	}
	if(empty($rule['detail'])){
		make_json_error($_LANG['detail_empty']);
	}
	if($_POST['limit_2']>0 && $_POST['limit_2']<$_POST['limit_1']){
		make_json_error('阶梯满金额错误');
	}
	if($_POST['limit_3']>0 && ($_POST['limit_3']<$_POST['limit_2'] || $_POST['limit_3']<$_POST['limit_1'])){
		make_json_error('阶梯满金额错误');
	}
	if($_POST['reduce_2']>0 && $_POST['reduce_2']<$_POST['reduce_1']){
		make_json_error('阶梯减金额错误');
	}
	if($_POST['reduce_3']>0 && ($_POST['reduce_3']<$_POST['reduce_2'] || $_POST['reduce_3']<$_POST['reduce_1'])){
		make_json_error('阶梯减金额错误');
	}


	$db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
	$db->query('START TRANSACTION');//开启事务

	if ($_REQUEST['act'] == 'insert')
    {
		if($_POST['start_time']<$now){
			make_json_error('开始时间异常');
		}
        /* 提交值 */
        $activity = array(
            'type'    	   => $_POST['type'],//供货商类型
            'name'    	   => $_POST['name'],//供货商类型
            'act_desc'    	   => $_POST['act_desc'],//主营业务
            'start_time'       => $_POST['start_time'],//店铺名称
            'end_time'         => $_POST['end_time']?:0,//店铺图标
			'ext_info'		   => json_encode($rule),
            'icon'         => '',
            'url'          => $_POST['url']?:'',
            'available'    => 1,
            'add_user_id'      => $_SESSION['admin_id'],
            'offline_user_id'  => 0,
            'update_time'      => $now,
            'add_time'         => $now,
        );

        /* 判断名称是否重复 */
        $sql = "SELECT act_id FROM " . $ecs->table('activity') . " WHERE name = '" . $activity['name'] . "'";
        if ($db->getRow($sql))
        {
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_name_exist']);
        }

		$addActivity = $db->autoExecute($ecs->table('activity'), $activity, 'INSERT');

        if(!$addActivity){
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_add_error']);
		}

		if(!updateCache()){
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_add_goods_error']);
		}

		$db->query("COMMIT"); //提交事务

		/* 记日志 */
		admin_log($_SESSION['admin_id'], 'add', $_REQUEST['act'].'-activity:'.print_r($_POST,true));


    }

    if ($_REQUEST['act'] == 'update')
    {

        $act_id =  $_POST['act_id'];

        if(empty($act_id)){
			make_json_error($_LANG['act_id_empty']);
		}


		$sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = ".intval($act_id);

		$activity['old'] = $db->getRow($sql);

		if (!$activity['old']) {
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_not_exist']);
		}


		$activity['old']['status'] = dealActivityStatus($activity['old']);

		if(!in_array($activity['old']['status'],[1,2])){
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_status_error']);
		}

		//未开始
		if($activity['old']['status'] == 1){
			/* 提交值 */
			$activity['new'] = array(
				'type'    	   => $_POST['type'],//供货商类型
				'act_desc'    	   => $_POST['act_desc'],//主营业务
				'name'    	   => $_POST['name'],//主营业务
				'start_time'       => $_POST['start_time'],//店铺名称
				'end_time'         => $_POST['end_time']?:0,//店铺图标
				'ext_info'		   => json_encode($rule),
			);
		}
		if(!empty($_POST['url'])){
			$activity['new']['url'] = $_POST['url'];//主营业务
		}
		//进行中
		if($activity['old']['status'] == 2){
			/* 提交值 */
			if($_POST['end_time']>$activity['old']['end_time']){
				$activity['new']['end_time'] = $_POST['end_time'];
			}
		}
		$activity['new']['update_time'] = $now;

		/* 判断活动名称是否重复 */
		$sql = "SELECT act_id FROM " . $ecs->table('activity') . " WHERE name = '" . $activity['new']['name'] . "' and act_id <> ".$act_id;
		if ($db->getRow($sql))
		{
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_name_exist']);
		}


        /* 更新活动信息 */
        $updateActivity = $db->autoExecute($ecs->table('activity'), $activity['new'], 'UPDATE', " act_id = " . $act_id );

        if(!$updateActivity){
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_edit_error']);
		}

		if(!updateCache()){
			$db->query("ROLLBACK"); //事务回滚
			make_json_error($_LANG['activity_edit_goods_error']);
		}
		$db->query("COMMIT"); //提交事务


        /* 记日志 */
        admin_log($_SESSION['admin_id'], 'edit', $_REQUEST['act'].'-activity:'.print_r($_POST,true));


    }

	/* 清除缓存 */
	clear_cache_files();

	make_json_result([],$_LANG[$_REQUEST['act'].'_activity_ok']);

}

elseif($_REQUEST['act']=='changeActivityStatus'){

	/* 检查权限 */
	check_authz_json('activity_edit');

	$now = time();

	$db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
	$db->query('START TRANSACTION');//开启事务

	$act_id = $_POST['act_id'];
	if(empty($act_id)){
		make_json_error($_LANG['act_id_empty']);
	}
	$sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = ".intval($act_id);

	$activity = $db->getRow($sql);
	$status = dealActivityStatus($activity);
	if($status>2){
		make_json_error($_LANG['activity_status_error']);
	}

	$remark = $_POST['remark'];

	if(empty($remark)){
		make_json_error('备注信息不能为空');
	}

	$sql = "UPDATE ".$ecs->table('activity')." SET available = 0,offline_user_id = {$_SESSION['admin_id']} , update_time = {$now} , end_time = {$now} , remark='{$remark}' WHERE act_id = " . $act_id;
	$updateActivity = $db->query($sql);

	if(!$updateActivity){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_edit_error']);
	}


	$sql = "UPDATE ".$ecs->table('activity_goods')." SET available = 0,update_time = {$now}  WHERE act_id = " . $act_id;
	$updateGoods = $db->query($sql);

	if(!$updateGoods){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_edit_goods_error']);
	}

	if(!updateCache()){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_edit_goods_error']);
	}
	$db->query("COMMIT"); //提交事务
	/* 记日志 */
	admin_log($_SESSION['admin_id'], 'edit', $_REQUEST['act'].'-activity:'.$act_id);



	make_json_result([],$_LANG['edit_activity_ok']);

}

elseif($_REQUEST['act']=='changeCouponActivityStatus'){

    /* 检查权限 */
    check_authz_json('activity_edit');

    $now = time();

    $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    $db->query('START TRANSACTION');//开启事务

    $act_id = $_POST['act_id'];
    $status = $_POST['status'];
    if(empty($act_id)){
        make_json_error($_LANG['act_id_empty']);
    }
    $sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = ".intval($act_id);

    $activity = $db->getRow($sql);
    if($status == 1){
        $sql = "UPDATE ".$ecs->table('activity')." SET available = 1, update_time = {$now} WHERE act_id = " . $act_id;
    }else{
        //优惠券结算
        $back_amount = $activity['amount'] - $activity['used_amount'];
        $ext_info = json_decode($activity['ext_info'], true);
        $back_money = $back_amount * $ext_info['detail'][1]['reduce'];
        if($back_money >= 0){
            //优先返回信用额度,再返回权益币余额
            $suppliers_account = $db->getRow("select * from " .$ecs->table('suppliers_account'). " where suppliers_id = {$activity['suppliers_id']}");
            //$back_credit_line = $suppliers_account['used_credit_line'];
            $used_credit_line = 0;
            $back_huanbi_money = 0;
            if($back_money <= $suppliers_account['used_credit_line']){
                $used_credit_line = $suppliers_account['used_credit_line'] - $back_money;
                $back_money = 0;
            }else{
                $back_huanbi_money = $back_money - $suppliers_account['used_credit_line'];
            }
            $set = "used_credit_line = {$used_credit_line}";
            //退回权益币余额
            if($back_huanbi_money > 0){
                $set .= ", huanbi_money = huanbi_money + {$back_huanbi_money}";

                //记录权益币退回流水
                $suppliersAccountLogInfo = [
                    'type' 		            => 'goods_coupon',
                    'addtime' 	            => time(),
                    'direction'             => 1,
                    'suppliers_id' 	        => $activity['suppliers_id'],
                    'related_info' 	        => date('YmdHis'),
                    'current_huanbi_money' 	=> $suppliers_account['huanbi_money'] ?: 0,
                    'current_cash_money' 	=> $suppliers_account['current_cash_money'] ?: 0,
                    'huanbi_money' 	        => $back_huanbi_money,
                ];
                $insert = $db->autoExecute($ecs->table('suppliers_account_log'), $suppliersAccountLogInfo, 'INSERT');
                if(!$insert){
                    $db->query("ROLLBACK"); //事务回滚
                    make_json_error($_LANG['activity_edit_error']);
                }
            }
            $update_sql = "update " .$ecs->table('suppliers_account'). " set {$set} where id = {$suppliers_account['id']}";
            if(!$db->query($update_sql)){
                $db->query("ROLLBACK"); //事务回滚
                make_json_error($_LANG['activity_edit_error']);
            }
        }
        $sql = "UPDATE ".$ecs->table('activity')." SET available = 0,offline_user_id = {$_SESSION['admin_id']} , update_time = {$now} , end_time = {$now} , remark='{$remark}', back_amount = {$back_amount} WHERE act_id = " . $act_id;
    }
    $updateActivity = $db->query($sql);

    if(!$updateActivity){
        $db->query("ROLLBACK"); //事务回滚
        make_json_error($_LANG['activity_edit_error']);
    }

    if($status != 1){
        $sql = "UPDATE ".$ecs->table('activity_goods')." SET available = 0,update_time = {$now}  WHERE act_id = " . $act_id;
        $updateGoods = $db->query($sql);

        if(!$updateGoods){
            $db->query("ROLLBACK"); //事务回滚
            make_json_error($_LANG['activity_edit_goods_error']);
        }
    }

    if(!updateCache()){
        $db->query("ROLLBACK"); //事务回滚
        make_json_error($_LANG['activity_edit_goods_error']);
    }
    $db->query("COMMIT"); //提交事务
    /* 记日志 */
    admin_log($_SESSION['admin_id'], 'edit', $_REQUEST['act'].'-activity:'.$act_id);
    make_json_result([],$_LANG['edit_activity_ok']);
}

//删除活动商品
elseif($_REQUEST['act']=='removeGoods'){

	/* 检查权限 */
	check_authz_json('activity_edit');

	$now = time();
	$goods_id =$_POST['goods_id'];

	$act_id 	= $_POST['act_id'];
	if(empty($act_id)){
		make_json_error($_LANG['act_id_empty']);
	}
	if(empty($goods_id)){
		make_json_error($_LANG['act_goods_id_empty']);
	}

	$db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
	$db->query('START TRANSACTION');//开启事务

	$goods_id = implode(',',$goods_id);

	$sql = "SELECT * FROM " . $ecs->table('activity') . " WHERE act_id = ".intval($act_id);

	$activity = $db->getRow($sql);
	if(empty($activity)){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_not_exist']);
	}
	$status = dealActivityStatus($activity);
	//进行中不能移除商品
	if($status!=1){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_status_not_modify_goods']);
	}

	$sql = "UPDATE ".$ecs->table('activity_goods')." SET available = 2,update_time = {$now}  WHERE act_id = $act_id and goods_id in (".$goods_id.")"  ;
	$updateGoods = $db->query($sql);
	if(!$updateGoods){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_edit_goods_error']);
	}

	$db->query("COMMIT"); //提交事务

	/* 记日志 */
	admin_log($_SESSION['admin_id'], 'edit', $_REQUEST['act'].'-activity_id:'.$act_id.' goods_id'.$goods_id);

	//updateCache();

	make_json_result([],$_LANG['edit_activity_ok']);

}
//添加商品
elseif($_REQUEST['act']=='addGoods'){
	/* 检查权限 */
	check_authz_json('activity_edit');

	$now = time();
	$goods_ids = $_POST['goods_id'];

	$act_id 	= $_POST['act_id'];
	if(empty($act_id)){
		make_json_error($_LANG['act_id_empty']);
	}
	if(empty($goods_ids)){
		make_json_error($_LANG['act_goods_id_empty']);
	}
	if(!is_array($goods_ids)){
		make_json_error('商品id格式错误');
	}

	$db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
	$db->query('START TRANSACTION');//开启事务

    $sql = "select type from " . $ecs->table('activity') . "where act_id = {$act_id}";
    $activity = $db->getRow($sql);

	$joining_goods = joining_activity_goods();
	$sql = 'INSERT INTO ' . $ecs->table('activity_goods') . " (id,act_id,goods_id,available,add_time,update_time)  VALUES  ";
	foreach ($goods_ids as $goods_id) {
		if(array_key_exists($goods_id,$joining_goods)){
				make_json_error('商品id:'.$goods_id.$_LANG['activity_goods_joining']);
		}else{
			$sql .=	"(0,$act_id,$goods_id,1,$now,$now),";
		}
	}
	$sql = rtrim($sql,',');
	$addGoods = $db->query($sql);
	if(!$addGoods){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_add_goods_error']);
	}
	if(!updateCache()){
		$db->query("ROLLBACK"); //事务回滚
		make_json_error($_LANG['activity_edit_goods_error']);
	}
	$db->query("COMMIT"); //提交事务


	make_json_result([],'添加商品成功');

}

/*------------------------------------------------------ */
//-- 查询可加入商品
/*------------------------------------------------------ */

elseif($_REQUEST['act']=='getWaitGoods'){
	/* 检查权限 */
	check_authz_json('activity_edit');
	//获取商品列表
	$wait_goods = wait_activity_goods_list();
	make_json_result($wait_goods);
}
/*------------------------------------------------------ */
//-- 查询已加入商品
/*------------------------------------------------------ */

elseif($_REQUEST['act']=='getJoinGoods'){
	check_authz_json('activity_edit');

	$joining_goods = get_join_goods($_REQUEST['act_id']);
	make_json_result($joining_goods);
}

elseif($_REQUEST['act']=='queryActivityName'){

	check_authz_json('activity_edit');

	if(empty($_REQUEST['name'] )){
		make_json_error($_LANG['act_name_empty']);
	}
	/* 判断名称是否重复 */
	$sql = "SELECT act_id FROM " . $ecs->table('activity') . " WHERE name = '" . $_REQUEST['name'] . "'";
	if ($db->getRow($sql))
	{
		make_json_error($_LANG['activity_name_exist']);
	}

	make_json_result([],$_LANG['activity_name_not_exist']);

}
elseif($_REQUEST['act']=='test'){
	        $filename = "【". local_date('Ynj', gmtime()). "】订单.xls";

			$obj=new PHPExcel();             //创建表
            $sheet=$obj->getActiveSheet(0);   //确定活动表

            $sheet->setCellValue('A1','dfdfd');
            $sheet->setCellValue('B1','fdfdfdfd');
            $sheet->setCellValue('C1','fdfdfdfd');



	        header("Content-type: application/octet-stream;charset=utf-8");
	        header("content-Disposition:attachement;filename= ".urlencode($filename));
	        $write = PHPExcel_IOFactory::createWriter($obj, 'Excel5');

	        $write->save('/data/web_doc_root/ecshop_web/appserver/storage/logs/'.$filename);
}
elseif($_REQUEST['act']=='getSuppliersTypeList'){
	$suppliers_type_list = shopTypeList();
	make_json_result($suppliers_type_list);
}
elseif($_REQUEST['act']=='getSuppliersList'){
	$suppliers_list = getSuppliers();
	make_json_result($suppliers_list);
}
elseif($_REQUEST['act']=='getCatList'){
	$cat_list = cat_list();
	make_json_result($cat_list);
}
elseif($_REQUEST['act']=='getSaleTypeList'){
	$sale_type_list = saleTypeList();
	make_json_result($sale_type_list);
}
elseif ($_REQUEST['act'] == 'staticActivityActList') {  //  静态活动页管理
    //活动列表
    /* 检查权限 */
    admin_priv('publish_static_activity_manage');
    $smarty->display('static_activity_list.htm');
}




function saleTypeList(){
	return [1=>'上架中',0=>'已下架'];
}

/**
 *  获取活动列表
 */
function activity_list()
{
    $result = get_filter();
    $now = time();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 ';
        if(!empty($_REQUEST['name']))
        {
            $where .= " AND name like '%".$_REQUEST['name']."%'";
        }
		if($_REQUEST['type'])
		{
			$where .= " AND type  =  ".$_REQUEST['type'];
		}
		if($_REQUEST['status'])
		{
			switch ($_REQUEST['status']){
				case 1://未开始
					$where .= " AND available  =  1  AND  start_time > $now ";
					break;
				case 2://进行中
					$where .= " AND available  = 1  AND  start_time <= $now  AND (end_time > $now or end_time = 0) ";
					break;
				case 3://已下线
					$where .= " AND available  = 0  ";
					break;
				case 4://已结束
					$where .= " AND available  = 1  AND  end_time < $now and end_time > 0 ";
					break;
			}
		}
        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('activity') . $where;
        $filter['record_count']   = intval($GLOBALS['db']->getOne($sql));
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT *
                FROM " . $GLOBALS['ecs']->table("activity") . "
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

	/* 格式话数据 */
	foreach ($row AS $key => $value)
	{
		$row[$key]['start_time'] = local_date('Y-m-d H:i:s',$value['start_time']);
		$row[$key]['end_time']   = local_date('Y-m-d H:i:s',$value['end_time']);
		$row[$key]['type']   = strval(getActivityType($value['type']));
		$row[$key]['status'] = dealActivityStatus($value);
		$row[$key]['zh_status'] = getActivityStatus(dealActivityStatus($value));

	}

    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 处理活动状态
 * @param $data
 * @return int
 */
function dealActivityStatus($data){

	$now = time();
	if(($data['available']==1 && $data['start_time']>$now) || ($data['available'] == 0 && $data['offline_user_id'] == 0)){
		return 1;
	}
	if($data['available']==1 && $data['start_time'] <= $now && ($data['end_time'] == 0 ||  $data['end_time'] > $now)){
		return 2;
	}
	if($data['available']==0 ){
		return 3;
	}
	if($data['available']==1 && $data['end_time'] < $now && $data['end_time']>0 ){
		return 4;
	}
	return 0 ;
}

/**
 * 获取商家
 */
function getSuppliers(){
	$where = ' 1  ';
	if($_REQUEST['suppliers_type']){
		$where = ' type = '.intval($_REQUEST['suppliers_type']);
	}
	$sql = "SELECT suppliers_id,suppliers_name FROM  " . $GLOBALS['ecs']->table('suppliers') . " WHERE $where  AND is_delete = 0 AND is_check = 1 ";
	$suppliers = $GLOBALS['db']->getAll($sql);
	if(!empty($suppliers)){
		$suppliers = array_column($suppliers,'suppliers_name','suppliers_id');
	}
	return $suppliers;
}


/**
 * 获得待添加商品列表
 * @param int $act_id
 * @param string $conditions
 * @return array
 */
function wait_activity_goods_list($act_id=0, $conditions = ''){

	/* 过滤条件 */
	$param_str = '-' . $act_id .'-'.$conditions ;
	$result = get_filter($param_str);
	if ($result === false)
	{
		$_REQUEST = trimData($_REQUEST);


		if(isset($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] !== '') $filter['is_on_sale']= intval($_REQUEST['is_on_sale']);
		$filter['suppliers_type']   = empty($_REQUEST['suppliers_type']) ? '' : intval($_REQUEST['suppliers_type']);
		$filter['suppliers_id']     = empty($_REQUEST['suppliers_id']) ? '' : intval($_REQUEST['suppliers_id']);
		$filter['cat_id']           = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
		$filter['brand_id']         = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
		$filter['goods_sn']         = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
		$filter['goods_name']       = empty($_REQUEST['goods_name']) ? '' : trim($_REQUEST['goods_name']);
		$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'goods_sn' : trim($_REQUEST['sort_by']);
		$filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		$where = ' g.is_delete = 0 AND s.is_delete=0 AND s.is_check = 1 ';
		/* 商家 */
		if($filter['suppliers_id']){
			$where .= ' AND s.suppliers_id = '.$filter['suppliers_id'];
		}

		/* 上下架 */
		if(isset($filter['is_on_sale'])){
			$where .= ' AND g.is_on_sale = '.$filter['is_on_sale'];
		}

		if($conditions){
			$where .= ' AND	'.$conditions;
		}
		/* 渠道 */
		if($filter['suppliers_type']){
			$where .= ' AND s.type = '.$filter['suppliers_type'];
		}
		/* 分类 */
		if($filter['cat_id'] > 0){
			$where .= ' AND ' . get_children($filter['cat_id']);
		}
		/* 品牌 */
		if ($filter['brand_id']) {
			$where .= ' AND g.brand_id = '.$filter['brand_id'];
		}
		/* 货号 */
		if ($filter['goods_sn']) {
			$where .= " AND g.goods_sn = '".$filter['goods_sn']."' ";
		}
		/* 名称 */
		if ($filter['goods_name']) {
			$where .= " AND g.goods_name like '%".$filter['goods_name']."%'";
		}
		/* 商家登录 */
		if($_SESSION['suppliers_id'] != 0) {
			$where .= " AND g.suppliers_id in (" . $_SESSION['suppliers_id'] . ")";
		}

		/* 记录总数 */
		$sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('goods'). " AS g left join ".$GLOBALS['ecs']->table('suppliers')." AS s  ON g.suppliers_id=s.suppliers_id WHERE  $where";
		$filter['record_count'] = intval($GLOBALS['db']->getOne($sql));

		/* 分页大小 */
		$filter = page_and_size($filter);

		$sql = "SELECT goods_id, goods_name, goods_sn, shop_price , goods_number, money_line ,is_on_sale  FROM " . $GLOBALS['ecs']->table('goods') . " AS g left join ".$GLOBALS['ecs']->table('suppliers')." AS s  ON g.suppliers_id=s.suppliers_id WHERE  $where " .
			" ORDER BY ".$filter['sort_by']." ". $filter['sort_order']. " LIMIT " . $filter['start'] . ",".$filter['page_size'];

		set_filter($filter, $sql, $param_str);
	}
	else
	{
		$sql    = $result['sql'];
		$filter = $result['filter'];
	}
	$row = $GLOBALS['db']->getAll($sql);

	$joining_goods = joining_activity_goods();

	if(!empty($row)){
		foreach ($row as $k => $item) {
			$row[$k]['activity_info'] = array_key_exists($item['goods_id'],$joining_goods)?'已参加'.$joining_goods[$item['goods_id']]['name']:'';
			$row[$k]['goods_price']   = $item['money_line']==-1 ? $item['shop_price']:('￥'.bcsub($item['shop_price'],$item['money_line'],2)."+".$item['money_line']);
			$row[$k]['zh_is_on_sale'] = $item['is_on_sale']==1?"上架中":"已下架";
			$row[$k]['coupon'] = $item['goods_act_num'] >= 2 ? 0 : 1;
			unset($row[$k]['shop_price'],$row[$k]['money_line']);
		}
	}

	return array('goods' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
 * 获取不能参加活动的商品id
 * @return mixed
 */
function joining_activity_goods(){
	$now = time();
	$goods = [];
	$sql = "select ag.goods_id ,a.act_desc,a.act_id,a.name,count(*) goods_act_num from ".$GLOBALS['ecs']->table('activity_goods')." as ag left join ".$GLOBALS['ecs']->table('activity')." as a on ag.act_id=a.act_id where ( a.end_time > $now  or a.end_time = 0) AND (a.available  = 1 or (a.available  = 0 and a.offline_user_id = 0))  AND ag.available = 1 group by ag.goods_id,a.act_id";
	$row = $GLOBALS['db']->getAll($sql);
	if(!empty($row)){
		foreach ($row as $v){
			$goods[$v['goods_id']]=$v;
		}
	}
	return $goods;
}


/**
 * 获取参加活动的商品
 * @param int $act_id
 * @param string $conditions
 * @return array
 */
function get_join_goods($act_id=0, $conditions = ''){
	/* 过滤条件 */
	$param_str = '-' . $act_id .'-'.$conditions ;
	$result = get_filter($param_str);
	if ($result === false)
	{
		$_REQUEST = trimData($_REQUEST);
		if(isset($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] !== '') $filter['is_on_sale']= intval($_REQUEST['is_on_sale']);
		$filter['suppliers_type']   = empty($_REQUEST['suppliers_type']) ? '' : intval($_REQUEST['suppliers_type']);
		$filter['suppliers_id']     = empty($_REQUEST['suppliers_id']) ? '' : intval($_REQUEST['suppliers_id']);
		$filter['cat_id']           = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
		$filter['brand_id']         = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
		$filter['goods_sn']         = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
		$filter['goods_name']       = empty($_REQUEST['goods_name']) ? '' : trim($_REQUEST['goods_name']);
		$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'goods_sn' : trim($_REQUEST['sort_by']);
		$filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		$where = ' g.is_delete = 0 AND s.is_delete=0 AND s.is_check = 1 AND ag.available = 1 ';

		if($act_id){
			$where .= ' AND	ag.act_id = '.$act_id;
		}
		if($conditions){
			$where .= ' AND	'.$conditions;
		}
		/* 上下架 */
		if(isset($filter['is_on_sale'])){
			$where .= ' AND g.is_on_sale = '.$filter['is_on_sale'];
		}
		/* 渠道 */
		if($filter['suppliers_type']){
			$where .= ' AND s.type = '.$filter['suppliers_type'];
		}
		/* 商家 */
		if($filter['suppliers_id']){
			$where .= ' AND s.suppliers_id = '.$filter['suppliers_id'];
		}
		/* 分类 */
		if($filter['cat_id'] > 0){
			$where .= ' AND ' . get_children($filter['cat_id']);
		}
		/* 品牌 */
		if ($filter['brand_id']) {
			$where .= ' AND g.brand_id = '.$filter['brand_id'];
		}
		/* 货号 */
		if ($filter['goods_sn']) {
			$where .= " AND g.goods_sn = '".$filter['goods_sn']."'";
		}
		/* 名称 */
		if ($filter['goods_name']) {
			$where .= " AND g.goods_name like '%".$filter['goods_name']."%'";
		}
		/* 商家登录 */
		if($_SESSION['suppliers_id'] != 0) {
			$where .= " AND g.suppliers_id in (" . $_SESSION['suppliers_id'] . ")";
		}

		/* 记录总数 */
		$sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('activity_goods'). " AS ag left join " .$GLOBALS['ecs']->table('goods'). " AS g on ag.goods_id=g.goods_id left join ".$GLOBALS['ecs']->table('suppliers')." AS s  ON g.suppliers_id=s.suppliers_id WHERE  $where";
		$filter['record_count'] = intval($GLOBALS['db']->getOne($sql)) ;

		/* 分页大小 */
		$filter = page_and_size($filter);

		$sql = "SELECT g.goods_id, g.goods_name, g.goods_sn, g.shop_price , g.goods_number, g.money_line,g.is_on_sale   FROM ".$GLOBALS['ecs']->table('activity_goods'). " AS ag left join "  . $GLOBALS['ecs']->table('goods') . " AS g on ag.goods_id=g.goods_id  left join ".$GLOBALS['ecs']->table('suppliers')." AS s  ON g.suppliers_id=s.suppliers_id WHERE  $where " .
			" ORDER BY ".$filter['sort_by']." ". $filter['sort_order']. " LIMIT " . $filter['start'] . ",".$filter['page_size'];

		set_filter($filter, $sql, $param_str);
	}
	else
	{
		$sql    = $result['sql'];
		$filter = $result['filter'];
	}
	$row = $GLOBALS['db']->getAll($sql);


	if(!empty($row)){
		foreach ($row as $k => $item) {
			$row[$k]['goods_price']   = $item['money_line']==-1 ? $item['shop_price']:('￥'.bcsub($item['shop_price'],$item['money_line'],2)."+".$item['money_line']);
			$row[$k]['zh_is_on_sale'] = $item['is_on_sale']==1?"上架中":"已下架";

			unset($row[$k]['shop_price'],$row[$k]['money_line']);
		}
	}

	return array('goods' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
 * 清理server里的缓存数据
 * @param int $i
 */
function updateCache(){
	$re =  exec(EXEC_PHP.APPSERVER_PATH.'/artisan GoodsActivityData');
	if(($re!='SUCCESS')){
		$re =  exec(EXEC_PHP.APPSERVER_PATH.'/artisan GoodsActivityData');
	}
	if($re!='SUCCESS'){
		return false;
	}
	return true;
}

function getActivityData($act_id){

	$activityInfo = [
		'pay_num'=>0,
		'pay_money'=>0,
		'pay_users'=>0
	];

	$sql = "select DISTINCT(order_id)  from ".$GLOBALS['ecs']->table('order_activity')." where act_id=".$act_id;
	$orders = $GLOBALS['db']->getAll($sql);
	if(!count($orders)){
		return $activityInfo;
	}

	$order_ids = implode(',',array_column($orders,'order_id')) ;

	$sql = " select count(order_id) as pay_num,count(DISTINCT(user_id)) as pay_users, sum(surplus+money_paid) as pay_money  from ".$GLOBALS['ecs']->table('order_info'). "  where order_id in ({$order_ids}) and  pay_status = 2 ";

	$res =  $GLOBALS['db']->getRow($sql);
	$activityInfo['pay_num'] 	= $res['pay_num']?$res['pay_num']:0;
	$activityInfo['pay_money'] 	= $res['pay_money']?number_format($res['pay_money'],2):0;
	$activityInfo['pay_users'] 	= $res['pay_users']?$res['pay_users']:0;

	return $activityInfo;
}



