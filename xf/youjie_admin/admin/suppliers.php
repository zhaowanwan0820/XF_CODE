<?php

/**
 * ECSHOP 管理中心供货商管理
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: wanglei $
 * $Id: suppliers.php 15013 2009-05-13 09:31:42Z wanglei $
 */
header("Access-Control-Allow-Origin:*");
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with, content-type');
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
define('SUPPLIERS_ACTION_LIST', 'delivery_view,back_view');
/*------------------------------------------------------ */
//-- 供货商列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list') {
    /* 检查权限 */
    admin_priv('suppliers_list');

    /* 查询 */
    $result = suppliers_list();
    //var_dump($result);

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['suppliers_list']); // 当前导航
    $smarty->assign('action_link', array('href' => 'suppliers.php?act=add', 'text' => $_LANG['add_suppliers']));

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('suppliers_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.png">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('suppliers_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query') {
    check_authz_json('suppliers_list');

    $result = suppliers_list();

    $smarty->assign('suppliers_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result(
        $smarty->fetch('suppliers_list.htm'),
        '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count'])
    );
}

/*------------------------------------------------------ */
//-- 列表页编辑名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_suppliers_name') {
    check_authz_json('suppliers_manage');

    $id     = intval($_POST['id']);
    $name   = json_str_iconv(trim($_POST['val']));

    /* 判断名称是否重复 */
    $sql = "SELECT suppliers_id
            FROM " . $ecs->table('suppliers') . "
            WHERE suppliers_name = '$name'
            AND suppliers_id <> '$id' ";
    if ($db->getOne($sql)) {
        make_json_error(sprintf($_LANG['suppliers_name_exist'], $name));
    } else {
        /* 保存供货商信息 */
        $sql = "UPDATE " . $ecs->table('suppliers') . "
                SET suppliers_name = '$name'
                WHERE suppliers_id = '$id'";
        if ($result = $db->query($sql)) {
            /* 记日志 */
            admin_log($name, 'edit', 'suppliers');

            clear_cache_files();

            make_json_result(stripslashes($name));
        } else {
            make_json_result(sprintf($_LANG['agency_edit_fail'], $name));
        }
    }
}

/*------------------------------------------------------ */
//-- 删除供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove') {
    check_authz_json('suppliers_manage');

    $id = intval($_REQUEST['id']);
    $sql = "SELECT *
            FROM " . $ecs->table('suppliers') . "
            WHERE suppliers_id = '$id'";
    $suppliers = $db->getRow($sql, TRUE);

    if ($suppliers['suppliers_id']) {
        /* 判断供货商是否存在订单 */
        $sql = "SELECT COUNT(*)
                FROM " . $ecs->table('order_info') . "AS O, " . $ecs->table('order_goods') . " AS OG, " . $ecs->table('goods') . " AS G
                WHERE O.order_id = OG.order_id
                AND OG.goods_id = G.goods_id
                AND G.suppliers_id = '$id'";
        $order_exists = $db->getOne($sql, TRUE);
        if ($order_exists > 0) {
            make_json_response('', 1, $_LANG['order_exists']);
            exit;
        }

        /* 判断供货商是否存在商品 */
        $sql = "SELECT COUNT(*)
                FROM " . $ecs->table('goods') . "AS G
                WHERE G.suppliers_id = '$id'";
        $goods_exists = $db->getOne($sql, TRUE);
        if ($goods_exists > 0) {
            make_json_response('', 1, $_LANG['goods_exists']);
            exit;
        }
        $sql = "UPDATE " . $ecs->table('suppliers') . " SET is_delete = 1 WHERE suppliers_id = '$id'";
        $db->query($sql);

        //        $sql = "DELETE FROM " . $ecs->table('suppliers') . "
        //            WHERE suppliers_id = '$id'";
        //        $db->query($sql);
        //
        //        /* 删除管理员、发货单关联、退货单关联和订单关联的供货商 */
        //        $table_array = array('admin_user', 'delivery_order', 'back_order');
        //        foreach ($table_array as $value)
        //        {
        //            $sql = "DELETE FROM " . $ecs->table($value) . " WHERE suppliers_id = '$id'";
        //            $db->query($sql, 'SILENT');
        //        }
        $sql = "SELECT suppliers_id, is_check
            FROM " . $ecs->table('suppliers') . "
            WHERE suppliers_id = '$id'";
        $suppliers = $db->getRow($sql, TRUE);

        if ($suppliers['suppliers_id']) {
            $_suppliers['is_check'] = 0;
            $db->autoExecute($ecs->table('suppliers'), $_suppliers, '', "suppliers_id = '$id'");
        }
        /* 记日志 */
        admin_log($suppliers['suppliers_name'], 'remove', 'suppliers');

        /* 清除缓存 */
        clear_cache_files();
    }

    $url = 'suppliers.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");

    exit;
}

/*------------------------------------------------------ */
//-- 修改供货商状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'is_check') {
    check_authz_json('suppliers_manage');

    $id = intval($_REQUEST['id']);
    $sql = "SELECT suppliers_id, is_check
            FROM " . $ecs->table('suppliers') . "
            WHERE suppliers_id = '$id'";
    $suppliers = $db->getRow($sql, TRUE);

    if ($suppliers['suppliers_id']) {
        $_suppliers['is_check'] = empty($suppliers['is_check']) ? 1 : 0;
        $db->autoExecute($ecs->table('suppliers'), $_suppliers, '', "suppliers_id = '$id'");
        clear_cache_files();
        make_json_result($_suppliers['is_check']);
    }

    exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch') {
    /* 取得要操作的记录编号 */
    if (empty($_POST['checkboxes'])) {
        sys_msg($_LANG['no_record_selected']);
    } else {
        /* 检查权限 */
        admin_priv('suppliers_manage');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['remove'])) {
            $sql = "SELECT *
                    FROM " . $ecs->table('suppliers') . "
                    WHERE suppliers_id " . db_create_in($ids);
            $suppliers = $db->getAll($sql);

            foreach ($suppliers as $key => $value) {
                /* 判断供货商是否存在订单 */
                $sql = "SELECT COUNT(*)
                        FROM " . $ecs->table('order_info') . "AS O, " . $ecs->table('order_goods') . " AS OG, " . $ecs->table('goods') . " AS G
                        WHERE O.order_id = OG.order_id
                        AND OG.goods_id = G.goods_id
                        AND G.suppliers_id = '" . $value['suppliers_id'] . "'";
                $order_exists = $db->getOne($sql, TRUE);
                if ($order_exists > 0) {
                    unset($suppliers[$key]);
                }

                /* 判断供货商是否存在商品 */
                $sql = "SELECT COUNT(*)
                        FROM " . $ecs->table('goods') . "AS G
                        WHERE G.suppliers_id = '" . $value['suppliers_id'] . "'";
                $goods_exists = $db->getOne($sql, TRUE);
                if ($goods_exists > 0) {
                    unset($suppliers[$key]);
                }

                $sql = "SELECT suppliers_id, is_check
                FROM " . $ecs->table('suppliers') . "
                WHERE suppliers_id ='" . $value['suppliers_id'] . "'";
                $supplier = $db->getRow($sql, TRUE);

                if ($supplier['suppliers_id']) {
                    $_supplier['is_check'] = 0;
                    $db->autoExecute($ecs->table('suppliers'), $_supplier, '', "suppliers_id = '" . $value['suppliers_id'] . "'");
                }
            }
            if (empty($suppliers)) {
                sys_msg($_LANG['batch_drop_no']);
            }

            $sql = "UPDATE " . $ecs->table('suppliers') . " SET is_delete = 1 WHERE suppliers_id " . db_create_in($ids);
            $db->query($sql);
            //            $sql = "DELETE FROM " . $ecs->table('suppliers') . "
            //                WHERE suppliers_id " . db_create_in($ids);
            //            $db->query($sql);
            //
            //            /* 更新管理员、发货单关联、退货单关联和订单关联的供货商 */
            //            $table_array = array('admin_user', 'delivery_order', 'back_order');
            //            foreach ($table_array as $value)
            //            {
            //                $sql = "DELETE FROM " . $ecs->table($value) . " WHERE suppliers_id " . db_create_in($ids) . " ";
            //                $db->query($sql, 'SILENT');
            //            }

            /* 记日志 */
            $suppliers_names = '';
            foreach ($suppliers as $value) {
                $suppliers_names .= $value['suppliers_name'] . '|';
            }
            admin_log($suppliers_names, 'remove', 'suppliers');

            /* 清除缓存 */
            clear_cache_files();
            $link[] = array('href' => 'suppliers.php?act=list&uselastfilter=1', 'text' => $_LANG['back_suppliers_list']);
            sys_msg($_LANG['batch_drop_ok'], 0, $link);
        }
    }
}

/*------------------------------------------------------ */
//-- 添加、编辑供货商
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('add', 'edit'))) {
    /* 检查权限 */
    admin_priv('suppliers_manage');

    if ($_REQUEST['act'] == 'add') {
        $suppliers = array();

        $suppliers['shop_brand_license_status'] = array('display: none;','display: none;','display: none;','display: none;','display: none;');

        /* 取得所有管理员，*/
        /* 标注哪些是该供货商的('this')，哪些是空闲的('free')，哪些是别的供货商的('other') */
        /* 排除是办事处的管理员 */
        //        $sql = "SELECT user_id, user_name, CASE
        //                WHEN suppliers_id = 0 THEN 'free'
        //                ELSE 'other' END AS type
        //                FROM " . $ecs->table('admin_user') . "
        //                WHERE agency_id = 0
        //                AND action_list <> 'all'";
        $sql = "SELECT user_id, user_name, CASE
                WHEN suppliers_id = 0 THEN 'free'
                ELSE 'other' END AS types
                FROM " . $ecs->table('admin_user') . "
                WHERE agency_id = 0
                AND suppliers_id = 0
                AND type = 1
                AND is_suppliers = 0
                ORDER BY add_time DESC ";
        $suppliers['admin_list'] = $db->getAll($sql);
        $sql = "SELECT user_id, user_name
                FROM " . $ecs->table('admin_user') . "
                WHERE agency_id = 0
                AND type = 0
                AND action_list <> 'all'
                ORDER BY add_time DESC";
        $suppliers['platform_list'] = $db->getAll($sql);
        $suppliers['typeList'] = shopTypeList();
        $suppliers['cooperate_type_list'] = getCooperateTypeList();
        $suppliers['huanbi_settlement_list']    = getHuanbiSettlementStatusList();
        $smarty->assign('ur_here', $_LANG['add_suppliers']);
        $smarty->assign('action_link', array('href' => 'suppliers.php?act=list', 'text' => $_LANG['suppliers_list']));
        $smarty->assign('form_action', 'insert');
        $smarty->assign('suppliers', $suppliers);

        assign_query_info();

        $smarty->display('suppliers_info.htm');
    } elseif ($_REQUEST['act'] == 'edit') {
        $suppliers = array();

        /* 取得供货商信息 */
        $id = $_REQUEST['id'];
        $sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '$id'";
        $suppliers = $db->getRow($sql);
        if (count($suppliers) <= 0) {
            sys_msg('suppliers does not exist');
        }
        $suppliers['shop_brand_license'] = explode(',' , $suppliers['shop_brand_license']);
        $suppliers['shop_brand_license_status'] = array();
        $suppliers['file_number'] = 0;
        for ($i=0; $i < 5; $i++) { 
            if (!empty($suppliers['shop_brand_license'][$i])) {
                $suppliers['file_number'] = $i+1;
                for ($j=0; $j < $i+1; $j++) { 
                    $suppliers['shop_brand_license_status'][$j] = '';
                }
            } else {
                $suppliers['shop_brand_license_status'][$i] = 'display: none;';
            }
        }
        if ($suppliers['file_number'] == 5) {
            $suppliers['add_file'] = 'display: none;';
        }
        $suppliers['service_time'] = json_decode($suppliers['service_time'], true);
        $suppliers['typeList']     = shopTypeList();
        $suppliers['cooperate_type_list']   = getCooperateTypeList();   // 合作类型
        $suppliers['huanbi_settlement_list']    = getHuanbiSettlementStatusList();

        /* 取得所有管理员，*/
        /* 标注哪些是该供货商的('this')，哪些是空闲的('free')，哪些是别的供货商的('other') */
        /* 排除是办事处的管理员 */
        //        $sql = "SELECT user_id, user_name, CASE
        //                WHEN suppliers_id = '$id' THEN 'this'
        //                WHEN suppliers_id = 0 THEN 'free'
        //                ELSE 'other' END AS type
        //                FROM " . $ecs->table('admin_user') . "
        //                WHERE agency_id = 0
        //                AND action_list <> 'all'";
        $sql = "SELECT user_id, user_name,CASE
                WHEN suppliers_id = '$id' THEN 'this'
                WHEN suppliers_id = 0 THEN 'free'
                ELSE 'other' END AS types
                FROM " . $ecs->table('admin_user') . "
                WHERE  suppliers_id in(0,'$id')
                AND agency_id = 0
                AND type = 1
                AND is_suppliers = 0
                ORDER BY add_time DESC";
        $suppliers['admin_list'] = $db->getAll($sql);
        $sql = "SELECT user_id, user_name,suppliers_id
                FROM " . $ecs->table('admin_user') . "
                WHERE agency_id = 0
                AND type = 0
                AND action_list <> 'all'
                ORDER BY add_time DESC";
        $platform_list = $db->getAll($sql);
        foreach ($platform_list as $k => $v) {
            $str = $v['suppliers_id'];
            $arr = explode(',', $str);
            if (in_array($id, $arr)) {
                $platform_list[$k]['types'] = 'this';
            }
        }
        $sql = "SELECT mobile_phone FROM " . $ecs->table('users') . " WHERE suppliers_id = '$id'";
        if($userInfo = $db->getRow($sql)){
            $suppliers['platform_user_phone'] = $userInfo['mobile_phone'];
        }
        $suppliers['platform_list'] = $platform_list;
        $smarty->assign('ur_here', $_LANG['edit_suppliers']);
        $smarty->assign('action_link', array('href' => 'suppliers.php?act=list&uselastfilter=1', 'text' => $_LANG['suppliers_list']));

        $smarty->assign('form_action', 'update');
        $smarty->assign('suppliers', $suppliers);

        assign_query_info();

        $smarty->display('suppliers_info.htm');
    }
} elseif (in_array($_REQUEST['act'], ['show'])) {
    /* 检查权限 */
    admin_priv('suppliers_shop_info');

    $suppliers = array();
    /* 取得供货商信息 */
    $id = $_SESSION['suppliers_id'];
    $sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '$id'";
    $suppliers = $db->getRow($sql);
    if (count($suppliers) <= 0) {
        sys_msg('suppliers does not exist');
    }
    $suppliers['service_time']      = json_decode($suppliers['service_time'], true);
    $suppliers['typeList'] = shopTypeList();

    $sql = "SELECT user_id, user_name,CASE
                WHEN suppliers_id = '$id' THEN 'this'
                WHEN suppliers_id = 0 THEN 'free'
                ELSE 'other' END AS types
                FROM " . $ecs->table('admin_user') . "
                WHERE  suppliers_id in(0,'$id')
                AND agency_id = 0
                AND type = 1
                AND is_suppliers = 0
                ORDER BY add_time DESC";
    $suppliers['admin_list'] = $db->getAll($sql);
    $sql = "SELECT user_id, user_name,suppliers_id
                FROM " . $ecs->table('admin_user') . "
                WHERE agency_id = 0
                AND type = 0
                AND action_list <> 'all'
                ORDER BY add_time DESC";
    $platform_list = $db->getAll($sql);
    foreach ($platform_list as $k => $v) {
        $str = $v['suppliers_id'];
        $arr = explode(',', $str);
        if (in_array($id, $arr)) {
            $platform_list[$k]['types'] = 'this';
        }
    }
    $sql = "SELECT mobile_phone FROM " . $ecs->table('users') . " WHERE suppliers_id = '$id'";
    if($userInfo = $db->getRow($sql)){
        $suppliers['platform_user_phone'] = $userInfo['mobile_phone'];
    }
    $suppliers['platform_list'] = $platform_list;
    $smarty->assign('suppliers', $suppliers);
    $smarty->assign('inputForbidden', '1');

    assign_query_info();

    $smarty->display('suppliers_info.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑供货商
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('insert', 'update'))) {
    /* 检查权限 */
    admin_priv('suppliers_manage');

    if (1 == $_POST['cooperate_type'] && 1 == $_POST['is_huanbi_settlement']) {
        $_POST['is_huanbi_settlement']  = 1;
    } else {
        $_POST['is_huanbi_settlement']  = 0;
    }

    $filePath              = uploadShopIcon($_POST['shop_icon'], 'shop_icon') ?: '';
    $shop_business_license = uploadShopIcon_new($_POST['shop_business_license'], 'shop_business_license') ?: '';
    $shop_brand_license    = array();
    foreach ($_POST['shop_brand_license'] as $key => $value) {
        $shop_brand_license[] = uploadShopIcon_new($value, 'shop_brand_license') ?: '';
    }

    $_POST = trimData($_POST);
    $now   = time();
    $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    $db->query('START TRANSACTION');//开启事务
    if ($_REQUEST['act'] == 'insert') {
        $service_time = array(
            'weekdays' => ['s' => $_POST['weekdays_s'], 'e' => $_POST['weekdays_e']],
            'holiday' => ['s' => $_POST['holiday_s'], 'e' => $_POST['holiday_e']],
        );

        $delivery_time = intval($_POST['delivery_time']);
        $delivery_arr = [24, 48, 72];
        if (!in_array($delivery_time, $delivery_arr)) {
            sys_msg($_LANG['warn_delivery_time']);
        }

        /* 提交值 */
        $suppliers = array(
            'type'                  => $_POST['type'], //供货商类型
            'main_business'         => $_POST['main_business'], //主营业务
            'shop_name'             => $_POST['shop_name'], //店铺名称
            'shop_icon'             => $filePath, //店铺图标
            'shop_business_license' => $shop_business_license, // 营业执照
            'shop_brand_license'    => implode(',' , $shop_brand_license), // 品牌及授权
            'personal_signature'    => $_POST['personal_signature'], //个性签名
            'shop_desc'             => $_POST['shop_desc'], //店铺描述
            'service_tel'           => $_POST['service_tel'], //服务电话
            'service_time'          => json_encode($service_time), //服务时间
            'receiver_address'      => $_POST['receiver_address'], //收货地址
            'receiver_name'         => $_POST['receiver_name'], //收货人
            'receiver_tel'          => $_POST['receiver_tel'], //收货电话
            'remark'                => $_POST['remark'], //备注
            'manager_name'          => $_POST['manager_name'], //负责人姓名
            'manager_tel'           => $_POST['manager_tel'], //负责人电话
            'suppliers_name'        => $_POST['suppliers_name'], //供应商名称
            'suppliers_desc'        => $_POST['suppliers_desc'], //供应商描述
            'parent_id'             => 0,
            'audit_status'          => isset($_POST['audit_status']) ?: 1,
            'success_time'          => $now,
            'update_time'           => $now,
            'addtime'               => $now,
            'service_qq'            => $_POST['service_qq'], //客服QQ
            'delivery_time'         => $delivery_time,      //商家承诺发货时间
            'cooperate_type'        => $_POST['cooperate_type'], // 商家合作类型
            'is_huanbi_settlement'  => $_POST['is_huanbi_settlement'], // 是否结算浣币
        );

        /* 判断名称是否重复 */
        $sql = "SELECT suppliers_id
                FROM " . $ecs->table('suppliers') . "
                WHERE suppliers_name = '" . $suppliers['suppliers_name'] . "' and is_delete = 0 and `type` != 5  ";
        if ($db->getOne($sql)) {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg($_LANG['suppliers_name_exist']);
        }

        /* 判断名称是否重复 */
        /* 2019/5/5 【需求】允许店铺名称重名 START
		$sql = "SELECT suppliers_id
                FROM " . $ecs->table('suppliers') . "
                WHERE shop_name = '" . $suppliers['shop_name'] .  "'";
		if ($db->getOne($sql))
		{
			sys_msg($_LANG['shop_name_exist']);
		}
        2019/5/5 【需求】允许店铺名称重名 END*/


        if(!$db->autoExecute($ecs->table('suppliers'), $suppliers, 'INSERT')){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('添加商户失败');
        }
        $suppliers['suppliers_id'] = $db->insert_id();

        if (isset($_POST['admins'])) {
            //            $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = '" . $suppliers['suppliers_id'] . "', action_list = '" . SUPPLIERS_ACTION_LIST . "' WHERE user_id " . db_create_in($_POST['admins']);
            $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = '" . $suppliers['suppliers_id'] . "' WHERE user_id =" . $_POST['admins'];
            if(!$db->query($sql)){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('管理管理员账号失败');
            }
        }
        //业务员绑定供应商（一或多）
        if (isset($_POST['platforms'])) {
            platforms_bound_suppliers($_POST['platforms'], $suppliers['suppliers_id']);
        }
        $sql = 'INSERT INTO ' . $ecs->table('suppliers_account') . ' (suppliers_id) ' .
            "VALUES('" . $suppliers['suppliers_id'] . "')";
        if(!$db->query($sql)){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('创建商家资金账户失败');
        }

        if(!empty($_POST['platform_user_phone'])){
            $sql = "SELECT * FROM " . $ecs->table('users') . " WHERE mobile_phone = {$_POST['platform_user_phone']} ";
            if(!$userInfo = $db->getRow($sql)){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('绑定换换用户失败，手机号码未注册');
            }
            if($userInfo['user_platform'] == 1){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('绑定换换用户失败，手机号码为爱投资用户');
            }
            if($userInfo['suppliers_id'] > 0){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('绑定换换用户失败，手机号码以被占用');
            }
            $sql = "UPDATE " . $ecs->table('users') . " SET suppliers_id = '" . $suppliers['suppliers_id'] . "' WHERE user_id = " . $userInfo['user_id'];
            if(!$db->query($sql)){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('换换用户关联商家账号失败');
            }
        }

        $db->query("COMMIT"); //提交事务

        /* 记日志 */
        admin_log($suppliers['suppliers_name'], 'add', 'suppliers');

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'suppliers.php?act=add',  'text' => $_LANG['continue_add_suppliers']),
            array('href' => 'suppliers.php?act=list', 'text' => $_LANG['back_suppliers_list'])
        );
        sys_msg($_LANG['add_suppliers_ok'], 0, $links);
    }

    if ($_REQUEST['act'] == 'update') {
        $service_time = array(
            'weekdays' => ['s' => $_POST['weekdays_s'], 'e' => $_POST['weekdays_e']],
            'holiday' => ['s' => $_POST['holiday_s'], 'e' => $_POST['holiday_e']],
        );

        $delivery_time = intval($_POST['delivery_time']);
        $delivery_arr = [24, 48, 72];
        if (!in_array($delivery_time, $delivery_arr)) {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg($_LANG['warn_delivery_time']);
        }

        /* 提交值 */
        $suppliers = array('id'   =>  $_POST['id']);

        /* 更新的 */
        $suppliers['new']  = array(
            'type'              => $_POST['type'], //供货商类型
            'cooperate_type'    => $_POST['cooperate_type'], // 商家合作类型
            'is_huanbi_settlement'  => $_POST['is_huanbi_settlement'], // 是否结算浣币
            'main_business'    => $_POST['main_business'], //主营业务
            'shop_name'        => $_POST['shop_name'], //店铺名称
            'personal_signature' => $_POST['personal_signature'], //个性签名
            'shop_desc'        => $_POST['shop_desc'], //店铺描述
            'service_tel'      => $_POST['service_tel'], //服务电话
            'service_time'     => json_encode($service_time), //服务时间
            'receiver_address' => $_POST['receiver_address'], //收货地址
            'receiver_name'    => $_POST['receiver_name'], //收货人
            'receiver_tel'     => $_POST['receiver_tel'], //收货电话
            'remark'           => $_POST['remark'], //备注
            'manager_name'     => $_POST['manager_name'], //负责人姓名
            'manager_tel'      => $_POST['manager_tel'], //负责人电话
            'suppliers_name'   => $_POST['suppliers_name'], //供应商名称
            'suppliers_desc'   => $_POST['suppliers_desc'], //供应商描述
            'update_time'      => $now,
            'service_qq'       => $_POST['service_qq'], //客服QQ
            'delivery_time'    => $delivery_time,
        );
        
        /* 取得供货商信息 */
        $sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '" . $suppliers['id'] . "'";
        $suppliers['old'] = $db->getRow($sql);
        if (empty($suppliers['old']['suppliers_id'])) {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('suppliers does not exist');
        }
        if ($filePath) {
            $suppliers['new']['shop_icon'] = $filePath;
            unlink('../'.$suppliers['old']['shop_icon']);
        }
        if ($shop_business_license) {
            $suppliers['new']['shop_business_license'] = $shop_business_license;
            unlink('../'.$suppliers['old']['shop_business_license']);
        }

        $suppliers['old']['shop_brand_license'] = explode(',' , $suppliers['old']['shop_brand_license']);
        for ($i=0; $i < 5; $i++) { 
            if (!empty($suppliers['old']['shop_brand_license'][$i])) {
                if (!empty($shop_brand_license[$i])) {
                    if ($suppliers['old']['shop_brand_license'][$i] != $shop_brand_license[$i]) {
                        $shop_brand_license_new[$i] = $shop_brand_license[$i];
                        unlink('../'.$suppliers['old']['shop_brand_license'][$i]);
                    } else {
                        $shop_brand_license_new[$i] = $suppliers['old']['shop_brand_license'][$i];
                    }
                } else {
                    if ($_POST['shop_brand_license_del'][$i] == 'OK') {
                        $shop_brand_license_new[$i] = '';
                        unlink('../'.$suppliers['old']['shop_brand_license'][$i]);
                    } else {
                        $shop_brand_license_new[$i] = $suppliers['old']['shop_brand_license'][$i];
                    }
                }
            } else {
                if (!empty($shop_brand_license[$i])) {
                    $shop_brand_license_new[$i] = $shop_brand_license[$i];
                } else {
                    $shop_brand_license_new[$i] = '';
                }
            }
        }
        $suppliers['new']['shop_brand_license'] = implode(',' , $shop_brand_license_new);

        /* 判断名称是否重复 */
        $sql = "SELECT suppliers_id
                FROM " . $ecs->table('suppliers') . "
                WHERE suppliers_name = '" . $suppliers['new']['suppliers_name'] . "'
                AND is_delete = 0 and `type` != 5  and suppliers_id <> '" . $suppliers['id'] . "'";
        if ($db->getOne($sql)) {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg($_LANG['suppliers_name_exist']);
        }

        /* 判断名称是否重复 */
        /* 2019/5/5 【需求】允许店铺名称重名 START
		$sql = "SELECT suppliers_id
                FROM " . $ecs->table('suppliers') . "
                WHERE shop_name = '".$suppliers['new']['shop_name']."'
                AND suppliers_id <> '" . $suppliers['id'] . "'";
		if ($db->getOne($sql))
		{
			sys_msg($_LANG['shop_name_exist']);
		}
        2019/5/5 【需求】允许店铺名称重名 END*/

        /* 保存供货商信息 */
        if(!$db->autoExecute($ecs->table('suppliers'), $suppliers['new'], 'UPDATE', "suppliers_id = '" . $suppliers['id'] . "'")){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('更新商户信息失败');
        }

        /* 同步负责人电话 */
        if (!empty($suppliers['new']['manager_tel'])){
            $sql = "SELECT * FROM ". $ecs->table('admin_user') . " WHERE suppliers_id = '" . $suppliers['id'] . "' AND type = 1";
            $admin_user = $db->getRow($sql);
            if(!empty($admin_user) && $admin_user['phone'] != $suppliers['new']['manager_tel']){
                $sql = "UPDATE ". $ecs->table('admin_user') ." SET phone = '" . $suppliers['new']['manager_tel'] . "' WHERE suppliers_id = '" .$suppliers['id']. "' AND type = 1";
                $admin_res = $db->query($sql);
                if(!$admin_res){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('负责人电话同步失败');
                }
            }
        }

        /* 清空供货商的管理员 */
        $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = 0 WHERE suppliers_id = '" . $suppliers['id'] . "' AND type = 1 AND is_suppliers = 0";
        if(!$db->query($sql)){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('清空供货商的管理员失败');
        }
        if (isset($_POST['admins'])) {
            /* 添加供货商的管理员 */
            $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = '" . $suppliers['old']['suppliers_id'] . "' WHERE user_id =" . $_POST['admins'];
            if(!$db->query($sql)){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('添加供货商的管理员失败');
            }
        }
        /* 清空平台业务员 */
        $plat = $db->getAll("SELECT user_id, suppliers_id FROM " . $ecs->table('admin_user') . " WHERE agency_id = 0 AND type = 0");
        $unset = '';
        foreach ($plat as $key => $val) {
            $str = $val['suppliers_id'];
            $arr = explode(',', $str);
            if (in_array($suppliers['id'], $arr)) {
                $new_arr = array_diff($arr, [$suppliers['id']]);
                $new_str = implode(',', $new_arr);
                $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = '" . $new_str . "' WHERE user_id =" . $val['user_id'];
                if(!$db->query($sql)){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('更新管理员关联商户失败');
                }
            }
        }
        if (isset($_POST['platforms'])) {
            platforms_bound_suppliers($_POST['platforms'], $suppliers['id']);
        }

        if(!empty($_POST['platform_user_phone'])){
            $sql = "SELECT * FROM " . $ecs->table('users') . " WHERE mobile_phone = {$_POST['platform_user_phone']} ";
            if(!$userInfo = $db->getRow($sql)){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('绑定换换用户失败，手机号码未注册');
            }
            if($userInfo['user_platform'] == 1){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('绑定换换用户失败，手机号码为爱投资用户');
            }
            if($userInfo['suppliers_id'] > 0 &&  $userInfo['suppliers_id'] != $suppliers['id']){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('绑定换换用户失败，手机号码以被占用');
            }
            $sql = "UPDATE " . $ecs->table('users') . " SET suppliers_id = '" . $suppliers['id'] . "' WHERE user_id = " . $userInfo['user_id'];
            if(!$db->query($sql)){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('换换用户关联商家账号失败');
            }
        }

        $db->query("COMMIT"); //提交事务

        /* 记日志 */
        admin_log($suppliers['old']['suppliers_name'], 'edit', 'suppliers');

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links[] = array('href' => 'suppliers.php?act=list', 'text' => $_LANG['back_suppliers_list']);
        sys_msg($_LANG['edit_suppliers_ok'], 0, $links);
    }
} elseif ($_REQUEST['act'] == 'account') {
    $smarty->display('suppliers_account_info.htm');
} elseif ($_REQUEST['act'] == 'accountList') {
    if($_SESSION['admin_type'] == 1){
       $smarty->display('suppliers_account_info.htm');exit;
    }
    $smarty->display('suppliers_account_list.htm');
} elseif ($_REQUEST['act'] == 'apiRelation') {
    check_authz_json('suppliers_manage');
    $suppliers_id       = $_POST['suppliers_id'];
    $admin_user_id      = $_POST['admin_user_id'];
    if (empty($suppliers_id)) {
        make_json_error($_LANG['suppliers_not_exist']);
    }
    if (empty($admin_user_id)) {
        make_json_error($_LANG['admin_user_exist']);
    }
    /* 取得供货商信息 */
    $sql = "SELECT suppliers_id FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = " . $suppliers_id;
    $suppliers = $db->getRow($sql);
    if (!$suppliers) {
        make_json_error($_LANG['suppliers_not_exist'] . ':' . $suppliers_id);
    }
    /* 取得管理员信息 */
    $sql = "SELECT user_id FROM " . $ecs->table('admin_user') . " WHERE user_id = " . $admin_user_id;
    $suppliers = $db->getRow($sql);
    if (!$suppliers) {
        make_json_error($_LANG['admin_user_exist'] . ':' . $admin_user_id);
    }
    /* 剔除商家其他管理员 */
    $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = 0 WHERE suppliers_id = " . $suppliers_id;
    $res = $db->query($sql);
    /* 关联供货商管理员 */
    $sql = "UPDATE " . $ecs->table('admin_user') . " SET suppliers_id = '" . $suppliers_id . "' WHERE user_id =" . $admin_user_id;
    $res = $db->query($sql);
    make_json_result('', 'success');
} elseif ($_REQUEST['act'] == 'multiStatementList') {
    admin_priv('multi_statement_list');
    $smarty->display('suppliers_multi_statement_list.htm');
}  elseif ($_REQUEST['act'] == 'shippingTplList') {
    $smarty->display('SuppliersShippingTemplates.htm');
} elseif ($_REQUEST['act'] == 'suppliersSurplusTransfer') {
    admin_priv('suppliers_surplus_transfer');
    $smarty->display('suppliers_surplus_transfer.htm');
}

elseif ($_REQUEST['act'] == 'paymentList') {      // 结算列表
    admin_priv('suppliers_payment');
    $smarty->display('paymentList.htm');

}

elseif ($_REQUEST['act'] == 'paymentPayCert') {   // 结算凭证详情
    admin_priv('suppliers_payment');
    $smarty->display('paymentPayCert.htm');

}

elseif ($_REQUEST['act'] == 'clientCodeList'){ // 电子面单客户号  2019-07-02 wanghai
    /* 检查权限 */
    admin_priv('client_code_list');
    $smarty->assign('suppliersId',$_SESSION['suppliers_id']);
    $smarty->display('client_code_list.htm');
}

elseif ($_REQUEST['act'] == 'businessAddress') {  //  商家地址信息接口 2019-07-03 wanghai
    admin_priv('business_address');
    $smarty->assign('suppliersId',$_SESSION['suppliers_id']);
    $smarty->display('business_address.htm');
}

elseif ($_REQUEST['act'] == 'suppliersDashboard') {  //  商家数据看版
    admin_priv('suppliers_dashboard');
    $smarty->assign('suppliersId',$_SESSION['suppliers_id']);
    $smarty->display('suppliers_dashboard.htm');
}

elseif ($_REQUEST['act'] == 'update_business_address') { // 绑定或修改商家地址信息接口
    if($_REQUEST){
        $req = [];
        if(empty($_REQUEST['id'])){
            $req = [
                'suppliersId'=>$_REQUEST['suppliersId'],
                'province'=>$_REQUEST['province'],
                'city'=>$_REQUEST['city'],
                'area'=>$_REQUEST['area'],
                'address'=>$_REQUEST['address'],
            ];
        }else{
            $req = [
                'id'=>$_REQUEST['id'],
                'suppliersId'=>$_REQUEST['suppliersId'],
                'province'=>$_REQUEST['province'],
                'city'=>$_REQUEST['city'],
                'area'=>$_REQUEST['area'],
                'address'=>$_REQUEST['address'],
            ];
        }
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($req,true),'info',$_SESSION['admin_name'],'update_business_address');
        $url = EXPRESS_INFO_URL.'/eorder/customerNumber/address';
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($url,true),'info',$_SESSION['admin_name'],'url');
        $res = curlData($url,json_encode($req),'POST');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'res');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

elseif ($_REQUEST['act'] == 'printer') {  //  打印机管理 2019-07-03 wanghai
    /* 检查权限 */
    admin_priv('printer_list');

    $smarty->assign('suppliersId',$_SESSION['suppliers_id']);
    $smarty->display('printer_list.htm');

}

/**
 *  获取供应商列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function suppliers_list()
{
    $result = get_filter();
    if ($result === false) {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'suppliers_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 AND is_delete = 0';
        if($_SESSION['suppliers_id'] != 0 &&  !in_array($_SESSION['admin_id'],getSuperAdmin())){
             $where .= " AND suppliers_id in(" . $_SESSION['suppliers_id'] . ")";
        }
        if (!empty($_REQUEST['suppliers_name'])) {
            $where .= " AND suppliers_name like '%" . $_REQUEST['suppliers_name'] . "%'";
        }
        if (!empty($_REQUEST['shop_name'])) {
            $where .= " AND shop_name like '%" . $_REQUEST['shop_name'] . "%'";
        }
        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0) {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        } else {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('suppliers') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT *
                FROM " . $GLOBALS['ecs']->table("suppliers") . "
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] . "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    } else {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);
    $sql = "SELECT user_id,user_name,suppliers_id FROM " . $GLOBALS['ecs']->table('admin_user') . "WHERE  type = 0";
    $arr = $GLOBALS['db']->getAll($sql);
    foreach ($arr as $k => $v) {
        $str = $v['suppliers_id'];
        $array = explode(',', $str);
        $arr[$k]['suppliers_id'] = $array;
    }

    foreach ($row as $k => $v) {
        $row[$k]['id'] = $k + 1;
        $length = str_len($v['suppliers_desc']);
        if ($length > 20) {
            $row[$k]['suppliers_desc'] = sub_str($v['suppliers_desc'], 18);
        }
        $suppliers_id = $v['suppliers_id'];
        $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('admin_user') . "WHERE suppliers_id = '$suppliers_id' AND type = 1 AND is_suppliers = 0";
        $name = $GLOBALS['db']->getOne($sql);
        $admin_arr = ['admin_name' => $name];
        $row[$k] = arrayInsert($row[$k], 3, $admin_arr);
        $plarform_name = '';
        foreach ($arr as $key => $val) {
            if (in_array($suppliers_id, $val['suppliers_id'])) {
                $plarform_name .= $plarform_name ? "、" . $val['user_name'] : $val['user_name'];
            }
        }
        $plarform_arr = ['plarform_name' => $plarform_name];
        $row[$k] = arrayInsert($row[$k], 4, $plarform_arr);
    }

    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 *  平台业务员绑定供货商
 *
 * @access  public
 * @param   array  $arr 平台业务员
 * @param   string $id 供应商id
 *
 */
function platforms_bound_suppliers($arr, $id)
{
    foreach ($arr as $v) {
        $suppliers_id = $GLOBALS['db']->getOne("SELECT suppliers_id from " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id =" . $v);
        if ($suppliers_id != 0) {
            $arr = explode(',', $suppliers_id);
            array_push($arr, $id);
            sort($arr);
            $suppliers_id = implode(',', $arr);
        } else {
            $suppliers_id = $id;
        }
        $sql = "UPDATE " . $GLOBALS['ecs']->table('admin_user') . " SET suppliers_id = '" . $suppliers_id . "' WHERE user_id =" . $v;
        $GLOBALS['db']->query($sql);
    }
}

function arrayInsert($array, $position, $insertArray)
{
    $ret = [];

    if ($position == count($array)) {
        $ret = $array + $insertArray;
    } else {
        $i = 0;
        foreach ($array as $key => $value) {
            if ($position == $i++) {
                $ret += $insertArray;
            }

            $ret[$key] = $value;
        }
    }

    return $ret;
}

/**
 * 上传商家图标
 * @param $base64_image_content
 * @param $path
 * @return bool|string
 */
function uploadShopIcon($base64_image_content, $path)
{
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
        $type = $result[2];
        $date = date('Ym');
        $basePutUrl = ROOT_PATH . IMAGE_DIR . '/' . $path . "/" . $date . '/';
        if (!file_exists($basePutUrl)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($basePutUrl, 0777, true);
        }
        $ping_url = rand(10000, 99999) . time() . ".{$type}";
        $ftp_image_upload_url = IMAGE_DIR . '/' . $path . '/' . $date . '/' . $ping_url;
        $local_file_url = $basePutUrl . $ping_url;
        if (file_put_contents($local_file_url, base64_decode(str_replace($result[1], '', $base64_image_content)))) {

            return $ftp_image_upload_url;
        } else {
            return false;
        }
    } else {
        return false;
    }
}


function uploadShopIcon_new($base64_image_content, $path)
{
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
        $type = $result[2];
        $date = date('Ym');
        $basePutUrl = ROOT_PATH . IMAGE_DIR . '/' . $path . "/" . $date . '/';
        if (!file_exists($basePutUrl)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($basePutUrl, 0777, true);
        }
        $ping_url = rand(10000, 99999) . time() . ".{$type}";
        $ftp_image_upload_url = IMAGE_DIR . '/' . $path . '/' . $date . '/' . $ping_url;
        $local_file_url = $basePutUrl . $ping_url;
        if (file_put_contents($local_file_url, base64_decode(str_replace($result[1], '', $base64_image_content)))) {

            $bigImgPath = $local_file_url; // 原图
            $logo       = "../images/shuiyin.png"; // 水印
            $im         = imagecreatefromstring(file_get_contents($bigImgPath));
            $watermark  = imagecreatefromstring(file_get_contents($logo)); // 获取水印源
            list($bgWidth, $bgHight, $bgType) = getimagesize($bigImgPath); // 获取原图宽、高、类型
            list($logoWidth, $logoHight, $logoType) = getimagesize($logo); // 获取水印宽、高、类型
            // 定义平铺数据
            $x_length = $bgWidth - 10; // x轴总长度
            $y_length = $bgHight - 10; // y轴总长度
            // 创建透明画布 伪白色
            $w     = imagesx($watermark);
            $h     = imagesy($watermark);
            $cut   = imagecreatetruecolor($w,$h);
            $white = imagecolorallocatealpha($cut, 255,255,255,0);
            imagefill($cut, 0, 0, $white);
            imagealphablending($cut, false); // 关闭混色模式 png自带透明
            // 整合水印
            imagecopy($cut, $watermark, 0, 0, 0, 0, $w, $h);
            // 循环平铺水印
            for ($x = 0; $x < $x_length; $x++)
            {
                for ($y = 0; $y < $y_length; $y++) {
                    imagecopy($im, $cut, $x, $y, 0, 0, $logoWidth, $logoHight);
                    $y += $logoHight;
                }
                $x += $logoWidth;
            }
            imagejpeg($im, $local_file_url); // 保存
            imagedestroy($im); // 释放内存
            imagedestroy($watermark); // 释放内存

            return $ftp_image_upload_url;
        } else {
            return false;
        }
    } else {
        return false;
    }
}


/**
 * 合作类型
 * @Author   haofuheng
 * @DateTime 2019-08-28T16:29:11+0800
 * @return   [type]                   [description]
 */
function getCooperateTypeList() {
    return array(
        '1'  => '入驻商',
        '2'  => '供应商'
    );
}


/**
 * 是否结算浣币
 * @Author   haofuheng
 * @DateTime 2019-11-04T17:35:40+0800
 * @return   [type]                   [description]
 */
function getHuanbiSettlementStatusList() {
    return array(
        '0'  => '不结算',
        '1'  => '结算'
    );
}


/**
 * @param $url
 * @param $data
 * @param bool $method  :post请求  :get请求
 * @return bool|string
 * author:wanghai
 */
function curlData($url,$data,$method = 'GET')
{
    //初始化
    $ch = curl_init();
    $headers = ['Content-Type: application/json'];
    if($method == 'GET'){
        if($data){
            $querystring = http_build_query($data);
            $url = $url.'?'.querystring;
        }
    }
    // 请求头，可以传数组
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);         // 执行后不直接打印出来
    if($method == 'POST'){
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');     // 请求方式
        curl_setopt($ch, CURLOPT_POST, true);               // post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);              // post的变量
    }

    if($method == 'PUT'){
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }

    if($method == 'DELETE'){
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    $output = curl_exec($ch); //执行并获取HTML文档内容
    curl_close($ch); //释放curl句柄
    return $output;
}
