<?php
/**
 * ECSHOP 订单管理
 * ============================================================================
 * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: order.php 17219 2011-01-27 10:49:19Z yehuaixiao $
 */
header("Access-Control-Allow-Origin:*");
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with, content-type');
define('IN_ECS', true);
error_reporting(0);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_activity.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_goods.php');
require_once(ROOT_PATH . 'includes/cls_matrix.php');
include_once(ROOT_PATH . 'includes/cls_certificate.php');
include_once(ROOT_PATH . 'includes/Classes/PHPExcel.php');
include_once(ROOT_PATH . 'includes/Classes/PHPExcel/IOFactory.php');
require('leancloud_push.php');
$platform_user_id = PLATFORM_UID;
$mlmOrderTimeLimit = 7;
/*------------------------------------------------------ */
//-- 用户登录权限重新获取保存
/*------------------------------------------------------ */
updateUserSessionInfo($_SESSION['admin_id']);

/*------------------------------------------------------ */
//-- 订单查询
/*------------------------------------------------------ */
addLog('order.php:action:'.$_REQUEST['act']." params :".print_r($_REQUEST,true) .' admin_id:'.$_SESSION['admin_id'],'info',$_SESSION['admin_name']);


// 商家合作类型
$suppliers_cooperate_type   = 0;
if (!empty($_SESSION['suppliers_info']['cooperate_type'])) {
    $suppliers_cooperate_type   = $_SESSION['suppliers_info']['cooperate_type'];
}
$smarty->assign('suppliers_cooperate_type', $suppliers_cooperate_type);


if ($_REQUEST['act'] == 'order_query')
{
    $suppliers_id = $_SESSION['suppliers_id'];
    /* 检查权限 */
    admin_priv('order_query');

    /* 载入配送方式 */
    $smarty->assign('shipping_list', shipping_list());

    /* 载入支付方式 */
    $smarty->assign('pay_list', payment_list());

    /* 载入国家 */
    $smarty->assign('country_list', get_regions());

    /* 载入订单状态、付款状态、发货状态 */
    $smarty->assign('os_list', get_status_list('order'));
    $smarty->assign('ps_list', get_status_list('payment'));
    $smarty->assign('ss_list', get_status_list('shipping'));

    /* 载入供货商 */
    $smarty->assign('suppliers_list_info', suppliers_list_info($suppliers_id ? "suppliers_id in ({$suppliers_id})" : ''));
    $smarty->assign('admin_type', $_SESSION['admin_type']);

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['03_order_query']);
    $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_query.htm');
}

/**
 *  生成电子面单(筛选条件页面)  2019-07-02   wanghai
 **/
elseif ($_REQUEST['act'] == 'addForm'){
    $smarty->assign('suppliersId',$_SESSION['suppliers_id']);
    $smarty->assign('admin_type',$_SESSION['admin_type']);
    $smarty->display('sheet_order.htm');
}

/**
 *  取消电子面单  2019-07-02   wanghai
 **/
elseif ($_REQUEST['act'] == 'cancel_electronic_order'){
    if(isset($_GET['order_id'])){
        $order_id = $_GET['order_id'];
        $sql = "SELECT order_sn,electronic_order_flag,shipping_status,invoice_no,shipping_id,order_status,pay_status FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id'";
        $order = $db->getRow($sql);
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($order,true),'info',$_SESSION['admin_name'],'order_info');
        if('1' == $order['electronic_order_flag'] && '1' == $order['shipping_status']){
            $shipperId = $order['shipping_id'];
            $sqlShipping = "SELECT shipping_code FROM " . $ecs->table('shipping') . " WHERE shipping_id = '$shipperId'";
            $sp = $db->getRow($sqlShipping);
            if(!empty($sp['shipping_code']) && !empty($order['order_sn']) && !empty($order['invoice_no'])){
                $data = [
                    'ShipperCode'=>$sp['shipping_code'],
                    'OrderCode'=>$order['order_sn'],
                    'LogisticCode'=>$order['invoice_no'],
                ];
                $url = EXPRESS_INFO_URL.'/eorder/cancel';
                $res = curlData($url,json_encode($data),'POST');
                $res_data = json_decode($res,true);
                addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'eorder_cancel');
                if($res_data['code'] ==1){
                    $sql = "UPDATE " . $ecs->table('order_info') . " SET shipping_status = 0,shipping_time = 0,electronic_order_flag = 0 WHERE order_id = " . $order_id;
                    $db->query($sql);

                    $sqlAction = 'INSERT INTO '.$ecs->table('order_action').' (order_id, action_user, order_status, shipping_status, pay_status,action_place,action_note,log_time) VALUES ("'.$order_id.'","'.$_SESSION['admin_name'].'", "'.$order['order_status'].'", "0", "'.$order['pay_status'].'",1,"电子面单","'.time().'")';
                    $db->query($sqlAction);
                    echo "<script>alert('取消电子面单成功');history.back();</script>";
                }else{
                    echo "<script>alert('{$res_data['msg']}');history.back();</script>";
                }
            }else{
                echo "<script>alert('订单参数错误');history.back();</script>";
            }
        }else{
            echo "<script>alert('订单状态不符合');history.back();</script>";
        }
    }else{
        echo "<script>alert('订单ID错误');history.back();</script>";
    }
}

/**
 *  查询物流轨迹  2019-07-02   wanghai
 **/
elseif ($_REQUEST['act'] == 'query_logistics_info'){
    $order_sn = $_GET['order_sn'];
    $sql = "SELECT invoice_no,shipping_id FROM " . $ecs->table('order_info') . " WHERE order_sn = '$order_sn'";
    $order = $db->getRow($sql);
    addLog('action :'.$_REQUEST['act'].' data:'.print_r($order,true),'info',$_SESSION['admin_name'],'query_logistics_info');
    if(!empty($order['shipping_id'])){
        $shipperId = $order['shipping_id'];
        $sqlShipping = 'SELECT * from '.$ecs->table('shipping').' WHERE shipping_id = "'.$shipperId.'"';
        $sp = $db->getRow($sqlShipping);
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($sp,true),'info',$_SESSION['admin_name'],'shipping');
    }
    $logistic_code = json_encode($order['invoice_no']);
    $shipper_code = json_encode($sp['shipping_code']);
    $smarty->assign('logistic_code',$logistic_code);
    $smarty->assign('shipper_code',$shipper_code);
    $smarty->display('logistics_trajectory.htm');
}


/**
 *  生成电子面单  2019-07-02   wanghai
 **/
elseif ($_REQUEST['act'] == 'add_electron_bill') {
    //var_dump($_SESSION);
    $suppliers_id = $_SESSION['suppliers_id'];
    $type = $_SESSION['admin_type'];
    $data = $_REQUEST;
    addLog('action :'.$_REQUEST['act'].' data:'.print_r($data,true),'info',$_SESSION['admin_name'],'add_electron_bill');
    $receive = [
        'customArea' => $data['customArea'],
        'transType' => $data['transType'],
        'shipperCode' => $data['shipperCode'] ? $data['shipperCode'] : '',
        'payType' => $data['payType'],
        'expressTypeId' => $data['expressTypeId'],
        //'isReturnSignBill' => $data['isReturnSignBill'],
        //'operateRequire' => $data['operateRequire'],
        'isNotice' => $data['isNotice'],
        'isSendMessage' => $data['isSendMessage'],
        'isReturnPrintTemplate' => $data['isReturnPrintTemplate'],
        'templateSize' => $data['templateSize'],
        'packingType' => $data['packingType'],
        'deliveryMethod' => $data['deliveryMethod'],
        'suppliersEorderInfoId' => isset($data['suppliersEorderInfoId']) ? isset($data['suppliersEorderInfoId']) : '',
    ];
    $orderId = array();
    if($data['temp']){  // 订单编号
        $orderId = explode(',',$data['temp']);
        $combine = array();
        $order_yc = array();
        // 供应商信息
        $sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '$suppliers_id'";
        $resSuppliers = $db->getRow($sql);
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($resSuppliers,true),'info',$_SESSION['admin_name'],'suppliers');
        foreach ($orderId as $key=>$value){
            // 订单基础信息
            $sql = "SELECT * FROM " . $ecs->table('order_info') . " WHERE order_sn = '$value'";
            $res = $db->getRow($sql);
            addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'order_info');
            if($res) {
                // 仅处理 未发货 + 已付款 状态的订单
                if((0 == $res['shipping_status'] || $res['shipping_status'] == 5) && 2 == $res['pay_status']){
                    // 订单商品信息
                    $sqlGoods = "SELECT * FROM " . $ecs->table('order_goods') . " WHERE order_id = '$value'";
                    $resGoods = $db->getRow($sqlGoods);

                    $province = $res['province'];
                    $city = $res['city'];
                    $district = $res['district'];
                    $address = $res['address'];
                    // 获取收件人省、市、区、地址
                    $sqlProvince = "SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$province'";
                    $receiverProvince = $db->getOne($sqlProvince);
                    $sqlCity = "SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$city'";
                    $receiverCity = $db->getOne($sqlCity);
                    $sql = "SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$district'";
                    $receiverDistrict = $db->getOne($sql);

                    $commodity = [
                        'goodsName' => isset($resGoods['goods_name']) ? isset($resGoods['goods_name']) : '测试' ,
                        'goodsCode' => '',
                        'goodsquantity' => '',
                        'goodsPrice' => '',
                        'goodsWeight' => '',
                        'goodsDesc' => '',
                        'goodsVol' => ''
                    ];
                    $combine = [
                        'suppliersId' => $suppliers_id,
                        'suppliersEorderInfoId' => $data['suppliersEorderInfoId'],
                        'orderCode' => $value,
                        'cost' => $res['shipping_fee'],
                        'receiverName' => $res['consignee'],
                        'receiverTel' => $res['tel'],
                        'receiverMobile' => $res['mobile'] ? $res['mobile'] : '13522222222',
                        'receiverPostCode' => '',
                        'receiverProvinceName' => $receiverProvince,
                        'receiverCityName' => $receiverCity,
                        'receiverExpAreaName' => $receiverDistrict,
                        'receiverAddress' => $address,
                        'senderCompany' => '',
                        'senderName' => $resSuppliers['shop_name'],
                        'senderTel' => $resSuppliers['service_tel'],
                        'senderMobile' => $resSuppliers['receiver_tel'] ? $resSuppliers['receiver_tel'] : '',
                        'senderPostCode' => '',
                        'senderProvinceName' => $data['province'],
                        'senderCityName' => $data['city'],
                        'senderExpAreaName' => $data['area'],
                        'senderAddress' => $data['address'],
                        'weight' => '',
                        'quantity' => 1,
                        'volume' => '',
                        'remark' => $res['postscript'],
                        'commodity' => $commodity,
                        'receiverPostCode' =>'100000',
                        'senderPostCode' =>'100000'
                    ];

                    $merge = array_merge($receive, $combine);
                    addLog('action :'.$_REQUEST['act'].' data:'.print_r($merge,true),'info',$_SESSION['admin_name'],'merge');
                    // 生成电子面单
                    $url = EXPRESS_INFO_URL . '/eorder';
                    $ret = curlData($url, json_encode($merge), 'POST');
                    addLog('action :'.$_REQUEST['act'].' data:'.print_r($ret,true),'info',$_SESSION['admin_name'],'eorder');
                    // 修改itz_order_info表 shipping_status为已发货
                    $res_data = json_decode($ret,true);
                    if(1 == $res_data['code']){
                        if ($res_data['data'] != NULL) {
                            // 快递单号
                            $logisticCode =  $res_data['data']['logisticCode'];
                            // 快递公司编码
                            $shipperCode = $data['shipperCode'];
                            // 订单编号
                            $sn = $res_data['data']['orderSn'];
                            // 快递公司名称
                            $expressName = $res_data['data']['expressName'];
                            // 获取配送方式id   itz_shipping
                            $sqlShip = 'SELECT shipping_id from '.$GLOBALS['ecs']->table('shipping').' WHERE shipping_code = "'.$shipperCode.'"';
                            $ship = $GLOBALS['db']->getRow($sqlShip);
                            $shipperId = $ship['shipping_id'] ? $ship['shipping_id'] : '0';

                            $sqlInfo = 'UPDATE '.$GLOBALS['ecs']->table('order_info').
                                ' SET  electronic_order_flag = 1,shipping_status = 1,invoice_no = "'.$logisticCode.'",shipping_id = "'.$shipperId.'",shipping_time = "'.time().'",shipping_name = "'.$expressName.'" WHERE order_sn = "'.$sn.'"';
                            $db->query($sqlInfo);
                            // 订单id
                            $sqlOrderId = "SELECT  order_id  from " . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '". $res_data['data']['orderSn']."'";
                            $orders = $GLOBALS['db']->getRow($sqlOrderId);
                            $order_id = $orders['order_id'];
                            /* 订单操作表记录 */
                            $log_time = time();
                            $admin_name = $_SESSION['admin_name'];
                            $sqlAction = 'INSERT INTO '.$ecs->table('order_action').' (order_id, action_user, order_status, shipping_status, pay_status,action_place,action_note,log_time) VALUES ("'.$order_id.'","'.$admin_name.'", "'.$res[order_status].'", "1", "'.$res[pay_status].'",1,"电子面单","'.$log_time.'")';
                            $db->query($sqlAction);
                        } else {
                            $order_yc[] = $value;
                        }
                    } else {
                        $order_yc[] = $value;
                    }
                }else{
                    // 不符合的订单号数组
                    $res_data['msg'] = '仅支持已付款并且未发货订单';
                    $order_yc[] = $value;
                }
            }
        }
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($order_yc,true),'info',$_SESSION['admin_name'],'order_yc');
        if(count($order_yc)>0){
            if (count($orderId) > 1) {
                $str = implode(',',array_unique($order_yc));
                addLog('action :'.$_REQUEST['act'].' data:'.print_r($str,true),'info',$_SESSION['admin_name'],'order_yc');
                $str_data=['code'=>0, 'data'=>$str,'msg'=>'不符合的订单号：'];
                echo json_encode($str_data);
            } else if (count($orderId) == 1) {
                addLog('action :'.$_REQUEST['act'].' data:'.$res_data['msg'],'info',$_SESSION['admin_name'],'order_yc');
                $str_data=['code'=>0, 'data'=>$res_data['msg'],'msg'=>'订单错误：'];
                echo json_encode($str_data);
            }
        }else{
            $s_data=['code'=>1, 'data'=>'','msg'=>'生成电子面单成功'];
            echo json_encode($s_data);
        }
    }else{
        echo "<script>alert('没有选择订单号');history.back();</script>";
    }

}

/**
 * 打印电子面单
 */
elseif ($_REQUEST['act'] == 'printer') {
    //var_dump($_SESSION);
    $smarty->assign('suppliersId',$_SESSION['suppliers_id']);
    $smarty->display('stamp.htm');
}

/*------------------------------------------------------ */
//-- 订单列表
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('order_list');

	$smarty->assign('act_type_list', getActivityType());

	/* 载入配送方式 */
    $smarty->assign('shipping_list', shipping_list());

    /* 载入支付方式 */
    $smarty->assign('pay_list', payment_list());

    /* 载入国家 */
    $smarty->assign('country_list', get_regions());

    /* 载入订单状态、付款状态、发货状态 */
    $smarty->assign('os_list', get_status_list('order'));
    $smarty->assign('ps_list', get_status_list('payment'));
    $smarty->assign('ss_list', get_status_list('shipping'));

    $admin_suppliers_id = $_SESSION['suppliers_id'];
    /* 载入供货商 */
    $smarty->assign('suppliers_list_info', suppliers_list_info($admin_suppliers_id ? "suppliers_id in ({$admin_suppliers_id})" : ''));
    $smarty->assign('admin_type', $_SESSION['admin_type']);
    $smarty->assign('admin_id', $_SESSION['admin_id']);
    $smarty->assign('suppliers_id', $_SESSION['suppliers_id']);
    $smarty->assign('suppliersId', $_SESSION['suppliers_id']);
    /* 模板赋值 */
    // $smarty->assign('ur_here', $_LANG['03_order_query']);
//     $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

    $cert = new certificate();
    $cert->is_bind_sn('ecos.ome')?$smarty->assign('is_bind_erp',true):$smarty->assign('is_bind_erp',false);
    $cert->is_bind_sn('taodali')?$smarty->assign('is_bind_taoda',true):$smarty->assign('is_bind_taoda',false);

    /* 模板赋值 */
    // $smarty->assign('ur_here', $_LANG['02_order_list']);
    // $smarty->assign('action_link', array('href' => 'order.php?act=order_query', 'text' => $_LANG['03_order_query']));

    $smarty->assign('status_list', $_LANG['cs']);   // 订单状态

    $smarty->assign('os_unconfirmed',   OS_UNCONFIRMED);
    $smarty->assign('cs_await_pay',     CS_AWAIT_PAY);
    $smarty->assign('cs_await_ship',    CS_AWAIT_SHIP);
    $smarty->assign('full_page',        1);

    $order_list = order_list();
    $smarty->assign('where',   $order_list['where']);
    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $smarty->assign('goods_num',   $order_list['goods_num']);
    $smarty->assign('sort_order_time', '<img src="images/sort_desc.png">');

    $panel_flag = 0;
    $erp_icon_html = "";
    $erpstr = array();
    $is_super_admin = is_super_admin();
    $panel_display = "none";
    if( $order_list['record_count']>=50 ){
        $sql = "SELECT  value  from " . $GLOBALS['ecs']->table('shop_config') .
            "WHERE code = 'showerpPanel'";
        $rs = $GLOBALS['db']->getRow($sql);
        if( $rs['value']==1 and $is_super_admin==1 ){
            $panel_display = "block";
        }
        $panel_flag = $is_super_admin==1 ? 1 : 0;
        $onclick = $is_super_admin==1?'onclick="showPanel()"':"";
        $cert = new certificate();
        if($cert->is_bind_sn('erp','goods_name')){//已经开通绑定了ERP
            $panel_flag=0;
            $erp_url="https://account.shopex.cn/product";//erp登录地址
            $erp_icon_html='<a href="'.$erp_url.'" class="btn-ERP" target="_blank">'.$_LANG['erp_enter'].'<i class="cl-red">ERP</i>'.$_LANG['erp_processing_orders'].'</a>';
        }elseif($cert->is_open_sn('erp')) {//只开通了ERP未绑定
            $erpstr=array($_LANG['erp_bind_desc'],$_LANG['erp_bind']);//您已开通ERP，请授权绑定|去绑定
            $erp_url = "certificate.php?act=list_edit";//绑定erp的地址
            $url=$is_super_admin==1?'javascript:void(0)':$erp_url;
            $erp_icon_html='<a href="'.$url.'" class="btn-ERP" '.$onclick.'>'.$_LANG['erp_bind_Auth'].'<i class="cl-red">ERP</i></a>';//授权绑定
        }else{//未开通ERP
            $erpstr=array($_LANG['erp_open_desc'],$_LANG['erp_open']);//已有99%的用户使用ERP处理订单|去开通
            $erp_url = "https://yunqi.shopex.cn/products/erp";//erp登录地址
            $erp_icon_html='<a href="'.$erp_url.'" target="_blank" onclick="getSnList();" class="btn-ERP" >'.$_LANG['erp_open_details'].'<i class="cl-red">ERP</i></a>';//了解详情开通
        }
    }

    $smarty->assign('panel_flag',  $panel_flag);
    $smarty->assign('panel_display',  $panel_display);
    $smarty->assign('erp_str',  $erpstr);
    $smarty->assign('erp_url',  $erp_url);
    $smarty->assign('erp_icon_html',  $erp_icon_html);
    /* 显示模板 */
    assign_query_info();

    $smarty->display('order_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{

    /* 检查权限 */
    admin_priv('order_query');
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    $order_list = order_list();
    $smarty->assign('where',   $order_list['where']);
    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $smarty->assign('goods_num',   $order_list['goods_num']);
    $sort_flag  = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch('order_list.htm'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}

/*------------------------------------------------------ */
//-- 订单详情页面
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'info')
{
    $suppliers_id = $_SESSION['suppliers_id'];
    $suppliers_where = '';
    if($_SESSION['admin_type'] == 1){
        $suppliers_where = " and suppliers_id in ({$suppliers_id})";
    }
    $smarty->assign('suppliers_id', $suppliers_id);
    $smarty->assign('admin_type', $_SESSION['admin_type']);
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    $sql = "SELECT `pay_id`, `pay_name` FROM " . $ecs->table('payment');
    $payMethods = $db->getAll($sql);

    /* 根据订单id或订单号查询订单信息 */
    if (isset($_REQUEST['order_id']))
    {
        $order_id = intval($_REQUEST['order_id']);
        $order = order_info($order_id, 0, $suppliers_id);
    }
    elseif (isset($_REQUEST['order_sn']))
    {
        $order_sn = trim($_REQUEST['order_sn']);
        $order = order_info(0, $order_sn, $suppliers_id);
    }
    else
    {
        /* 如果参数不存在，退出 */
        die('invalid parameter');
    }

    /* 如果订单不存在，退出 */
    if (empty($order))
    {
        die('order does not exist');
    }

    /* 根据订单是否完成检查权限 */
    if (order_finished($order))
    {
        admin_priv('order_view_finished');
    }
    else
    {
        admin_priv('order_query');
    }

    /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
    $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
    $agency_id = $db->getOne($sql);
    if ($agency_id > 0)
    {
        if ($order['agency_id'] != $agency_id)
        {
            sys_msg($_LANG['priv_error']);
        }
    }

    /* 取得上一个、下一个订单号 */
    if (!empty($_COOKIE['ECSCP']['lastfilter']))
    {
        $filter = unserialize(urldecode($_COOKIE['ECSCP']['lastfilter']));
        if (!empty($filter['composite_status']))
        {
            $where = '';
            //综合状态
            switch($filter['composite_status'])
            {
                case CS_AWAIT_PAY :
                    $where .= order_query_sql('await_pay');
                    break;

                case CS_AWAIT_SHIP :
                    $where .= order_query_sql('await_ship');
                    break;

                case CS_FINISHED :
                    $where .= order_query_sql('finished');
                    break;

                default:
                    if ($filter['composite_status'] != -1)
                    {
                        $where .= " AND o.order_status = '$filter[composite_status]' ";
                    }
            }
        }
    }
    $sql = "SELECT MAX(order_id) FROM " . $ecs->table('order_info') . " as o WHERE order_id < '$order[order_id]'";
    if ($agency_id > 0)
    {
        $sql .= " AND agency_id = '$agency_id'";
    }
    if (!empty($where))
    {
        $sql .= $where;
    }
    if (!empty($suppliers_where))
    {
        $sql .= $suppliers_where;
    }
    $smarty->assign('prev_id', $db->getOne($sql));
    $sql = "SELECT MIN(order_id) FROM " . $ecs->table('order_info') . " as o WHERE order_id > '$order[order_id]'";
    if ($agency_id > 0)
    {
        $sql .= " AND agency_id = '$agency_id'";
    }
    if (!empty($where))
    {
        $sql .= $where;
    }
    if (!empty($suppliers_where))
    {
        $sql .= $suppliers_where;
    }
    $smarty->assign('next_id', $db->getOne($sql));

    /* 取得用户名 */
    if ($order['user_id'] > 0)
    {
        $user = user_info($order['user_id']);
        if (!empty($user))
        {
            $order['user_name'] = $user['user_name'];
        }
    }

    /* 取得所有办事处 */
    $sql = "SELECT agency_id, agency_name FROM " . $ecs->table('agency');
    $smarty->assign('agency_list', $db->getAll($sql));

    /* 取得区域名 */
    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
        "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
        "FROM " . $ecs->table('order_info') . " AS o " .
        "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
        "WHERE o.order_id = '$order[order_id]'";
    $order['region'] = $db->getOne($sql);
    $order['formated_settlement_money']             = price_format($row['settlement_money']);

    /* 格式化金额 */
    if ($order['order_amount'] < 0)
    {
        $order['money_refund']          = abs($order['order_amount']);
        $order['formated_money_refund'] = price_format(abs($order['order_amount']));
    }

    /* 其他处理 */
    $order['order_time']    = local_date($_CFG['time_format'], $order['add_time']);
    $order['pay_time']      = $order['pay_time'] > 0 ?
        local_date($_CFG['time_format'], $order['pay_time']) : $_LANG['ps'][PS_UNPAYED];
    $order['shipping_time'] = $order['shipping_time'] > 0 ?
        local_date($_CFG['time_format'], $order['shipping_time']) : $_LANG['ss'][SS_UNSHIPPED];
    $order['status']        = $_LANG['os'][$order['order_status']] . ',' . $_LANG['ps'][$order['pay_status']] . ',' . $_LANG['ss'][$order['shipping_status']];
    $order['invoice_no']    = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];

    /* 取得订单的来源 */
    if ($order['from_ad'] == 0)
    {
        $order['referer'] = empty($order['referer']) ? $_LANG['from_self_site'] : $order['referer'];
    }
    elseif ($order['from_ad'] == -1)
    {
        $order['referer'] = $_LANG['from_goods_js'] . ' ('.$_LANG['from'] . $order['referer'].')';
    }
    else
    {
        /* 查询广告的名称 */
        $ad_name = $db->getOne("SELECT ad_name FROM " .$ecs->table('ad'). " WHERE ad_id='$order[from_ad]'");
        $order['referer'] = $_LANG['from_ad_js'] . $ad_name . ' ('.$_LANG['from'] . $order['referer'].')';
    }

    if($order['mlm_id'] > 0){
        $order['referer'] = $_LANG['from_mlm'];
    }

    // 设置分期信息
    if($order['order_type'] == 1){
        $order['referer'] = $_LANG['from_instalment'];
        $sql = "SELECT * FROM " . $ecs->table('order_instalment') . " WHERE `order_id`=$order[order_id]";
        $instalmentOrder = $db->getAll($sql);
        $arr = [];
        foreach($instalmentOrder as $key => $item){
            $arr[$key + 1]['all'] = $item['total_amount'];
            $arr[$key + 1]['need_pay_time'] = date('Y-m-d H:i:s', $item['need_pay_time']);
            $arr[$key + 1]['method'] = getInstalmentMethod($item['pay_id'], $payMethods);
            $arr[$key + 1]['instalment_sn'] = $item['instalment_sn'];
            $arr[$key + 1]['over'] = getOverTime($item['need_pay_time'], $item['real_pay_time']);
            $arr[$key + 1]['real_money_paid'] = $item['real_money_paid'];
            $arr[$key + 1]['real_surplus']    = $item['real_surplus'];
            // 如果支付为0，当前时间大于应付时间，变已支付
            if ($item['money_paid'] == 0.00 && $item['surplus'] == 0.00) {
                if (time() > $item['need_pay_time']) {
                    $item['status'] = INSTALMENT_OS_CONFIRMED;
                }
            }

            $arr[$key + 1]['status'] = getInstalmentStatus($item['status'], $item['real_pay_time']);
        }
        $smarty->assign('instalment_order', $arr);
        $smarty->assign('instalment_num', count($instalmentOrder));
        $smarty->assign('instalment_method', $instalmentOrder[0]['method']);
    }

    if($order['order_type'] == 4){
        $order['referer'] = $_LANG['from_seckill'];
    }

    if($order['order_type'] == 5){
        $order['referer'] = $_LANG['from_train'];
    }

    /* 获取商家名称 */
    if($order['suppliers_id']>0){
        $sql = "SELECT shop_name FROM" . $ecs->table('suppliers') . " WHERE suppliers_id = ".$order['suppliers_id'];
        $order['supplier'] = $db->getOne($sql);
    }

    /* 此订单的发货备注(此订单的最后一条操作记录) */
    $sql = "SELECT action_note FROM " . $ecs->table('order_action').
        " WHERE order_id = '$order[order_id]' AND shipping_status = 1 ORDER BY log_time DESC";
    $order['invoice_note'] = $db->getOne($sql);

    /* 取得订单商品总重量 */
    $weight_price = order_weight_price($order['order_id']);
    $order['total_weight'] = $weight_price['formated_weight'];

    /* 参数赋值：订单 */
    $smarty->assign('order', $order);

    /* 取得用户信息 */
    if ($order['user_id'] > 0)
    {
        /* 用户等级 */
        if ($user['user_rank'] > 0)
        {
            $where = " WHERE rank_id = '$user[user_rank]' ";
        }
        else
        {
            $where = " WHERE min_points <= " . intval($user['rank_points']) . " ORDER BY min_points DESC ";
        }
        $sql = "SELECT rank_name FROM " . $ecs->table('user_rank') . $where;
        $user['rank_name'] = $db->getOne($sql);

        // 用户红包数量
        $day    = getdate();
        $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $sql = "SELECT COUNT(*) " .
            "FROM " . $ecs->table('bonus_type') . " AS bt, " . $ecs->table('user_bonus') . " AS ub " .
            "WHERE bt.type_id = ub.bonus_type_id " .
            "AND ub.user_id = '$order[user_id]' " .
            "AND ub.order_id = 0 " .
            "AND bt.use_start_date <= '$today' " .
            "AND bt.use_end_date >= '$today'";
        $user['bonus_count'] = $db->getOne($sql);
        $smarty->assign('user', $user);

        // 地址信息
        $sql = "SELECT * FROM " . $ecs->table('user_address') . " WHERE user_id = '$order[user_id]'";
        $smarty->assign('address_list', $db->getAll($sql));
    }

    /* 取得订单商品及货品 */
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, g.suppliers_id, IFNULL(b.brand_name, '') AS brand_name, p.product_sn, g.goods_location, g.shop_price, g.money_line
            FROM " . $ecs->table('order_goods') . " AS o
                LEFT JOIN " . $ecs->table('products') . " AS p
                    ON p.product_id = o.product_id
                LEFT JOIN " . $ecs->table('goods') . " AS g
                    ON o.goods_id = g.goods_id
                LEFT JOIN " . $ecs->table('brand') . " AS b
                    ON g.brand_id = b.brand_id
            WHERE o.order_id = '$order[order_id]'";
    $res = $db->query($sql);
    $formated_subtotal_settlement_money = 0;
    while ($row = $db->fetchRow($res))
    {
        /* 虚拟商品支持 */
        if ($row['is_real'] == 0)
        {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
            if (file_exists($filename))
            {
                include_once($filename);
                if (!empty($_LANG[$row['extension_code'].'_link']))
                {
                    $row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        if (empty($row['settlement_money']) || 0.00 == $row['settlement_money']) {
            $row['settlement_money']    = $row['goods_price'];
        }

        $formated_subtotal_settlement_money         += $row['settlement_money'] * $row['goods_number'];
        $row['formated_goods_settlement_money']     = price_format($row['settlement_money'] * $row['goods_number']);
        $row['formated_subtotal']                   = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']                = price_format($row['goods_price']);
        $row['formated_settlement_money']           = price_format($row['settlement_money']);
        $row['formated_settlement_money']   = price_format($row['settlement_money']);

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

        if ($row['extension_code'] == 'package_buy')
        {
            $row['storage'] = '';
            $row['brand_name'] = '';
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }

        $goods_list[] = $row;
    }
    // echo "<pre>";print_r($order);//exit();


    $attr = array();
    $arr  = array();
    foreach ($goods_attr AS $index => $array_val)
    {
        foreach ($array_val AS $value)
        {
            $arr = explode(':', $value);//以 : 号将属性拆开
            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }
    // 如果是以物抵债商品则判断是否是商家购买
    if($goods_list[0]['shop_price'] == $goods_list[0]['money_line']){
        if($user['suppliers_id'] > 0){
            $smarty->assign('suppliers_pay', true);
        }
    }

    $smarty->assign('formated_subtotal_settlement_money',   price_format($formated_subtotal_settlement_money));
    $smarty->assign('formated_total_settlement_money',      price_format($formated_subtotal_settlement_money + $order['shipping_fee']));

    $smarty->assign('goods_attr', $attr);
    $smarty->assign('goods_list', $goods_list);

    // 如果是分销订单获取分销信息
    if($order['mlm_id'] > 0){
        $smarty->assign('suppliers_id', $_SESSION['suppliers_id']);
        $sql = "SELECT u.user_name, mo.buyer_time, mo.order_status, mo.pay_status, mo.rebate,mo.act_rebate,mo.custom_profit,mo.do_surplus,mo.surplus, ".
            "mo.pay_cash,gm.shop_price,gm.money_line,gm.real_shop_price,gm.real_money_line, ".
            "moa.act_goods_id".
            " FROM " .
            $ecs->table('mlm_order') . " AS mo ".
            " LEFT JOIN " . $ecs->table('mlm_order_activity') . " AS moa ON mo.id=moa.order_id " .
            " LEFT JOIN " . $ecs->table('mlm_goods') . " AS mg ON mg.mlm_id=mo.mlm_id " .
            " LEFT JOIN " . $ecs->table('goods_mlm') . " AS gm ON gm.goods_id=mg.goods_id " .
            " LEFT JOIN " . $ecs->table('users') . " AS u ON u.user_id=mo.user_id " .
            " WHERE mo.order_id = '$order[order_id]' ".
            " LIMIT 1 ";
        $mlmOrderQuery = $db->query($sql);
        $mlmOrder = $db->fetchRow($mlmOrderQuery);

        $mlmOrder['mlm_act_award'] = '0.00';
        $mlmOrder['mlm_new_award'] = '0.00';
        $mlmOrder['real_shop_price_money'] = bcsub($mlmOrder['real_shop_price'], $mlmOrder['real_money_line'], 2);
        $mlmOrder['shop_price_money'] = bcsub($mlmOrder['shop_price'], $mlmOrder['money_line'], 2);

        if($mlmOrder['custom_profit'] > 0){
            $mlmOrder['surplus_exchange'] = bcsub($mlmOrder['rebate'], bcadd($mlmOrder['act_rebate'], $mlmOrder['custom_profit'], 2), 2);
            $mlmOrder['act_real_get'] = $mlmOrder['act_rebate'];
        }else{
            $mlmOrder['surplus_exchange'] = bcsub($mlmOrder['rebate'], $mlmOrder['act_rebate'], 2);
            $act_real_get = bcadd($mlmOrder['act_rebate'], $mlmOrder['custom_profit'], 2);
            $mlmOrder['act_real_get'] = $act_real_get > 0 ? $act_real_get : '0.00';
        }

        $mlmOrder['custom_profit'] > 0 ? $mlmOrder['mlm_money'] = $mlmOrder['custom_profit'] : $mlmOrder['mlm_money'] = '0.00';

        // 判断是否是活动订单
        if($mlmOrder['act_goods_id']){
            $sql = "SELECT e.`act_name`,e.`change_money`,g.`act_operation_price`,g.`act_operation_coin_upper_limit` FROM " .
                $ecs->table('mlm_activity_goods_ext') . ' AS e ' .
                ' LEFT JOIN ' . $ecs->table('mlm_activity_goods') . " AS g ON g.act_goods_id=e.act_goods_id " .
                " WHERE e.act_goods_id=" . $mlmOrder['act_goods_id'];
            $actExtQuery = $db->query($sql);

            // 获取活动佣金
            while($row = $db->fetchRow($actExtQuery)){
                if($row['act_name'] == $_LANG['mlm_new_award']){
                    $mlmOrder['mlm_new_award'] = $row['change_money'];
                }else if($row['act_name'] == $_LANG['mlm_act_award']){
                    $mlmOrder['mlm_act_award'] = $row['change_money'];
                }

                $mlmOrder['act_operation_price_money'] = bcsub($row['act_operation_price'], $row['act_operation_coin_upper_limit'], 2);
                $mlmOrder['act_operation_coin_upper_limit'] = $row['act_operation_coin_upper_limit'];
            }
        }

        $smarty->assign('mlmOrder', $mlmOrder);
    }

    // 获取线下退款信息
    $sql = 'SELECT a.`audit_id`, u.`user_name`, a.`user_money`, a.`created_at`, a.`audit_status`, a.`change_desc` FROM ' . 
        $ecs->table('account_change_audit') . ' AS a LEFT JOIN ' .
        $ecs->table('users') . ' AS u ' .
        ' ON a.user_id = u.user_id ' .
        ' WHERE a.related_info = ' . $order['order_id'];
    $audits = $db->getAll($sql);
    foreach ($audits as $key => $item) {
        $audits[$key]['created_at'] = date('Y-m-d H:i:s', $item['created_at']);
        $audits[$key]['audit_status'] = $_LANG['offline_audit_status_list'][$item['audit_status']];
    }
    $audits == [] ? $audits = false : '';
    $smarty->assign('audits', $audits);

    /* 取得能执行的操作列表 */
    $operable_list = operable_list($order);
    $smarty->assign('operable_list', $operable_list);

    /* 取得订单操作记录 */
    $act_list = array();
    $sql = "SELECT * FROM " . $ecs->table('order_action') . " WHERE order_id = '$order[order_id]' ORDER BY log_time DESC,action_id DESC";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $row['order_status']    = $_LANG['os'][$row['order_status']];
        $row['pay_status']      = $_LANG['ps'][$row['pay_status']];
        $row['shipping_status'] = $_LANG['ss'][$row['shipping_status']];
        $row['action_time']     = local_date($_CFG['time_format'], $row['log_time']);
        $act_list[] = $row;
    }
    $smarty->assign('action_list', $act_list);

    /* 取得是否存在实体商品 */
    $smarty->assign('exist_real_goods', exist_real_goods($order['order_id']));

    /* 是否打印订单，分别赋值 */
    if (isset($_GET['print']))
    {
        $smarty->assign('shop_name',    $_CFG['shop_name']);
        $smarty->assign('shop_url',     $ecs->url());
        $smarty->assign('shop_address', $_CFG['shop_address']);
        $smarty->assign('service_phone',$_CFG['service_phone']);
        $smarty->assign('print_time',   local_date($_CFG['time_format']));
        $smarty->assign('action_user',  $_SESSION['admin_name']);

        $smarty->template_dir = '../' . DATA_DIR;
        $smarty->display('order_print.html');
    }
    /* 打印快递单 */
    elseif (isset($_GET['shipping_print']))
    {
        //$smarty->assign('print_time',   local_date($_CFG['time_format']));
        //发货地址所在地
        $region_array = array();
        $region_id = !empty($_CFG['shop_country']) ? $_CFG['shop_country'] . ',' : '';
        $region_id .= !empty($_CFG['shop_province']) ? $_CFG['shop_province'] . ',' : '';
        $region_id .= !empty($_CFG['shop_city']) ? $_CFG['shop_city'] . ',' : '';
        $region_id .= !empty($order['province']) ? $order['province'] . ',' : '';
        $region_id .= !empty($order['city']) ? $order['city'] . ',' : '';
        $region_id .= !empty($order['district']) ? $order['district'] . ',' : '';
        $region_id = substr($region_id, 0, -1);
        $region = $db->getAll("SELECT region_id, region_name FROM " . $ecs->table("region") . " WHERE region_id IN ($region_id)");
        if (!empty($region))
        {
            foreach($region as $region_data)
            {
                $region_array[$region_data['region_id']] = $region_data['region_name'];
            }
        }
        $smarty->assign('shop_name',    $_CFG['shop_name']);
        $smarty->assign('order_id',    $order_id);
        $smarty->assign('province', $region_array[$_CFG['shop_province']]);
        $smarty->assign('city', $region_array[$_CFG['shop_city']]);
        $smarty->assign('shop_address', $_CFG['shop_address']);
        $smarty->assign('service_phone',$_CFG['service_phone']);
        $shipping = $db->getRow("SELECT * FROM " . $ecs->table("shipping") . " WHERE shipping_id = " . $order['shipping_id']);

        //打印单模式
        if ($shipping['print_model'] == 2)
        {
            /* 可视化 */
            /* 快递单 */
            $shipping['print_bg'] = empty($shipping['print_bg']) ? '' : get_site_root_url() . $shipping['print_bg'];

            /* 取快递单背景宽高 */
            if (!empty($shipping['print_bg']))
            {
                $_size = @getimagesize($shipping['print_bg']);

                if ($_size != false)
                {
                    $shipping['print_bg_size'] = array('width' => $_size[0], 'height' => $_size[1]);
                }
            }

            if (empty($shipping['print_bg_size']))
            {
                $shipping['print_bg_size'] = array('width' => '1024', 'height' => '600');
            }

            /* 标签信息 */
            $lable_box = array();
            $lable_box['t_shop_country'] = $region_array[$_CFG['shop_country']]; //网店-国家
            $lable_box['t_shop_city'] = $region_array[$_CFG['shop_city']]; //网店-城市
            $lable_box['t_shop_province'] = $region_array[$_CFG['shop_province']]; //网店-省份
            $lable_box['t_shop_name'] = $_CFG['shop_name']; //网店-名称
            $lable_box['t_shop_district'] = ''; //网店-区/县
            $lable_box['t_shop_tel'] = $_CFG['service_phone']; //网店-联系电话
            $lable_box['t_shop_address'] = $_CFG['shop_address']; //网店-地址
            $lable_box['t_customer_country'] = $region_array[$order['country']]; //收件人-国家
            $lable_box['t_customer_province'] = $region_array[$order['province']]; //收件人-省份
            $lable_box['t_customer_city'] = $region_array[$order['city']]; //收件人-城市
            $lable_box['t_customer_district'] = $region_array[$order['district']]; //收件人-区/县
            $lable_box['t_customer_tel'] = $order['tel']; //收件人-电话
            $lable_box['t_customer_mobel'] = $order['mobile']; //收件人-手机
            $lable_box['t_customer_post'] = $order['zipcode']; //收件人-邮编
            $lable_box['t_customer_address'] = $order['address']; //收件人-详细地址
            $lable_box['t_customer_name'] = $order['consignee']; //收件人-姓名

            $gmtime_utc_temp = gmtime(); //获取 UTC 时间戳
            $lable_box['t_year'] = date('Y', $gmtime_utc_temp); //年-当日日期
            $lable_box['t_months'] = date('m', $gmtime_utc_temp); //月-当日日期
            $lable_box['t_day'] = date('d', $gmtime_utc_temp); //日-当日日期

            $lable_box['t_order_no'] = $order['order_sn']; //订单号-订单
            $lable_box['t_order_postscript'] = $order['postscript']; //备注-订单
            $lable_box['t_order_best_time'] = $order['best_time']; //送货时间-订单
            $lable_box['t_pigeon'] = '√'; //√-对号
            $lable_box['t_custom_content'] = ''; //自定义内容

            //标签替换
            $temp_config_lable = explode('||,||', $shipping['config_lable']);
            if (!is_array($temp_config_lable))
            {
                $temp_config_lable[] = $shipping['config_lable'];
            }
            foreach ($temp_config_lable as $temp_key => $temp_lable)
            {
                $temp_info = explode(',', $temp_lable);
                if (is_array($temp_info))
                {
                    $temp_info[1] = $lable_box[$temp_info[0]];
                }
                $temp_config_lable[$temp_key] = implode(',', $temp_info);
            }
            $shipping['config_lable'] = implode('||,||',  $temp_config_lable);

            $smarty->assign('shipping', $shipping);

            $smarty->display('print.htm');
        }
        elseif (!empty($shipping['shipping_print']))
        {
            /* 代码 */
            echo $smarty->fetch("str:" . $shipping['shipping_print']);
        }
        else
        {
            $shipping_code = $db->getOne("SELECT shipping_code FROM " . $ecs->table('shipping') . " WHERE shipping_id=" . $order['shipping_id']);
            if ($shipping_code)
            {
                include_once(ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php');
            }

            if (!empty($_LANG['shipping_print']))
            {
                echo $smarty->fetch("str:$_LANG[shipping_print]");
            }
            else
            {
                echo $_LANG['no_print_shipping'];
            }
        }
    }
    else
    {
        /* 模板赋值 */
        $smarty->assign('ur_here', $_LANG['order_info']);
        $smarty->assign('action_link', array('href' => 'order.php?act=list&' . list_link_postfix(), 'text' => $_LANG['02_order_list']));

        /* 显示模板 */
        assign_query_info();
        $smarty->display('order_info.htm');
    }
}

/*------------------------------------------------------ */
//-- 发货单列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'delivery_list')
{
    /* 检查权限 */
    admin_priv('delivery_view');
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    /* 查询 */
    $result = delivery_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['09_delivery_order']);

    $smarty->assign('os_unconfirmed',   OS_UNCONFIRMED);
    $smarty->assign('cs_await_pay',     CS_AWAIT_PAY);
    $smarty->assign('cs_await_ship',    CS_AWAIT_SHIP);
    $smarty->assign('full_page',        1);

    $smarty->assign('delivery_list',   $result['delivery']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_update_time', '<img src="images/sort_desc.png">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('delivery_list.htm');
}

/*------------------------------------------------------ */
//-- 搜索、排序、分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'delivery_query')
{
    /* 检查权限 */
    admin_priv('delivery_view');

    $result = delivery_list();

    $smarty->assign('delivery_list',   $result['delivery']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    $sort_flag = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch('delivery_list.htm'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 发货单详细
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'delivery_info')
{
    /* 检查权限 */
    admin_priv('delivery_view');
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    $delivery_id = intval(trim($_REQUEST['delivery_id']));

    /* 根据发货单id查询发货单信息 */
    if (!empty($delivery_id))
    {
        $delivery_order = delivery_order_info($delivery_id);
    }
    else
    {
        die('order does not exist');
    }

    /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
    $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '" . $_SESSION['admin_id'] . "'";
    $agency_id = $db->getOne($sql);
    if ($agency_id > 0)
    {
        if ($delivery_order['agency_id'] != $agency_id)
        {
            sys_msg($_LANG['priv_error']);
        }

        /* 取当前办事处信息 */
        $sql = "SELECT agency_name FROM " . $ecs->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
        $agency_name = $db->getOne($sql);
        $delivery_order['agency_name'] = $agency_name;
    }

    /* 取得用户名 */
    if ($delivery_order['user_id'] > 0)
    {
        $user = user_info($delivery_order['user_id']);
        if (!empty($user))
        {
            $delivery_order['user_name'] = $user['user_name'];
        }
    }

    /* 取得区域名 */
    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
        "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
        "FROM " . $ecs->table('order_info') . " AS o " .
        "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
        "WHERE o.order_id = '" . $delivery_order['order_id'] . "'";
    $delivery_order['region'] = $db->getOne($sql);

    /* 是否保价 */
    $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

    /* 取得发货单商品 */
    $goods_sql = "SELECT *
                  FROM " . $ecs->table('delivery_goods') . "
                  WHERE delivery_id = " . $delivery_order['delivery_id'];
    $goods_list = $GLOBALS['db']->getAll($goods_sql);

    /* 是否存在实体商品 */
    $exist_real_goods = 0;
    if ($goods_list)
    {
        foreach ($goods_list as $value)
        {
            if ($value['is_real'])
            {
                $exist_real_goods++;
            }
        }
    }

    /* 取得订单操作记录 */
    $act_list = array();
    $sql = "SELECT * FROM " . $ecs->table('order_action') . " WHERE order_id = '" . $delivery_order['order_id'] . "' AND action_place = 1 ORDER BY log_time DESC,action_id DESC";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $row['order_status']    = $_LANG['os'][$row['order_status']];
        $row['pay_status']      = $_LANG['ps'][$row['pay_status']];
        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? $_LANG['ss_admin'][SS_SHIPPED_ING] : $_LANG['ss'][$row['shipping_status']];
        $row['action_time']     = local_date($_CFG['time_format'], $row['log_time']);
        $act_list[] = $row;
    }
    $smarty->assign('action_list', $act_list);

    /* 模板赋值 */
    $smarty->assign('delivery_order', $delivery_order);
    $smarty->assign('exist_real_goods', $exist_real_goods);
    $smarty->assign('goods_list', $goods_list);
    $smarty->assign('delivery_id', $delivery_id); // 发货单id

    /* 显示模板 */
    $smarty->assign('ur_here', $_LANG['delivery_operate'] . $_LANG['detail']);
    $smarty->assign('action_link', array('href' => 'order.php?act=delivery_list&' . list_link_postfix(), 'text' => $_LANG['09_delivery_order']));
    $smarty->assign('action_act', ($delivery_order['status'] == 2) ? 'delivery_ship' : 'delivery_cancel_ship');
    assign_query_info();
    $smarty->display('delivery_info.htm');
    exit; //
}

/*------------------------------------------------------ */
//-- 发货单发货确认
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'delivery_ship')
{
    //商家暂无此权限
    if($_SESSION['admin_type'] == 1){
        sys_msg($_LANG['priv_error'], 0);
    }
    /* 检查权限 */
    admin_priv('delivery_view');

    /* 定义当前时间 */
    define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

    /* 取得参数 */
    $delivery   = array();
    $order_id   = intval(trim($_REQUEST['order_id']));        // 订单id
    $delivery_id   = intval(trim($_REQUEST['delivery_id']));        // 发货单id
    $delivery['invoice_no'] = isset($_REQUEST['invoice_no']) ? trim($_REQUEST['invoice_no']) : '';
    $action_note    = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

    /* 根据发货单id查询发货单信息 */
    if (!empty($delivery_id))
    {
        $delivery_order = delivery_order_info($delivery_id);
    }
    else
    {
        die('order does not exist');
    }

    /* 查询订单信息 */
    $order = order_info($order_id);

    /* 检查此单发货商品库存缺货情况 */
    $virtual_goods = array();
    $delivery_stock_sql = "SELECT DG.goods_id, DG.is_real, DG.product_id, SUM(DG.send_number) AS sums, IF(DG.product_id > 0, P.product_number, G.goods_number) AS storage, G.goods_name, DG.send_number
        FROM " . $GLOBALS['ecs']->table('delivery_goods') . " AS DG, " . $GLOBALS['ecs']->table('goods') . " AS G, " . $GLOBALS['ecs']->table('products') . " AS P
        WHERE DG.goods_id = G.goods_id
        AND DG.delivery_id = '$delivery_id'
        AND DG.product_id = P.product_id
        GROUP BY DG.product_id ";

    $delivery_stock_result = $GLOBALS['db']->getAll($delivery_stock_sql);

    /* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
    if(!empty($delivery_stock_result))
    {
        foreach ($delivery_stock_result as $value)
        {
            if (($value['sums'] > $value['storage'] || $value['storage'] <= 0) && (($_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP) || ($_CFG['use_storage'] == '0' && $value['is_real'] == 0)))
            {
                /* 操作失败 */
                $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                sys_msg(sprintf($_LANG['act_good_vacancy'], $value['goods_name']), 1, $links);
                break;
            }

            /* 虚拟商品列表 virtual_card*/
            if ($value['is_real'] == 0)
            {
                $virtual_goods[] = array(
                    'goods_id' => $value['goods_id'],
                    'goods_name' => $value['goods_name'],
                    'num' => $value['send_number']
                );
            }
        }
    }
    else
    {
        $delivery_stock_sql = "SELECT DG.goods_id, DG.is_real, SUM(DG.send_number) AS sums, G.goods_number, G.goods_name, DG.send_number
        FROM " . $GLOBALS['ecs']->table('delivery_goods') . " AS DG, " . $GLOBALS['ecs']->table('goods') . " AS G
        WHERE DG.goods_id = G.goods_id
        AND DG.delivery_id = '$delivery_id'
        GROUP BY DG.goods_id ";
        $delivery_stock_result = $GLOBALS['db']->getAll($delivery_stock_sql);
        foreach ($delivery_stock_result as $value)
        {
            if (($value['sums'] > $value['goods_number'] || $value['goods_number'] <= 0) && (($_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP) || ($_CFG['use_storage'] == '0' && $value['is_real'] == 0)))
            {
                /* 操作失败 */
                $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                sys_msg(sprintf($_LANG['act_good_vacancy'], $value['goods_name']), 1, $links);
                break;
            }

            /* 虚拟商品列表 virtual_card*/
            if ($value['is_real'] == 0)
            {
                $virtual_goods[] = array(
                    'goods_id' => $value['goods_id'],
                    'goods_name' => $value['goods_name'],
                    'num' => $value['send_number']
                );
            }
        }
    }

    /* 发货 */
    /* 处理虚拟卡 商品（虚货） */
    if (is_array($virtual_goods) && count($virtual_goods) > 0)
    {
        foreach ($virtual_goods as $virtual_value)
        {
            virtual_card_shipping($virtual_value,$order['order_sn'], $msg, 'split');
        }
    }

    /* 如果使用库存，且发货时减库存，则修改库存 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_SHIP)
    {

        foreach ($delivery_stock_result as $value)
        {

            /* 商品（实货）、超级礼包（实货） */
            if ($value['is_real'] != 0)
            {
                //（货品）
                if (!empty($value['product_id']))
                {
                    $minus_stock_sql = "UPDATE " . $GLOBALS['ecs']->table('products') . "
                                        SET product_number = product_number - " . $value['sums'] . "
                                        WHERE product_id = " . $value['product_id'];
                    $GLOBALS['db']->query($minus_stock_sql, 'SILENT');
                }

                $minus_stock_sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . "
                                    SET goods_number = goods_number - " . $value['sums'] . "
                                    WHERE goods_id = " . $value['goods_id'];

                $GLOBALS['db']->query($minus_stock_sql, 'SILENT');
            }
        }
    }

    /* 修改发货单信息 */
    $invoice_no = str_replace(',', '<br>', $delivery['invoice_no']);
    $invoice_no = trim($invoice_no, '<br>');
    $_delivery['invoice_no'] = $invoice_no;
    $_delivery['status'] = 0; // 0，为已发货
    $query = $db->autoExecute($ecs->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');
    if (!$query)
    {
        /* 操作失败 */
        $links[] = array('text' => $_LANG['delivery_sn'] . $_LANG['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
        sys_msg($_LANG['act_false'], 1, $links);
    }

    /* 标记订单为已确认 “已发货” */
    /* 更新发货时间 */
    $order_finish = get_all_delivery_finish($order_id);
    $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
    $arr['shipping_status']     = $shipping_status;
    $arr['shipping_time']       = GMTIME_UTC; // 发货时间
    $arr['invoice_no']          = trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');
    update_order($order_id, $arr);

    update_order_crm($order['order_sn']);
    /* 发货单发货记录log */
    order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, null, 1);

    /* 如果当前订单已经全部发货 */
    if ($order_finish)
    {
        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
        if ($order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($order['user_id']);

            /* 计算并发放积分 */
            $integral = integral_to_give($order);

            log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($_LANG['order_gift_integral'], $order['order_sn']));

            /* 发放红包 */
            send_order_bonus($order_id);
        }

        /* 发送邮件 */
        $cfg = $_CFG['send_ship_email'];
        if ($cfg == '1')
        {
            $order['invoice_no'] = $invoice_no;
            $tpl = get_mail_template('deliver_notice');
            $smarty->assign('order', $order);
            $smarty->assign('send_time', local_date($_CFG['time_format']));
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', local_date($_CFG['date_format']));
            $smarty->assign('sent_date', local_date($_CFG['date_format']));
            $smarty->assign('confirm_url', $ecs->url() . 'receive.php?id=' . $order['order_id'] . '&con=' . rawurlencode($order['consignee']));
            $smarty->assign('send_msg_url',$ecs->url() . 'user.php?act=message_list&order_id=' . $order['order_id']);
            $content = $smarty->fetch('str:' . $tpl['template_content']);
            if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $msg = $_LANG['send_mail_fail'];
            }
        }

        /* 如果需要，发短信 */
        if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '')
        {
            include_once('../includes/cls_sms.php');
            $sms = new sms();
            $sms->send($order['mobile'], sprintf($GLOBALS['_LANG']['order_shipped_sms'], $order['order_sn'],
                local_date($GLOBALS['_LANG']['sms_time_format']), $GLOBALS['_CFG']['shop_name']), 0);
        }
    }

    //发货通知APP
    $is_push = delivery_msg_push($delivery_id,$db,$ecs);
    /* 清除缓存 */
    clear_cache_files();

    /* 操作成功 */
    $links[] = array('text' => $_LANG['09_delivery_order'], 'href' => 'order.php?act=delivery_list');
    $links[] = array('text' => $_LANG['delivery_sn'] . $_LANG['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
    sys_msg($_LANG['act_ok'], 0, $links);
}

/*------------------------------------------------------ */
//-- 发货单取消发货
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'delivery_cancel_ship')
{
    //商家暂无此权限
    if($_SESSION['admin_type'] == 1){
        sys_msg($_LANG['priv_error'], 0);
    }
    /* 检查权限 */
    admin_priv('delivery_view');

    /* 取得参数 */
    $delivery = '';
    $order_id   = intval(trim($_REQUEST['order_id']));        // 订单id
    $delivery_id   = intval(trim($_REQUEST['delivery_id']));        // 发货单id
    $delivery['invoice_no'] = isset($_REQUEST['invoice_no']) ? trim($_REQUEST['invoice_no']) : '';
    $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

    /* 根据发货单id查询发货单信息 */
    if (!empty($delivery_id))
    {
        $delivery_order = delivery_order_info($delivery_id);
    }
    else
    {
        die('order does not exist');
    }

    /* 查询订单信息 */
    $order = order_info($order_id);

    /* 取消当前发货单物流单号 */
    $_delivery['invoice_no'] = '';
    $_delivery['status'] = 2;
    $query = $db->autoExecute($ecs->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');
    if (!$query)
    {
        /* 操作失败 */
        $links[] = array('text' => $_LANG['delivery_sn'] . $_LANG['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
        sys_msg($_LANG['act_false'], 1, $links);
        exit;
    }

    /* 修改定单发货单号 */
    $invoice_no_order = explode('<br>', $order['invoice_no']);
    $invoice_no_delivery = explode('<br>', $delivery_order['invoice_no']);
    foreach ($invoice_no_order as $key => $value)
    {
        $delivery_key = array_search($value, $invoice_no_delivery);
        if ($delivery_key !== false)
        {
            unset($invoice_no_order[$key], $invoice_no_delivery[$delivery_key]);
            if (count($invoice_no_delivery) == 0)
            {
                break;
            }
        }
    }
    $_order['invoice_no'] = implode('<br>', $invoice_no_order);

    /* 更新配送状态 */
    $order_finish = get_all_delivery_finish($order_id);
    $shipping_status = ($order_finish == -1) ? SS_SHIPPED_PART : SS_SHIPPED_ING;
    $arr['shipping_status']     = $shipping_status;
    if ($shipping_status == SS_SHIPPED_ING)
    {
        $arr['shipping_time']   = ''; // 发货时间
    }
    $arr['invoice_no']          = $_order['invoice_no'];
    update_order($order_id, $arr);

    /* 发货单取消发货记录log */
    order_action($order['order_sn'], $order['order_status'], $shipping_status, $order['pay_status'], $action_note, null, 1);

    /* 如果使用库存，则增加库存 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_SHIP)
    {
        // 检查此单发货商品数量
        $virtual_goods = array();
        $delivery_stock_sql = "SELECT DG.goods_id, DG.product_id, DG.is_real, SUM(DG.send_number) AS sums
            FROM " . $GLOBALS['ecs']->table('delivery_goods') . " AS DG
            WHERE DG.delivery_id = '$delivery_id'
            GROUP BY DG.goods_id ";
        $delivery_stock_result = $GLOBALS['db']->getAll($delivery_stock_sql);
        foreach ($delivery_stock_result as $key => $value)
        {
            /* 虚拟商品 */
            if ($value['is_real'] == 0)
            {
                continue;
            }

            //（货品）
            if (!empty($value['product_id']))
            {
                $minus_stock_sql = "UPDATE " . $GLOBALS['ecs']->table('products') . "
                                    SET product_number = product_number + " . $value['sums'] . "
                                    WHERE product_id = " . $value['product_id'];
                $GLOBALS['db']->query($minus_stock_sql, 'SILENT');
            }

            $minus_stock_sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . "
                                SET goods_number = goods_number + " . $value['sums'] . "
                                WHERE goods_id = " . $value['goods_id'];
            $GLOBALS['db']->query($minus_stock_sql, 'SILENT');
        }
    }

    /* 发货单全退回时，退回其它 */
    if ($order['order_status'] == SS_SHIPPED_ING)
    {
        /* 如果订单用户不为空，计算积分，并退回 */
        if ($order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($order['user_id']);

            /* 计算并退回积分 */
            $integral = integral_to_give($order);
            log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf($_LANG['return_order_gift_integral'], $order['order_sn']));

            /* todo 计算并退回红包 */
            return_order_bonus($order_id);
        }
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 操作成功 */
    $links[] = array('text' => $_LANG['delivery_sn'] . $_LANG['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
    sys_msg($_LANG['act_ok'], 0, $links);
}

/*------------------------------------------------------ */
//-- 退货单列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'back_list')
{
    /* 检查权限 */
    admin_priv('back_view');
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    /* 查询 */
    $result = back_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['10_back_order']);

    $smarty->assign('os_unconfirmed',   OS_UNCONFIRMED);
    $smarty->assign('cs_await_pay',     CS_AWAIT_PAY);
    $smarty->assign('cs_await_ship',    CS_AWAIT_SHIP);
    $smarty->assign('full_page',        1);

    $smarty->assign('back_list',   $result['back']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_update_time', '<img src="images/sort_desc.png">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('back_list.htm');
}

/*------------------------------------------------------ */
//-- 搜索、排序、分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'back_query')
{
    /* 检查权限 */
    admin_priv('back_view');

    $result = back_list();

    $smarty->assign('back_list',   $result['back']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    $sort_flag = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch('back_list.htm'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 退货单详细
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'back_info')
{
    /* 检查权限 */
    admin_priv('back_view');

    $back_id = intval(trim($_REQUEST['back_id']));

    /* 根据发货单id查询发货单信息 */
    if (!empty($back_id))
    {
        $back_order = back_order_info($back_id);
    }
    else
    {
        die('order does not exist');
    }

    /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
    $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
    $agency_id = $db->getOne($sql);
    if ($agency_id > 0)
    {
        if ($back_order['agency_id'] != $agency_id)
        {
            sys_msg($_LANG['priv_error']);
        }

        /* 取当前办事处信息*/
        $sql = "SELECT agency_name FROM " . $ecs->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
        $agency_name = $db->getOne($sql);
        $back_order['agency_name'] = $agency_name;
    }

    /* 取得用户名 */
    if ($back_order['user_id'] > 0)
    {
        $user = user_info($back_order['user_id']);
        if (!empty($user))
        {
            $back_order['user_name'] = $user['user_name'];
        }
    }

    /* 取得区域名 */
    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
        "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
        "FROM " . $ecs->table('order_info') . " AS o " .
        "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
        "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
        "WHERE o.order_id = '" . $back_order['order_id'] . "'";
    $back_order['region'] = $db->getOne($sql);

    /* 是否保价 */
    $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

    /* 取得发货单商品 */
    $goods_sql = "SELECT *
                  FROM " . $ecs->table('back_goods') . "
                  WHERE back_id = " . $back_order['back_id'];
    $goods_list = $GLOBALS['db']->getAll($goods_sql);

    /* 是否存在实体商品 */
    $exist_real_goods = 0;
    if ($goods_list)
    {
        foreach ($goods_list as $value)
        {
            if ($value['is_real'])
            {
                $exist_real_goods++;
            }
        }
    }

    /* 模板赋值 */
    $smarty->assign('back_order', $back_order);
    $smarty->assign('exist_real_goods', $exist_real_goods);
    $smarty->assign('goods_list', $goods_list);
    $smarty->assign('back_id', $back_id); // 发货单id

    /* 显示模板 */
    $smarty->assign('ur_here', $_LANG['back_operate'] . $_LANG['detail']);
    $smarty->assign('action_link', array('href' => 'order.php?act=back_list&' . list_link_postfix(), 'text' => $_LANG['10_back_order']));
    assign_query_info();
    $smarty->display('back_info.htm');
    exit; //
}

/*------------------------------------------------------ */
//-- 修改订单（处理提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'step_post')
{
    /* 检查权限 */
    admin_priv('order_edit');

    /* 取得参数 step */
    $step_list = array('user', 'edit_goods', 'add_goods', 'goods', 'consignee', 'shipping', 'payment', 'other', 'money', 'invoice');
    $step = isset($_REQUEST['step']) && in_array($_REQUEST['step'], $step_list) ? $_REQUEST['step'] : 'user';

    /* 取得参数 order_id */
    $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
    if ($order_id > 0)
    {
        $old_order = order_info($order_id, 0, $_SESSION['suppliers_id']);
        /* 如果订单不存在，退出 */
        if (empty($old_order))
        {
            die('order does not exist');
        }
    }

    /* 取得参数 step_act 添加还是编辑 */
    $step_act = isset($_REQUEST['step_act']) ? $_REQUEST['step_act'] : 'add';

    /* 插入订单信息 */
    if ('user' == $step)
    {
        /* 取得参数：user_id */
        $user_id = ($_POST['anonymous'] == 1) ? 0 : intval($_POST['user']);

        /* 插入新订单，状态为无效 */
        $order = array(
            'user_id'           => $user_id,
            'add_time'          => gmtime(),
            'order_status'      => OS_INVALID,
            'shipping_status'   => SS_UNSHIPPED,
            'pay_status'        => PS_UNPAYED,
            'from_ad'           => 0,
            'referer'           => $_LANG['admin'],
            'suppliers_id'      => (int)$_SESSION['suppliers_id'],
        );

        do
        {
            $order['order_sn'] = get_order_sn();
            if ($db->autoExecute($ecs->table('order_info'), $order, 'INSERT', '', 'SILENT'))
            {
                break;
            }
            else
            {
                if ($db->errno() != 1062)
                {
                    die($db->error());
                }
            }
        }
        while (true); // 防止订单号重复

        $order_id = $db->insert_id();

        /* todo 记录日志 */
        admin_log($order['order_sn'], 'add', 'order');

        /* 插入 pay_log */
        $sql = 'INSERT INTO ' . $ecs->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
            " VALUES ('$order_id', 0, '" . PAY_ORDER . "', 0)";
        $db->query($sql);

        // 请求crm
        update_order_crm($order['order_sn']);
        /* 下一步 */
        ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }
    /* 编辑商品信息 */
    elseif ('edit_goods' == $step)
    {
        if (isset($_POST['rec_id']))
        {
            foreach ($_POST['rec_id'] AS $key => $rec_id)
            {
                $sql = "SELECT goods_number ".
                    'FROM ' . $GLOBALS['ecs']->table('goods') .
                    "WHERE goods_id =".$_POST['goods_id'][$key];
                /* 取得参数 */
                $goods_price = floatval($_POST['goods_price'][$key]);
                $goods_number = intval($_POST['goods_number'][$key]);
                $goods_attr = $_POST['goods_attr'][$key];
                $product_id = intval($_POST['product_id'][$key]);
                if($product_id)
                {

                    $sql = "SELECT product_number ".
                        'FROM ' . $GLOBALS['ecs']->table('products') .
                        " WHERE product_id =".$_POST['product_id'][$key];
                }
                $goods_number_all = $db->getOne($sql);
                if($goods_number_all>=$goods_number)
                {
                    /* 修改 */
                    $sql = "UPDATE " . $ecs->table('order_goods') .
                        " SET goods_price = '$goods_price', " .
                        "goods_number = '$goods_number', " .
                        "goods_attr = '$goods_attr' " .
                        "WHERE rec_id = '$rec_id' LIMIT 1";
                    $db->query($sql);
                }
                else
                {
                    sys_msg($_LANG['goods_num_err']);


                }
            }

            /* 更新商品总金额和订单总金额 */
            $goods_amount = order_amount($order_id);
            update_order($order_id, array('goods_amount' => $goods_amount));
            update_order_amount($order_id);

            /* 更新 pay_log */
            update_pay_log($order_id);

            /* todo 记录日志 */
            $sn = $old_order['order_sn'];
            $new_order = order_info($order_id);
            if ($old_order['total_fee'] != $new_order['total_fee'])
            {
                $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
            }
            admin_log($sn, 'edit', 'order');
        }
        // 请求crm
        update_order_crm($old_order['order_sn']);
        /* 跳回订单商品 */
        ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }
    /* 添加商品 */
    elseif ('add_goods' == $step)
    {
        /* 取得参数 */
        $goods_id = intval($_POST['goodslist']);
        $goods_price = $_POST['add_price'] != 'user_input' ? floatval($_POST['add_price']) : floatval($_POST['input_price']);
        $goods_attr = '0';
        for ($i = 0; $i < $_POST['spec_count']; $i++)
        {
            if (is_array($_POST['spec_' . $i]))
            {
                $temp_array = $_POST['spec_' . $i];
                $temp_array_count = count($_POST['spec_' . $i]);
                for ($j = 0; $j < $temp_array_count; $j++)
                {
                    if($temp_array[$j]!==NULL)
                    {
                        $goods_attr .= ',' . $temp_array[$j];
                    }
                }
            }
            else
            {
                if($_POST['spec_' . $i]!==NULL)
                {
                    $goods_attr .= ',' . $_POST['spec_' . $i];
                }
            }
        }
        $goods_number = $_POST['add_number'];
        $attr_list = $goods_attr;

        $goods_attr = explode(',',$goods_attr);
        $k   =   array_search(0,$goods_attr);
        unset($goods_attr[$k]);


        $sql = "SELECT attr_value ".
            'FROM ' . $GLOBALS['ecs']->table('goods_attr') .
            "WHERE goods_attr_id in($attr_list)";
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $attr_value[] = $row['attr_value'];
        }

        $attr_value = implode(",",$attr_value);

        $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '$goods_id' LIMIT 0, 1";
        $prod = $GLOBALS['db']->getRow($sql);


        if (is_spec($goods_attr) && !empty($prod))
        {
            $product_info = get_products_info($_REQUEST['goodslist'], $goods_attr);
        }

        //商品存在规格 是货品 检查该货品库存
        if (is_spec($goods_attr) && !empty($prod))
        {
            if (!empty($goods_attr))
            {
                /* 取规格的货品库存 */
                if ($goods_number > $product_info['product_number'])
                {
                    $url = "order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods";

                    echo '<a href="'.$url.'">'.$_LANG['goods_num_err'] .'</a>';
                    exit;

                    return false;
                }
            }
        }

        if(is_spec($goods_attr) && !empty($prod))
        {
            /* 插入订单商品 */
            $sql = "INSERT INTO " . $ecs->table('order_goods') .
                "(order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, " .
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) " .
                "SELECT '$order_id', goods_id, goods_name, goods_sn, " .$product_info['product_id'].", ".
                "'$goods_number', market_price, '$goods_price', '" .$attr_value . "', " .
                "is_real, extension_code, 0, 0 , '".implode(',',$goods_attr)."' " .
                "FROM " . $ecs->table('goods') .
                " WHERE goods_id = '$goods_id' LIMIT 1";
        }
        else
        {
            $sql = "INSERT INTO " . $ecs->table('order_goods') .
                " (order_id, goods_id, goods_name, goods_sn, " .
                "goods_number, market_price, goods_price, goods_attr, " .
                "is_real, extension_code, parent_id, is_gift)" .
                "SELECT '$order_id', goods_id, goods_name, goods_sn, " .
                "'$goods_number', market_price, '$goods_price', '" . $attr_value. "', " .
                "is_real, extension_code, 0, 0 " .
                "FROM " . $ecs->table('goods') .
                " WHERE goods_id = '$goods_id' LIMIT 1";
        }
        $db->query($sql);

        if($suppliers_id == 0){
            $sql = "SELECT suppliers_id FROM " . $GLOBALS['ecs']->table('goods') ." WHERE goods_id =".$goods_id;
            $suppliers_id = $db->getOne($sql);
            $sql = "UPDATE " . $ecs->table('order_info') . " SET suppliers_id = '$suppliers_id'" . " WHERE order_id = '$order_id' LIMIT 1";
            $db->query($sql);
        }
        /* 如果使用库存，且下订单时减库存，则修改库存 */
        if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
        {

            //（货品）
            if (!empty($product_info['product_id']))
            {
                $sql = "UPDATE " . $ecs->table('products') . "
                                    SET product_number = product_number - " . $goods_number . "
                                    WHERE product_id = " . $product_info['product_id'];

                $db->query($sql);
            }


            $sql = "UPDATE " . $ecs->table('goods') .
                " SET `goods_number` = goods_number - '" . $goods_number . "' " .
                " WHERE `goods_id` = '" . $goods_id . "' LIMIT 1";
            $db->query($sql);
        }

        /* 更新商品总金额和订单总金额 */
        update_order($order_id, array('goods_amount' => order_amount($order_id)));
        update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');
        // 请求crm
        update_order_crm($old_order['order_sn']);
        /* 跳回订单商品 */
        ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }
    /* 商品 */
    elseif ('goods' == $step)
    {
        /* 下一步 */
        if (isset($_POST['next']))
        {
            ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=consignee\n");
            exit;
        }
        /* 完成 */
        elseif (isset($_POST['finish']))
        {
            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {
                // 请求crm
                update_order_crm($old_order['order_sn']);
                /* 跳转到订单详情 */
                ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }
    }
    /* 保存收货人信息 */
    elseif ('consignee' == $step)
    {
        if($old_order['shipping_status'] > 0){
            sys_msg('订单发货中或已发货，不能修改收货地址');
        }
        /* 保存订单 */
        $order = $_POST;
        $order['agency_id'] = get_agency_by_regions(array($order['country'], $order['province'], $order['city'], $order['district']));
        update_order($order_id, $order);

        /* 该订单所属办事处是否变化 */
        $agency_changed = $old_order['agency_id'] != $order['agency_id'];
        // 请求crm
        update_order_crm($old_order['order_sn']);
        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        admin_log($sn, 'edit', 'order');

        if (isset($_POST['next']))
        {
            /* 下一步 */
            if (exist_real_goods($order_id))
            {
                /* 存在实体商品，去配送方式 */
                ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=shipping\n");
                exit;
            }
            else
            {
                /* 不存在实体商品，去支付方式 */
                ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=payment\n");
                exit;
            }
        }
        elseif (isset($_POST['finish']))
        {
            /* 如果是编辑且存在实体商品，检查收货人地区的改变是否影响原来选的配送 */
            if ('edit' == $step_act && exist_real_goods($order_id))
            {
                $order = order_info($order_id);

                /* 取得可用配送方式 */
                $region_id_list = array(
                    $order['country'], $order['province'], $order['city'], $order['district']
                );
                $shipping_list = available_shipping_list($region_id_list);

                /* 判断订单的配送是否在可用配送之内 */
                $exist = false;
                foreach ($shipping_list AS $shipping)
                {
                    if ($shipping['shipping_id'] == $order['shipping_id'])
                    {
                        $exist = true;
                        break;
                    }
                }

                /* 如果不在可用配送之内，提示用户去修改配送 */
                if (!$exist)
                {
                    // 修改配送为空，配送费和保价费为0
                    update_order($order_id, array('shipping_id' => 0, 'shipping_name' => ''));
//                    $links[] = array('text' => $_LANG['step']['shipping'], 'href' => 'order.php?act=edit&order_id=' . $order_id . '&step=shipping');
//                    sys_msg($_LANG['continue_shipping'], 1, $links);
                }
            }

            /* 完成 */
            if ($agency_changed)
            {
                ecs_header("Location: order.php?act=list\n");
            }
            else
            {
                ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            }
            exit;
        }
    }
    /* 保存配送信息 */
    elseif ('shipping' == $step)
    {
        /* 如果不存在实体商品，退出 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 取得订单信息 */
        $order_info = order_info($order_id);
        $region_id_list = array($order_info['country'], $order_info['province'], $order_info['city'], $order_info['district']);

        /* 保存订单 */
        $shipping_id = $_POST['shipping'];
        $shipping = shipping_area_info($shipping_id, $region_id_list);
        $weight_amount = order_weight_price($order_id);
        $shipping_fee = shipping_fee($shipping['shipping_code'], $shipping['configure'], $weight_amount['weight'], $weight_amount['amount'], $weight_amount['number']);
        $order = array(
            'shipping_id' => $shipping_id,
            'shipping_name' => addslashes($shipping['shipping_name']),
            'shipping_fee' => $shipping_fee
        );

        if (isset($_POST['insure']))
        {
            /* 计算保价费 */
            $order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], order_amount($order_id), $shipping['insure']);
        }
        else
        {
            $order['insure_fee'] = 0;
        }
        update_order($order_id, $order);
        update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* 清除首页缓存：发货单查询 */
        clear_cache_files('index.dwt');

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');
        // 请求crm
        update_order_crm($old_order['order_sn']);
        if (isset($_POST['next']))
        {
            /* 下一步 */
            ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=payment\n");
            exit;
        }
        elseif (isset($_POST['finish']))
        {
            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 如果是编辑且配送不支持货到付款且原支付方式是货到付款 */
            if ('edit' == $step_act && $shipping['support_cod'] == 0)
            {
                $payment = payment_info($order['pay_id']);
                if ($payment['is_cod'] == 1)
                {
                    /* 修改支付为空 */
                    update_order($order_id, array('pay_id' => 0, 'pay_name' => ''));
                    $msgs[]     = $_LANG['continue_payment'];
                    $links[]    = array('text' => $_LANG['step']['payment'], 'href' => 'order.php?act=' . $step_act . '&order_id=' . $order_id . '&step=payment');
                }
            }

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {
                /* 完成 */
                ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }
    }
    /* 保存支付信息 */
    elseif ('payment' == $step)
    {
        /* 取得支付信息 */
        $pay_id = $_POST['payment'];
        $payment = payment_info($pay_id);

        /* 计算支付费用 */
        $order_amount = order_amount($order_id);
        if ($payment['is_cod'] == 1)
        {
            $order = order_info($order_id);
            $region_id_list = array(
                $order['country'], $order['province'], $order['city'], $order['district']
            );
            $shipping = shipping_area_info($order['shipping_id'], $region_id_list);
            $pay_fee = pay_fee($pay_id, $order_amount, $shipping['pay_fee']);
        }
        else
        {
            $pay_fee = pay_fee($pay_id, $order_amount);
        }

        /* 保存订单 */
        $order = array(
            'pay_id' => $pay_id,
            'pay_name' => addslashes($payment['pay_name']),
            'pay_fee' => $pay_fee
        );
        update_order($order_id, $order);
        update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');
        // 请求crm
        update_order_crm($old_order['order_sn']);
        if (isset($_POST['next']))
        {
            /* 下一步 */
            ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=other\n");
            exit;
        }
        elseif (isset($_POST['finish']))
        {
            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {
                /* 完成 */
                ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }
    }
    elseif ('other' == $step)
    {
        /* 保存订单 */
        $order = array();
        if (isset($_POST['pack']) && $_POST['pack'] > 0)
        {
            $pack               = pack_info($_POST['pack']);
            $order['pack_id']   = $pack['pack_id'];
            $order['pack_name'] = addslashes($pack['pack_name']);
            $order['pack_fee']  = $pack['pack_fee'];
        }
        else
        {
            $order['pack_id']   = 0;
            $order['pack_name'] = '';
            $order['pack_fee']  = 0;
        }
        if (isset($_POST['card']) && $_POST['card'] > 0)
        {
            $card               = card_info($_POST['card']);
            $order['card_id']   = $card['card_id'];
            $order['card_name'] = addslashes($card['card_name']);
            $order['card_fee']  = $card['card_fee'];
            $order['card_message'] = $_POST['card_message'];
        }
        else
        {
            $order['card_id']   = 0;
            $order['card_name'] = '';
            $order['card_fee']  = 0;
            $order['card_message'] = '';
        }
        $order['inv_type']      = $_POST['inv_type'];
        $order['inv_payee']     = $_POST['inv_payee'];
        $order['inv_content']   = $_POST['inv_content'];
        $order['how_oos']       = $_POST['how_oos'];
        $order['postscript']    = $_POST['postscript'];
        $order['to_buyer']      = $_POST['to_buyer'];
        update_order($order_id, $order);
        update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        admin_log($sn, 'edit', 'order');
        // 请求crm
        update_order_crm($old_order['order_sn']);
        if (isset($_POST['next']))
        {
            /* 下一步 */
            ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=money\n");
            exit;
        }
        elseif (isset($_POST['finish']))
        {
            /* 完成 */
            ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }
    }
    elseif ('money' == $step)
    {
        /* 取得订单信息 */
        $old_order = order_info($order_id);
        if ($old_order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($old_order['user_id']);
        }

        /* 保存信息 */
        $order['goods_amount']  = $old_order['goods_amount'];
        $order['discount']      = isset($_POST['discount']) && floatval($_POST['discount']) >= 0 ? round(floatval($_POST['discount']), 2) : 0;
        $order['tax']           = round(floatval($_POST['tax']), 2);
        $order['shipping_fee']  = isset($_POST['shipping_fee']) && floatval($_POST['shipping_fee']) >= 0 ? round(floatval($_POST['shipping_fee']), 2) : 0;
        $order['insure_fee']    = isset($_POST['insure_fee']) && floatval($_POST['insure_fee']) >= 0 ? round(floatval($_POST['insure_fee']), 2) : 0;
        $order['pay_fee']       = floatval($_POST['pay_fee']) >= 0 ? round(floatval($_POST['pay_fee']), 2) : 0;
        $order['pack_fee']      = isset($_POST['pack_fee']) && floatval($_POST['pack_fee']) >= 0 ? round(floatval($_POST['pack_fee']), 2) : 0;
        $order['card_fee']      = isset($_POST['card_fee']) && floatval($_POST['card_fee']) >= 0 ? round(floatval($_POST['card_fee']), 2) : 0;

        $order['money_paid']    = $old_order['money_paid'];
        $order['surplus']       = 0;
        //$order['integral']      = 0;
        $order['integral']=intval($_POST['integral']) >= 0 ? intval($_POST['integral']) : 0;
        $order['integral_money']= 0;
        $order['bonus_id']      = 0;
        $order['bonus']         = 0;

        /* 计算待付款金额 */
        $order['order_amount']  = $order['goods_amount'] - $order['discount']
            + $order['tax']
            + $order['shipping_fee']
            + $order['insure_fee']
            + $order['pay_fee']
            + $order['pack_fee']
            + $order['card_fee']
            - $order['money_paid'];
        if ($order['order_amount'] > 0)
        {
            if ($old_order['user_id'] > 0)
            {
                /* 如果选择了红包，先使用红包支付 */
                if ($_POST['bonus_id'] > 0)
                {
                    /* todo 检查红包是否可用 */
                    $order['bonus_id']      = $_POST['bonus_id'];
                    $bonus                  = bonus_info($_POST['bonus_id']);
                    $order['bonus']         = $bonus['type_money'];

                    $order['order_amount']  -= $order['bonus'];
                }

                /* 使用红包之后待付款金额仍大于0 */
                if ($order['order_amount'] > 0)
                {
                    if($old_order['extension_code']!='exchange_goods')
                    {
                        /* 如果设置了积分，再使用积分支付 */
                        if (isset($_POST['integral']) && intval($_POST['integral']) > 0)
                        {
                            /* 检查积分是否足够 */
                            $order['integral']          = intval($_POST['integral']);
                            $order['integral_money']    = value_of_integral(intval($_POST['integral']));
                            if ($old_order['integral'] + $user['pay_points'] < $order['integral'])
                            {
                                sys_msg($_LANG['pay_points_not_enough']);
                            }

                            $order['order_amount'] -= $order['integral_money'];
                        }
                    }
                    else
                    {
                        if (intval($_POST['integral']) > $user['pay_points']+$old_order['integral'])
                        {
                            sys_msg($_LANG['pay_points_not_enough']);
                        }

                    }
                    if ($order['order_amount'] > 0)
                    {
                        /* 如果设置了余额，再使用余额支付 */
                        if (isset($_POST['surplus']) && floatval($_POST['surplus']) >= 0)
                        {
                            /* 检查余额是否足够 */
                            $order['surplus'] = round(floatval($_POST['surplus']), 2);
                            if ($old_order['surplus'] + $user['user_money'] + $user['credit_line'] < $order['surplus'])
                            {
                                sys_msg($_LANG['user_money_not_enough']);
                            }

                            /* 如果红包和积分和余额足以支付，把待付款金额改为0，退回部分积分余额 */
                            $order['order_amount'] -= $order['surplus'];
                            if ($order['order_amount'] < 0)
                            {
                                $order['surplus']       += $order['order_amount'];
                                $order['order_amount']  = 0;
                            }
                        }
                    }
                    else
                    {
                        /* 如果红包和积分足以支付，把待付款金额改为0，退回部分积分 */
                        $order['integral_money']    += $order['order_amount'];
                        $order['integral']          = integral_of_value($order['integral_money']);
                        $order['order_amount']      = 0;
                    }
                }
                else
                {
                    /* 如果红包足以支付，把待付款金额设为0 */
                    $order['order_amount'] = 0;
                }
            }
        }

        update_order($order_id, $order);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');
        // 请求crm
        update_order_crm($old_order['order_sn']);
        /* 如果余额、积分、红包有变化，做相应更新 */
        if ($old_order['user_id'] > 0)
        {
            $user_money_change = $old_order['surplus'] - $order['surplus'];
            if ($user_money_change != 0)
            {
                log_account_change($user['user_id'], $user_money_change, 0, 0, 0, sprintf($_LANG['change_use_surplus'], $old_order['order_sn']));
            }

            $pay_points_change = $old_order['integral'] - $order['integral'];
            if ($pay_points_change != 0)
            {
                log_account_change($user['user_id'], 0, 0, 0, $pay_points_change, sprintf($_LANG['change_use_integral'], $old_order['order_sn']));
            }

            if ($old_order['bonus_id'] != $order['bonus_id'])
            {
                if ($old_order['bonus_id'] > 0)
                {
                    $sql = "UPDATE " . $ecs->table('user_bonus') .
                        " SET used_time = 0, order_id = 0 " .
                        "WHERE bonus_id = '$old_order[bonus_id]' LIMIT 1";
                    $db->query($sql);
                }

                if ($order['bonus_id'] > 0)
                {
                    $sql = "UPDATE " . $ecs->table('user_bonus') .
                        " SET used_time = '" . gmtime() . "', order_id = '$order_id' " .
                        "WHERE bonus_id = '$order[bonus_id]' LIMIT 1";
                    $db->query($sql);
                }
            }
        }

        if (isset($_POST['finish']))
        {
            /* 完成 */
            if ($step_act == 'add')
            {
                /* 订单改为已确认，（已付款） */
                $arr['order_status'] = OS_CONFIRMED;
                $arr['confirm_time'] = gmtime();
                if ($order['order_amount'] <= 0)
                {
                    $arr['pay_status']  = PS_PAYED;
                    $arr['pay_time']    = gmtime();
                }
                update_order($order_id, $arr);
            }

            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {
                ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }
    }
    /* 保存发货后的配送方式和发货单号 */
    elseif ('invoice' == $step)
    {
        /* 如果不存在实体商品，退出 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 保存订单 */
//        $shipping_id    = $_POST['shipping'];
//        $shipping       = shipping_info($shipping_id);
//        $invoice_no     = str_replace(',', '<br>', $invoice_no);

        $shipping_name     = !empty($_POST['shipping_name']) ? htmlspecialchars(trim($_POST['shipping_name'])) : '';
        $order['invoice_no']     = !empty($_POST['invoice_no']) ? htmlspecialchars(trim($_POST['invoice_no'])) : '';
        $sql = "select * from " . $GLOBALS['ecs']->table('shipping') . " where shipping_name = '" . $shipping_name . "'";
        $shippp_arr = $db->getRow($sql);
        if (empty($shippp_arr)) {
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('shipping') . " (shipping_name,enabled) VALUES ('" . $shipping_name . "',1)";
            $db->query($sql);
            $order['shipping_id'] = $db->insert_id($sql);
            $order['shipping_name'] = $shipping_name;
        } else {
            $order['shipping_id'] = $shippp_arr['shipping_id'];
            $order['shipping_name'] = $shippp_arr['shipping_name'];
        }
        if($old_order['shipping_status'] == SS_SHIPPED_ING){
            $order['shipping_status'] = SS_SHIPPED;
            $order['shipping_time'] = time();
        }
//        $order = array(
//            'shipping_id'   => $shipping_id,
//            'shipping_name' => addslashes($shipping['shipping_name']),
//            'invoice_no'    => $invoice_no
//        );
        update_order($order_id, $order);

        /* 记录log */
        if($old_order['shipping_status'] == SS_SHIPPED_ING){
            $action_note = '编辑导入【' . $shipping_name . '】' . trim($order['invoice_no'] );
            order_action($old_order['order_sn'], $old_order['order_status'], SS_SHIPPED_ING, $old_order['pay_status'], $action_note, $_SESSION['admin_name']);
        }else{
            $action_note = '修改配送方式【' . $shipping_name . '】' . trim($order['invoice_no'] );
            order_action($old_order['order_sn'], $old_order['order_status'], $old_order['shipping_status'], $old_order['pay_status'], $action_note, $_SESSION['admin_name']);
        }


        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        admin_log($sn, 'edit', 'order');
        // 请求crm
        update_order_crm($old_order['order_sn']);
        if (isset($_POST['finish']))
        {
            ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }
    }
}

/*------------------------------------------------------ */
//-- 修改订单（载入页面）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('order_edit');
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    /* 取得参数 order_id */
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $smarty->assign('order_id', $order_id);

    /* 取得参数 step */
    $step_list = array('user', 'goods', 'consignee', 'shipping', 'payment', 'other', 'money');
    $step = isset($_GET['step']) && in_array($_GET['step'], $step_list) ? $_GET['step'] : 'user';
    $smarty->assign('step', $step);

    /* 取得参数 act */
    $act = $_GET['act'];
    $smarty->assign('ur_here',$_LANG['add_order']);
    $smarty->assign('step_act', $act);

    /* 取得订单信息 */
    if ($order_id > 0)
    {
        $suppliers_id = $_SESSION['suppliers_id'];
        $order = order_info($order_id, 0, $suppliers_id);
        if(empty($order)){
            die('order does not exist');
        }

        /* 发货单格式化 */
        $order['invoice_no'] = str_replace('<br>', ',', $order['invoice_no']);

        /* 如果已发货，就不能修改订单了（配送方式和发货单号除外） */
        if ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED || $order['shipping_status'] == SS_SHIPPED_ING)
        {
            if ($step != 'shipping')
            {
                sys_msg($_LANG['cannot_edit_order_shipped']);
            }
            else
            {
                $step = 'invoice';
                $smarty->assign('step', $step);
            }
        }

        $smarty->assign('order', $order);
    }
    else
    {
        if ($act != 'add' || $step != 'user')
        {
            die('invalid params');
        }
    }

    /* 选择会员 */
    if ('user' == $step)
    {
        // 无操作
    }

    /* 增删改商品 */
    elseif ('goods' == $step)
    {
        /* 取得订单商品 */
        $goods_list = order_goods($order_id);
        if (!empty($goods_list))
        {
            foreach ($goods_list AS $key => $goods)
            {
                /* 计算属性数 */
                $attr = $goods['goods_attr'];
                if ($attr == '')
                {
                    $goods_list[$key]['rows'] = 1;
                }
                else
                {
                    $goods_list[$key]['rows'] = count(explode(chr(13), $attr));
                }
            }
        }

        $smarty->assign('goods_list', $goods_list);

        /* 取得商品总金额 */
        $smarty->assign('goods_amount', order_amount($order_id));
    }

    // 设置收货人
    elseif ('consignee' == $step)
    {
        /* 查询是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);
        $smarty->assign('exist_real_goods', $exist_real_goods);

        /* 取得收货地址列表 */
        if ($order['user_id'] > 0)
        {
            $smarty->assign('address_list', address_list($order['user_id']));

            $address_id = isset($_REQUEST['address_id']) ? intval($_REQUEST['address_id']) : 0;
            if ($address_id > 0)
            {
                $address = address_info($address_id);
                if ($address)
                {
                    $order['consignee']     = $address['consignee'];
                    $order['country']       = $address['country'];
                    $order['province']      = $address['province'];
                    $order['city']          = $address['city'];
                    $order['district']      = $address['district'];
                    $order['email']         = $address['email'];
                    $order['address']       = $address['address'];
                    $order['zipcode']       = $address['zipcode'];
                    $order['tel']           = $address['tel'];
                    $order['mobile']        = $address['mobile'];
                    $order['sign_building'] = $address['sign_building'];
                    $order['best_time']     = $address['best_time'];
                    $smarty->assign('order', $order);
                }
            }
        }

        if ($exist_real_goods)
        {
            /* 取得国家 */
            $smarty->assign('country_list', get_regions());
            if ($order['country'] > 0)
            {
                /* 取得省份 */
                $smarty->assign('province_list', get_regions(1, $order['country']));
                if ($order['province'] > 0)
                {
                    /* 取得城市 */
                    $smarty->assign('city_list', get_regions(2, $order['province']));
                    if ($order['city'] > 0)
                    {
                        /* 取得区域 */
                        $smarty->assign('district_list', get_regions(3, $order['city']));
                    }
                }
            }
        }
    }

    // 选择配送方式
    elseif ('shipping' == $step)
    {
        /* 如果不存在实体商品 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 取得可用的配送方式列表 */
        $region_id_list = array(
            $order['country'], $order['province'], $order['city'], $order['district']
        );
        $shipping_list = available_shipping_list($region_id_list);

        /* 取得配送费用 */
        $total = order_weight_price($order_id);
        foreach ($shipping_list AS $key => $shipping)
        {
            $shipping['configure'] = unserialize($shipping['configure']);
            $shipping_fee = shipping_fee($shipping['shipping_code'],
                $shipping['configure'], $total['weight'], $total['amount'], $total['number']);
            $shipping_list[$key]['shipping_fee'] = $shipping_fee;
            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
            $shipping_list[$key]['free_money'] = price_format($shipping['configure']['free_money']);
        }
        $smarty->assign('shipping_list', $shipping_list);
    }

    // 选择支付方式
    elseif ('payment' == $step)
    {
        /* 取得可用的支付方式列表 */
        if (exist_real_goods($order_id))
        {
            /* 存在实体商品 */
            $region_id_list = array(
                $order['country'], $order['province'], $order['city'], $order['district']
            );
            $shipping_area = shipping_area_info($order['shipping_id'], $region_id_list);
            $pay_fee = ($shipping_area['support_cod'] == 1) ? $shipping_area['pay_fee'] : 0;

            $payment_list = available_payment_list($shipping_area['support_cod'], $pay_fee);
        }
        else
        {
            /* 不存在实体商品 */
            $payment_list = available_payment_list(false);
        }

        /* 过滤掉使用余额支付 */
        foreach ($payment_list as $key => $payment)
        {
            if ($payment['pay_code'] == 'balance')
            {
                unset($payment_list[$key]);
            }
        }
        $smarty->assign('payment_list', $payment_list);
    }

    // 选择包装、贺卡
    elseif ('other' == $step)
    {
        /* 查询是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);
        $smarty->assign('exist_real_goods', $exist_real_goods);

        if ($exist_real_goods)
        {
            /* 取得包装列表 */
            $smarty->assign('pack_list', pack_list());

            /* 取得贺卡列表 */
            $smarty->assign('card_list', card_list());
        }
    }

    // 费用
    elseif ('money' == $step)
    {
        /* 查询是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);
        $smarty->assign('exist_real_goods', $exist_real_goods);

        /* 取得用户信息 */
        if ($order['user_id'] > 0)
        {
            $user = user_info($order['user_id']);

            /* 计算可用余额 */
            $smarty->assign('available_user_money', $order['surplus'] + $user['user_money']);

            /* 计算可用积分 */
            $smarty->assign('available_pay_points', $order['integral'] + $user['pay_points']);

            /* 取得用户可用红包 */
            $user_bonus = user_bonus($order['user_id'], $order['goods_amount']);
            if ($order['bonus_id'] > 0)
            {
                $bonus = bonus_info($order['bonus_id']);
                $user_bonus[] = $bonus;
            }
            $smarty->assign('available_bonus', $user_bonus);
        }
    }

    // 发货后修改配送方式和发货单号
    elseif ('invoice' == $step)
    {
        /* 如果不存在实体商品 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 取得可用的配送方式列表 */
//        $region_id_list = array(
//            $order['country'], $order['province'], $order['city'], $order['district']
//        );
//        $shipping_list = available_shipping_list($region_id_list);

//        /* 取得配送费用 */
//        $total = order_weight_price($order_id);
//        foreach ($shipping_list AS $key => $shipping)
//        {
//            $shipping_fee = shipping_fee($shipping['shipping_code'],
//                unserialize($shipping['configure']), $total['weight'], $total['amount'], $total['number']);
//            $shipping_list[$key]['shipping_fee'] = $shipping_fee;
//            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
//            $shipping_list[$key]['free_money'] = price_format($shipping['configure']['free_money']);
//        }
//        $smarty->assign('shipping_list', $shipping_list);
    }

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_step.htm');
}

/*------------------------------------------------------ */
//-- 处理
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'process')
{
    $order_id = intval($_GET['order_id']);
    $order = order_info($order_id, 0, $_SESSION['suppliers_id']);
    /* 如果订单不存在，退出 */
    if (empty($order))
    {
        die('order does not exist');
    }
    /* 取得参数 func */
    $func = isset($_GET['func']) ? $_GET['func'] : '';

    /* 删除订单商品 */
    if ('drop_order_goods' == $func)
    {
        /* 检查权限 */
        admin_priv('order_edit');

        /* 取得参数 */
        $rec_id = intval($_GET['rec_id']);
        $step_act = $_GET['step_act'];
        $order_id = intval($_GET['order_id']);

        /* 如果使用库存，且下订单时减库存，则修改库存 */
        if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
        {
            $goods = $db->getRow("SELECT goods_id, goods_number FROM " . $ecs->table('order_goods') . " WHERE rec_id = " . $rec_id );
            $sql = "UPDATE " . $ecs->table('goods') .
                " SET `goods_number` = goods_number + '" . $goods['goods_number'] . "' " .
                " WHERE `goods_id` = '" . $goods['goods_id'] . "' LIMIT 1";
            $db->query($sql);
        }

        /* 删除 */
        $sql = "DELETE FROM " . $ecs->table('order_goods') .
            " WHERE rec_id = '$rec_id' LIMIT 1";
        $db->query($sql);

        /* 更新商品总金额和订单总金额 */
        update_order($order_id, array('goods_amount' => order_amount($order_id)));
        update_order_amount($order_id);

        /* 跳回订单商品 */
        ecs_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }

    /* 取消刚添加或编辑的订单 */
    elseif ('cancel_order' == $func)
    {
        $step_act = $_GET['step_act'];
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($step_act == 'add')
        {
            /* 如果是添加，删除订单，返回订单列表 */
            if ($order_id > 0)
            {
                $sql = "DELETE FROM " . $ecs->table('order_info') .
                    " WHERE order_id = '$order_id' LIMIT 1";
                $db->query($sql);
            }
            ecs_header("Location: order.php?act=list\n");
            exit;
        }
        else
        {
            /* 如果是编辑，返回订单信息 */
            ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }
    }

    /* 编辑订单时由于订单已付款且金额减少而退款 */
    elseif ('refund' == $func)
    {
        /* 处理退款 */
        $order_id       = $_REQUEST['order_id'];
        $refund_type    = $_REQUEST['refund'];
        $refund_note    = $_REQUEST['refund_note'];
        $refund_amount  = $_REQUEST['refund_amount'];
        $order          = order_info($order_id);
        order_refund($order, $refund_type, $refund_note, $refund_amount);

        /* 修改应付款金额为0，已付款金额减少 $refund_amount */
        update_order($order_id, array('order_amount' => 0, 'money_paid' => $order['money_paid'] - $refund_amount));

        /* 返回订单详情 */
        ecs_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
        exit;
    }

    /* 载入退款页面 */
    elseif ('load_refund' == $func)
    {
        $refund_amount = floatval($_REQUEST['refund_amount']);
        $smarty->assign('refund_amount', $refund_amount);
        $smarty->assign('formated_refund_amount', price_format($refund_amount));

        $anonymous = $_REQUEST['anonymous'];
        $smarty->assign('anonymous', $anonymous); // 是否匿名

        $order_id = intval($_REQUEST['order_id']);
        $smarty->assign('order_id', $order_id); // 订单id

        /* 显示模板 */
        $smarty->assign('ur_here', $_LANG['refund']);
        assign_query_info();
        $smarty->display('order_refund.htm');
    }

    else
    {
        die('invalid params');
    }
}

/*------------------------------------------------------ */
//-- 合并订单
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'merge')
{
    /* 检查权限 */
    admin_priv('order_os_edit');
    $matrix = new matrix();
    $matrix->get_bind_info(array('ecos.ome'))?$smarty->assign('node_info',true):$smarty->assign('node_info',false);

    /* 取得满足条件的订单 */
    $sql = "SELECT o.order_sn, u.user_name " .
        "FROM " . $ecs->table('order_info') . " AS o " .
        "LEFT JOIN " . $ecs->table('users') . " AS u ON o.user_id = u.user_id " .
        "WHERE o.user_id > 0 " .
        "AND o.extension_code = '' " . order_query_sql('unprocessed');
    $smarty->assign('order_list', $db->getAll($sql));

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['04_merge_order']);
    $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('merge_order.htm');
}

/*------------------------------------------------------ */
//-- 订单打印模板（载入页面）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'templates')
{
    /* 检查权限 */
    admin_priv('order_os_edit');

    /* 读入订单打印模板文件 */
    $file_path    = ROOT_PATH. DATA_DIR . '/order_print.html';
    $file_content = file_get_contents($file_path);
    @fclose($file_content);

    include_once(ROOT_PATH."includes/fckeditor/fckeditor.php");

    /* 编辑器 */
    $editor = new FCKeditor('FCKeditor1');
    $editor->BasePath   = "../includes/fckeditor/";
    $editor->ToolbarSet = "Normal";
    $editor->Width      = "95%";
    $editor->Height     = "500";
    $editor->Value      = $file_content;

    $fckeditor = $editor->CreateHtml();
    $smarty->assign('fckeditor', $fckeditor);

    /* 模板赋值 */
    $smarty->assign('ur_here',      $_LANG['edit_order_templates']);
    $smarty->assign('action_link',  array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));
    $smarty->assign('act', 'edit_templates');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_templates.htm');
}
/*------------------------------------------------------ */
//-- 订单打印模板（提交修改）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_templates')
{
    /* 更新模板文件的内容 */
    $file_name = @fopen('../' . DATA_DIR . '/order_print.html', 'w+');
    @fwrite($file_name, stripslashes($_POST['FCKeditor1']));
    @fclose($file_name);

    /* 提示信息 */
    $link[] = array('text' => $_LANG['back_list'], 'href'=>'order.php?act=list');
    sys_msg($_LANG['edit_template_success'], 0, $link);
}

/*------------------------------------------------------ */
//-- 操作订单状态（载入页面）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'operate')
{
    $suppliers_id = $_SESSION['suppliers_id'];
    $order_id = '';


    /* 取得订单id（可能是多个，多个sn）和操作备注（可能没有） */
    if(!empty($_REQUEST['order_id']))
    {
        $order_id = $_REQUEST['order_id'];
        if(isset($_POST['batch']) && !empty($_POST['batch'])){
            $order = order_info(0, $order_id, $suppliers_id);
        }else{
            $order = order_info($order_id,  0, $suppliers_id);
        }
        if(empty($order)){
            die('order does not exist');
        }
    }
    $batch          = isset($_REQUEST['batch']); // 是否批处理
    $action_note    = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

    /* 确认 */
    if (isset($_POST['confirm']))
    {
        admin_priv('order_os_edit');
        $require_note   = false;
        $action         = $_LANG['op_confirm'];
        $operation      = 'confirm';
    }
    /* 付款 */
    elseif (isset($_POST['pay']))
    {
        /* 检查权限 */
        admin_priv('order_ps_edit');
        $require_note   = $_CFG['order_pay_note'] == 1;
        $action         = $_LANG['op_pay'];
        $operation      = 'pay';
    }
    /* 未付款 */
    elseif (isset($_POST['unpay']))
    {
        /* 检查权限 */
        admin_priv('order_ps_edit');

        $require_note   = $_CFG['order_unpay_note'] == 1;
        $order          = order_info($order_id);
        if ($order['money_paid'] > 0 || $order['surplus'] > 0)
        {
            $show_refund = true;
        }
        $anonymous      = $order['user_id'] == 0;
        $action         = $_LANG['op_unpay'];
        $operation      = 'unpay';
    }
    /* 配货 */
    elseif (isset($_POST['prepare']))
    {
        admin_priv('order_ss_edit');
        $require_note   = false;
        $action         = $_LANG['op_prepare'];
        $operation      = 'prepare';
    }
    /* 分单 */
    elseif (isset($_POST['ship']))
    {
        /* 查询：检查权限 */
        admin_priv('order_ss_edit');

        $order_id = intval(trim($order_id));
        $action_note = trim($action_note);

        /* 查询：根据订单id查询订单信息 */
        if (!empty($order_id))
        {
            $order = order_info($order_id);
        }
        else
        {
            die('order does not exist');
        }

        /* 查询：根据订单是否完成 检查权限 */
        if (order_finished($order))
        {
            admin_priv('order_view_finished');
        }
        else
        {
            admin_priv('order_query');
        }

        /* 查询：如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
        $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
        $agency_id = $db->getOne($sql);
        if ($agency_id > 0)
        {
            if ($order['agency_id'] != $agency_id)
            {
                sys_msg($_LANG['priv_error'], 0);
            }
        }

        /* 查询：取得用户名 */
        if ($order['user_id'] > 0)
        {
            $user = user_info($order['user_id']);
            if (!empty($user))
            {
                $order['user_name'] = $user['user_name'];
            }
        }

        /* 查询：取得区域名 */
        $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
            "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
            "FROM " . $ecs->table('order_info') . " AS o " .
            "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
            "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
            "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
            "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
            "WHERE o.order_id = '$order[order_id]'";
        $order['region'] = $db->getOne($sql);

        /* 查询：其他处理 */
        $order['order_time']    = local_date($_CFG['time_format'], $order['add_time']);
        $order['invoice_no']    = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];

        /* 查询：是否保价 */
        $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

        /* 查询：是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);

        /* 查询：取得订单商品 */
        $_goods = get_order_goods(array('order_id' => $order['order_id'], 'order_sn' =>$order['order_sn']));

        $attr = $_goods['attr'];
        $goods_list = $_goods['goods_list'];
        unset($_goods);

        /* 查询：商品已发货数量 此单可发货数量 */
        if ($goods_list)
        {
            foreach ($goods_list as $key=>$goods_value)
            {
                if (!$goods_value['goods_id'])
                {
                    continue;
                }

                /* 超级礼包 */
                if (($goods_value['extension_code'] == 'package_buy') && (count($goods_value['package_goods_list']) > 0))
                {
                    $goods_list[$key]['package_goods_list'] = package_goods($goods_value['package_goods_list'], $goods_value['goods_number'], $goods_value['order_id'], $goods_value['extension_code'], $goods_value['goods_id']);

                    foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value)
                    {
                        $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = '';
                        /* 使用库存 是否缺货 */
                        if ($pg_value['storage'] <= 0 && $_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_SHIP)
                        {
                            $goods_list[$key]['package_goods_list'][$pg_key]['send'] = $_LANG['act_good_vacancy'];
                            $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                        }
                        /* 将已经全部发货的商品设置为只读 */
                        elseif ($pg_value['send'] <= 0)
                        {
                            $goods_list[$key]['package_goods_list'][$pg_key]['send'] = $_LANG['act_good_delivery'];
                            $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                        }
                    }
                }
                else
                {
                    $goods_list[$key]['sended'] = $goods_value['send_number'];
                    $goods_list[$key]['send'] = $goods_value['goods_number'] - $goods_value['send_number'];

                    $goods_list[$key]['readonly'] = '';
                    /* 是否缺货 */
                    if ($goods_value['storage'] <= 0 && $_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP)
                    {
                        $goods_list[$key]['send'] = $_LANG['act_good_vacancy'];
                        $goods_list[$key]['readonly'] = 'readonly="readonly"';
                    }
                    elseif ($goods_list[$key]['send'] <= 0)
                    {
                        $goods_list[$key]['send'] = $_LANG['act_good_delivery'];
                        $goods_list[$key]['readonly'] = 'readonly="readonly"';
                    }
                }
            }
        }

        /* 模板赋值 */
        $smarty->assign('order', $order);
        $smarty->assign('exist_real_goods', $exist_real_goods);
        $smarty->assign('goods_attr', $attr);
        $smarty->assign('goods_list', $goods_list);
        $smarty->assign('order_id', $order_id); // 订单id
        $smarty->assign('operation', 'split'); // 订单id
        $smarty->assign('action_note', $action_note); // 发货操作信息

        $suppliers_list = get_suppliers_list();
        $suppliers_list_count = count($suppliers_list);
        $smarty->assign('suppliers_name', suppliers_list_name()); // 取供货商名
        $smarty->assign('suppliers_list', ($suppliers_list_count == 0 ? 0 : $suppliers_list)); // 取供货商列表

        /* 显示模板 */
        $smarty->assign('ur_here', $_LANG['order_operate'] . $_LANG['op_split']);
        assign_query_info();
        $smarty->display('order_delivery_info.htm');
        exit;
    }
    /* 未发货 */
    elseif (isset($_POST['unship']))
    {
        /* 检查权限 */
        admin_priv('order_ss_edit');

        $require_note   = $_CFG['order_unship_note'] == 1;
        $action         = $_LANG['op_unship'];
        $operation      = 'unship';
    }
    /* 收货确认 */
    elseif (isset($_POST['receive']))
    {
        admin_priv('order_os_edit');
        $require_note   = $_CFG['order_receive_note'] == 1;
        $action         = $_LANG['op_receive'];
        $operation      = 'receive';
    }
    /* 取消 */
    elseif (isset($_POST['cancel']))
    {
        /* 查询：检查权限*/
        admin_priv('order_os_edit');
        $require_note   = $_CFG['order_cancel_note'] == 1;
        $action         = $_LANG['op_cancel'];
        $operation      = 'cancel';
        $show_cancel_note   = true;
        $order          = order_info($order_id);
        if ($order['money_paid'] > 0 || $order['surplus'] > 0)
        {
            $show_refund = true;
        }
        $anonymous      = $order['user_id'] == 0;
    }
    /* 无效 */
    elseif (isset($_POST['invalid']))
    {
        admin_priv('order_os_edit');
        $require_note   = $_CFG['order_invalid_note'] == 1;
        $action         = $_LANG['op_invalid'];
        $operation      = 'invalid';
    }
    /* 售后 */
	elseif (isset($_POST['after_service']))
	{
            admin_priv('order_os_edit');
	        $require_note   = true;
	        $action         = $_LANG['op_after_service'];
	        $operation      = 'after_service';
	        $after_service  = true;
	        /* 查询：根据订单id查询订单信息 */
	        if (empty($order_id))
	        {
	            die('order does not exist');
	        }
	        $order = order_info($order_id);

			/* $order_goods_amount = $order['goods_amount']; //商品权益币支付部分
	        $order_shipping_fee = $order['shipping_fee']; //运费
	        $order_cash_amount = $order['cash_amount'] ? $order['cash_amount'] : 0; //现金支付部分
	       	$order_total_amount = bcadd($order_goods_amount, $order_shipping_fee,2);
	        $order_total_amount = bcadd($order_money_paid, $order_surplus,2);	//订单总额
	        $refund_huan_default = sprintf($_LANG['refund_huan_default'], intval($order_total_amount));
	        $refund_huan_after = sprintf($_LANG['refund_huan_after'], $order_goods_amount,$order_cash_amount, $order_shipping_fee, $order_total_amount); */
            //TODO mlm订单需要整合
			if($order['mlm_id']>0){
				$order['money_paid'] = bcadd($order['money_paid'],$order['surplus'],2);
				$order['surplus'] = 0;
            }
	        $order_money_paid = $order['money_paid']; //商品权益币支付部分
	        $order_surplus = $order['surplus']; //商品现金支付部分
	        $order_total_amount = bcadd($order_money_paid, $order_surplus,2);	//订单总额
	       	$refund_huan_after = sprintf($_LANG['refund_huan_after'], $order['goods_amount'],$order['shipping_fee'], $order_total_amount);		//订单信息
	        $refund_huan_default = sprintf($_LANG['refund_huan_default'], $order_total_amount);			//提示信息
	        $refund_order_surplus = sprintf($_LANG['refund_order_surplus'], $order_surplus);		//权益币支付金额
	        $refund_order_money_paid = sprintf($_LANG['refund_order_money_paid'], $order_money_paid);	//现金支付金额
            $input_money_paid = bcsub($order_money_paid,$order['cash_back'],2);
            $input_surplus = bcsub($order_surplus,$order['surplus_back'],2);
	        $refund_surplus_default = sprintf($_LANG['refund_huan_default'], $input_surplus);		//权益币提示框
	        $refund_money_paid_default = sprintf($_LANG['refund_huan_default'], $input_money_paid);	//现金提示框


	        $smarty->assign('refund_huan_default', $refund_huan_after);                //商品总金额%s元,运费%s元,订单总金额%s元
	        $smarty->assign('order_money_paid', $order_money_paid);                    //现金支付
	        $smarty->assign('order_surplus', $order_surplus);                          //权益币支付
	        $smarty->assign('refund_order_money_paid', $refund_order_money_paid);      //现金支付：%s元
	        $smarty->assign('refund_order_surplus', $refund_order_surplus);            //权益币支付：%s元
	        $smarty->assign('refund_surplus_default', $refund_surplus_default);        //请输入0―%s之间的数值,权益币
	        $smarty->assign('refund_money_paid_default', $refund_money_paid_default);  //请输入0―%s之间的数值,现金
	        $smarty->assign('cash_line', $order_money_paid);                           //现金支付
	        $smarty->assign('surplus_line', $order_surplus);                           //权益币支付
	        $smarty->assign('cash_back', $order['cash_back']);                         //现金退还
	        $smarty->assign('surplus_back', $order['surplus_back']);                   //权益币退还
	        //配送状态
	        $shipping_status = $order['shipping_status'] > SS_UNSHIPPED ? true : false;
	        $smarty->assign('shipping_status', $shipping_status);
	        $smarty->assign('order_goods_amount', $order_goods_amount);
	        $smarty->assign('order_shipping_fee', $order_shipping_fee);
	        $smarty->assign('order_total_amount', $order_total_amount);
	        //退还默认值，最大可退还
            $smarty->assign('input_money_paid', $input_money_paid);
            $smarty->assign('input_surplus', $input_surplus);
	}
    /* 退货 */
    elseif (isset($_POST['return']))
    {
        admin_priv('order_os_edit');
        $require_note   = $_CFG['order_return_note'] == 1;
        $order          = order_info($order_id);
        if ($order['money_paid'] > 0  || $order['surplus'] > 0)
        {
            $show_refund = true;
        }
        $anonymous      = $order['user_id'] == 0;
        $action         = $_LANG['op_return'];
        $operation      = 'return';

    }
    // 线下退款
    elseif (isset($_POST['return_offline']))
    {
        admin_priv('order_os_edit');
        $action    = $_LANG['op_return_offline'];
        $operation = 'return_offline';
    }
    /* 指派 */
    elseif (isset($_POST['assign']))
    {
        admin_priv('order_os_edit');
        /* 取得参数 */
        $new_agency_id  = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
        if ($new_agency_id == 0)
        {
            sys_msg($_LANG['js_languages']['pls_select_agency']);
        }

        /* 查询订单信息 */
        $order = order_info($order_id);

        /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
        $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
        $admin_agency_id = $db->getOne($sql);
        if ($admin_agency_id > 0)
        {
            if ($order['agency_id'] != $admin_agency_id)
            {
                sys_msg($_LANG['priv_error']);
            }
        }

        /* 修改订单相关所属的办事处 */
        if ($new_agency_id != $order['agency_id'])
        {
            $query_array = array('order_info', // 更改订单表的供货商ID
                'delivery_order', // 更改订单的发货单供货商ID
                'back_order'// 更改订单的退货单供货商ID
            );
            foreach ($query_array as $value)
            {
                $db->query("UPDATE " . $ecs->table($value) . " SET agency_id = '$new_agency_id' " .
                    "WHERE order_id = '$order_id'");

            }
        }

        /* 操作成功 */
        $links[] = array('href' => 'order.php?act=list&' . list_link_postfix(), 'text' => $_LANG['02_order_list']);
        sys_msg($_LANG['act_ok'], 0, $links);
    }
    /* 订单删除 */
    elseif (isset($_POST['remove']))
    {
        admin_priv('order_os_edit');
        $require_note = false;
        $operation = 'remove';
        if (!$batch)
        {
            /* 检查能否操作 */
            $order = order_info($order_id);
            $operable_list = operable_list($order);
            if (!isset($operable_list['remove']))
            {
                die('Hacking attempt');
            }

            /* 删除订单 */
            $db->query("DELETE FROM ".$ecs->table('order_info'). " WHERE order_id = '$order_id'");
            $db->query("DELETE FROM ".$ecs->table('order_goods'). " WHERE order_id = '$order_id'");
            $db->query("DELETE FROM ".$ecs->table('order_action'). " WHERE order_id = '$order_id'");
            $action_array = array('delivery', 'back');
            del_delivery($order_id, $action_array);

            /* todo 记录日志 */
            admin_log($order['order_sn'], 'remove', 'order');

            /* 返回 */
            sys_msg($_LANG['order_removed'], 0, array(array('href'=>'order.php?act=list&' . list_link_postfix(), 'text' => $_LANG['return_list'])));
        }
    }
    /* 发货单删除 */
    elseif (isset($_REQUEST['remove_invoice']))
    {
        admin_priv('order_os_edit');
        // 删除发货单
        $delivery_id=$_REQUEST['delivery_id'];
        $delivery_id = is_array($delivery_id) ? $delivery_id : array($delivery_id);

        foreach($delivery_id as $value_is)
        {
            $value_is = intval(trim($value_is));

            // 查询：发货单信息
            $delivery_order = delivery_order_info($value_is);

            // 如果status不是退货
            if ($delivery_order['status'] != 1)
            {
                /* 处理退货 */
                delivery_return_goods($value_is, $delivery_order);
            }

            // 如果status是已发货并且发货单号不为空
            if ($delivery_order['status'] == 0 && $delivery_order['invoice_no'] != '')
            {
                /* 更新：删除订单中的发货单号 */
                del_order_invoice_no($delivery_order['order_id'], $delivery_order['invoice_no']);
            }

            // 更新：删除发货单
            $sql = "DELETE FROM ".$ecs->table('delivery_order'). " WHERE delivery_id = '$value_is'";
            $db->query($sql);
        }

        /* 返回 */
        sys_msg($_LANG['tips_delivery_del'], 0, array(array('href'=>'order.php?act=delivery_list' , 'text' => $_LANG['return_list'])));
    }
    /* 退货单删除 */
    elseif (isset($_REQUEST['remove_back']))
    {
        admin_priv('order_os_edit');
        $back_id = $_REQUEST['back_id'];
        /* 删除退货单 */
        if(is_array($back_id))
        {
            foreach ($back_id as $value_is)
            {
                $sql = "DELETE FROM ".$ecs->table('back_order'). " WHERE back_id = '$value_is'";
                $db->query($sql);
            }
        }
        else
        {
            $sql = "DELETE FROM ".$ecs->table('back_order'). " WHERE back_id = '$back_id'";
            $db->query($sql);
        }
        /* 返回 */
        sys_msg($_LANG['tips_back_del'], 0, array(array('href'=>'order.php?act=back_list' , 'text' => $_LANG['return_list'])));
    }
    /* 批量打印订单 */
    elseif (isset($_POST['print']))
    {
        admin_priv('order_os_edit');
        if (empty($_POST['order_id']))
        {
            sys_msg($_LANG['pls_select_order']);
        }

        /* 赋值公用信息 */
        $smarty->assign('shop_name',    $_CFG['shop_name']);
        $smarty->assign('shop_url',     $ecs->url());
        $smarty->assign('shop_address', $_CFG['shop_address']);
        $smarty->assign('service_phone',$_CFG['service_phone']);
        $smarty->assign('print_time',   local_date($_CFG['time_format']));
        $smarty->assign('action_user',  $_SESSION['admin_name']);

        $html = '';
        $order_sn_list = explode(',', $_POST['order_id']);
        foreach ($order_sn_list as $order_sn)
        {
            /* 取得订单信息 */
            $order = order_info(0, $order_sn);
            if (empty($order))
            {
                continue;
            }

            /* 根据订单是否完成检查权限 */
            if (order_finished($order))
            {
                if (!admin_priv('order_view_finished', '', false))
                {
                    continue;
                }
            }
            else
            {
                if (!admin_priv('order_query', '', false))
                {
                    continue;
                }
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
            $agency_id = $db->getOne($sql);
            if ($agency_id > 0)
            {
                if ($order['agency_id'] != $agency_id)
                {
                    continue;
                }
            }

            /* 取得用户名 */
            if ($order['user_id'] > 0)
            {
                $user = user_info($order['user_id']);
                if (!empty($user))
                {
                    $order['user_name'] = $user['user_name'];
                }
            }

            /* 取得区域名 */
            $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
                "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
                "FROM " . $ecs->table('order_info') . " AS o " .
                "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
                "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
                "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
                "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
                "WHERE o.order_id = '$order[order_id]'";
            $order['region'] = $db->getOne($sql);

            /* 其他处理 */
            $order['order_time']    = local_date($_CFG['time_format'], $order['add_time']);
            $order['pay_time']      = $order['pay_time'] > 0 ?
                local_date($_CFG['time_format'], $order['pay_time']) : $_LANG['ps'][PS_UNPAYED];
            $order['shipping_time'] = $order['shipping_time'] > 0 ?
                local_date($_CFG['time_format'], $order['shipping_time']) : $_LANG['ss'][SS_UNSHIPPED];
            $order['status']        = $_LANG['os'][$order['order_status']] . ',' . $_LANG['ps'][$order['pay_status']] . ',' . $_LANG['ss'][$order['shipping_status']];
            $order['invoice_no']    = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];

            /* 此订单的发货备注(此订单的最后一条操作记录) */
            $sql = "SELECT action_note FROM " . $ecs->table('order_action').
                " WHERE order_id = '$order[order_id]' AND shipping_status = 1 ORDER BY log_time DESC";
            $order['invoice_note'] = $db->getOne($sql);

            /* 参数赋值：订单 */
            $smarty->assign('order', $order);

            /* 取得订单商品 */
            $goods_list = array();
            $goods_attr = array();
            $sql = "SELECT o.*, g.goods_number AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name " .
                "FROM " . $ecs->table('order_goods') . " AS o ".
                "LEFT JOIN " . $ecs->table('goods') . " AS g ON o.goods_id = g.goods_id " .
                "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                "WHERE o.order_id = '$order[order_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                /* 虚拟商品支持 */
                if ($row['is_real'] == 0)
                {
                    /* 取得语言项 */
                    $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
                    if (file_exists($filename))
                    {
                        include_once($filename);
                        if (!empty($_LANG[$row['extension_code'].'_link']))
                        {
                            $row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                        }
                    }
                }

                $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
                $row['formated_goods_price']    = price_format($row['goods_price']);

                $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                $goods_list[] = $row;
            }

            $attr = array();
            $arr  = array();
            foreach ($goods_attr AS $index => $array_val)
            {
                foreach ($array_val AS $value)
                {
                    $arr = explode(':', $value);//以 : 号将属性拆开
                    $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
                }
            }

            $smarty->assign('goods_attr', $attr);
            $smarty->assign('goods_list', $goods_list);

            $smarty->template_dir = '../' . DATA_DIR;
            $html .= $smarty->fetch('order_print.html') .
                '<div style="PAGE-BREAK-AFTER:always"></div>';
        }

        echo $html;
        exit;
    }

    /* 批量导出订单 */
    elseif (isset($_POST['export'])) {
        admin_priv('order_query');
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $type = $_POST['execl_type'] ? $_POST['execl_type'] : 0;
        $where = strpos($_POST['sqlwhere'],'WHERE') === false ?' WHERE  1 ': $_POST['sqlwhere'] ;
        if(!empty($_REQUEST['order_id'])){
            $order_sn = $_REQUEST['order_id'];
            $where .= " AND o.order_sn ".db_create_in($order_sn);
        }
        $admin_type = $_SESSION['admin_type'] ? $_SESSION['admin_type'] : 0;
        if(($admin_type && !$_SESSION['suppliers_id']) || ($admin_type && !is_numeric($_SESSION['suppliers_id']))){
            $sess->destroy_session();
            sys_msg('登录异常，请重新登录……');
        }

        if(isset($_SESSION['suppliers_id'])){
            $where .= !empty($_SESSION['suppliers_id']) ? " AND o.suppliers_id in ({$_SESSION['suppliers_id']})" : '';
        }else{
            $sess->destroy_session();
            sys_msg('登录异常，请重新登录');
        }

        $where .= ' AND o.is_temp = 0  ORDER BY o.add_time desc limit 5000';
        $filename = "【". local_date('Ynj', gmtime()). "】订单.xls";

        if ($type == 1) {
            $sql = "select
            o.suppliers_id,
            o.shipping_time,
            o.invoice_no,
            o.shipping_name,
            o.user_id,
            o.order_id,
            o.shipping_status,
            o.order_status,
            o.pay_status,
            o.shipping_fee,
            b.goods_sn,
            b.goods_name,
            b.goods_attr,
            o.order_sn,
            FROM_UNIXTIME(
            o.add_time
            )  AS  time,
            f.shop_price,
            b.settlement_money,
            o.goods_amount,
            o.money_paid,
            o.surplus,
            b.goods_number,
            f.goods_location,
            o.consignee,
            f.provider_name,
            IFNULL(c.region_name,  '') as province,
            IFNULL(d.region_name,  '') as city,
            IFNULL(e.region_name,  '') as district,
            o.address,
            o.zipcode,
            o.mobile,
            p.product_sn
            FROM
            " . $GLOBALS['ecs']->table('order_info') . "  AS  o
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  h  ON  o.country  =  h.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  c  ON  o.province  =  c.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  d  ON  o.city  =  d.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  e  ON  o.district  =  e.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('order_goods') . "  b  ON  o.order_id  =  b.order_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('goods') . "  f  ON  f.goods_id  =  b.goods_id
            LEFT  JOIN " .$GLOBALS['ecs']->table('products'). " AS p ON p.product_id=b.product_id " . $where;

            $sql = str_replace('\\', '', $sql);
            $data = $db->getAll($sql);
            $obj=new PHPExcel();             //创建表
            $sheet=$obj->getActiveSheet(0);   //确定活动表
            $sheet->getStyle('C')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
            $sheet->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $sheet->getStyle('O')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $sheet->setCellValue('A1',$_LANG['goods_sn']);
            $sheet->setCellValue('B1',$_LANG['goods_name']);
            $sheet->setCellValue('C1',$_LANG['order_sn']);
            $sheet->setCellValue('D1',$_LANG['order_time']);

            if (2 != $suppliers_cooperate_type) {
                $sheet->setCellValue('E1',$_LANG['shop_price']);
                $sheet->setCellValue('F1',$_LANG['label_goods_amount']);
                $sheet->setCellValue('G1',$_LANG['export_money_paid']);
                $sheet->setCellValue('H1',$_LANG['export_surplus']);
                $index_header   = 7;
            } else {
                $sheet->setCellValue('E1',$_LANG['export_settlement_money']);
                $index_header   = 4;
            }

            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['goods_number']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['goods_location']);
            $sheet->setCellValue(IntToChr(++$index_header).'1','货品号');
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_consignee']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['provider_name']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(30);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['province']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(18);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['city']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['district']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(18);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['address']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(30);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_zipcode']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_mobile']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(18);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_shipping_time']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(18);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_invoice_no']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['shipping_name']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_user_name']);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['label_user_phone']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(18);
            $sheet->setCellValue(IntToChr(++$index_header).'1',$_LANG['order_status']);
            $sheet->getColumnDimension(IntToChr($index_header))->setWidth(30);

            $i = 2;
            foreach ($data as $k=>$v){
                if ($v['suppliers_id'] != $_SESSION['suppliers_id'] && $admin_type == 1) {
                    continue;
                }
                if ($admin_type == 1 && is_numeric($_SESSION['suppliers_id']) && ($v['suppliers_id'] == $_SESSION['suppliers_id'])) {
                    if ($v['shipping_status'] == SS_UNSHIPPED && $v['order_status'] == OS_CONFIRMED && $v['pay_status'] == PS_PAYED) {
                        edits("shipping_status = " . SS_SHIPPED_ING, $v['order_id']);
                        /* 记录log */
                        order_action($v['order_sn'], OS_CONFIRMED, SS_SHIPPED_ING, PS_PAYED, '导出订单出库' . " (sid:{$_SESSION['suppliers_id']})");
                    }
                }

                $user1 = user_info($v['user_id']);
                $temp_status = '';
                //订单状态 0:未确认 1:已确认 2:取消 3:无效 4:退货 5:分单 6:部分分单
                switch ($v['order_status']) {
                    case OS_UNCONFIRMED:
                        $temp_status.='未确认,';
                        break;
                    case OS_CONFIRMED:
                        $temp_status.='已确认,';
                        break;
                    case OS_CANCELED:
                        $temp_status.='取消,';
                        break;
                    case OS_INVALID:
                        $temp_status.='无效,';
                        break;
                    case OS_RETURNED:
                        $temp_status.='退货,';
                        break;
                    case OS_SPLITED:
                        $temp_status.='分单,';
                        break;
                    case OS_SPLITING_PART:
                        $temp_status.='部分分单,';
                        break;
                }
                //款项状态 0:未付款 1:付款中 2:已付款
                switch ($v['pay_status']) {
                    case PS_UNPAYED:
                        $temp_status.='未付款,';
                        break;
                    case PS_PAYING:
                        $temp_status.='付款中,';
                        break;
                    case PS_PAYED:
                        $temp_status.='已付款,';
                        break;
                }
                //配送状态 0:未发货 1:已发货 2:确认收货 3:备货中 4:已发货(部分商品) 5:发货中(处理分单) 6:已发货(部分商品)
                /* 配送状态 */
                switch ($v['shipping_status']) {
                    case SS_UNSHIPPED:
                        $temp_status.='未发货';
                        break;
                    case SS_SHIPPED:
                        $temp_status.='已发货';
                        break;
                    case SS_RECEIVED:
                        $temp_status.='确认收货';
                        break;
                    case SS_PREPARING:
                        $temp_status.='配货中';
                        break;
                    case SS_SHIPPED_PART:
                        $temp_status.='已发货(部分商品)';
                        break;
                    case SS_SHIPPED_ING:
                        $temp_status.='发货中(处理分单)';
                        break;
                    case OS_SHIPPED_PART:
                        $temp_status.='已发货(部分商品)';
                        break;
                }
                $sheet->setCellValue('A'.$i,$v['goods_sn']);
                $str = $v['goods_name']."  \r\r  ".$v['goods_attr'];
                $sheet->setCellValue('B'.$i,$str);
                $sheet->setCellValue('C'.$i,$v['order_sn']);
                $sheet->setCellValue('D'.$i,$v['time']);

                if (2 != $suppliers_cooperate_type) {
                    $sheet->setCellValue('E'.$i,$v['shop_price']);
                    $sheet->setCellValue('F'.$i,$v['goods_amount']);
                    $sheet->setCellValue('G'.$i,$v['money_paid']);
                    $sheet->setCellValue('H'.$i,$v['surplus']);
                    $index_header   = 7;
                } else {
                    $sheet->setCellValue('E'.$i,$v['settlement_money']+$v['shipping_fee']);
                    $index_header   = 4;
                }

                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['goods_number']);
                $sheet->setCellValue(IntToChr(++$index_header).$i, $v['goods_location']);
                $sheet->setCellValue(IntToChr(++$index_header).$i, $v['product_sn']."\t");
                $sheet->setCellValue(IntToChr(++$index_header).$i, $v['consignee']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['provider_name']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['province']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['city']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['district']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['address']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['zipcode']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['mobile']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['shipping_time']?date('Y-m-d H:i:s',$v['shipping_time']):'');
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['invoice_no']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$v['shipping_name']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$user1['user_name']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$user1['mobile_phone']);
                $sheet->setCellValue(IntToChr(++$index_header).$i,$temp_status);
                $i++;
            }
            addLog($sql,'info',$_SESSION['admin_name'],'MySql');
        }
        elseif($type == 2)
        {
            $sql = "select
            o.suppliers_id,
            o.order_id,
            o.shipping_status,
            o.order_status,
            o.pay_status,
            o.order_sn,
            o.consignee,
            o.tel,
            o.mobile,
            h.region_name as country,
            c.region_name as province,
            d.region_name as city,
            e.region_name as district,
            o.address,
            b.goods_name,
            b.goods_attr,
            b.goods_sn,
            b.goods_number,
            o.postscript
            FROM
            " . $GLOBALS['ecs']->table('order_info') . "  AS  o
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  h  ON  o.country  =  h.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  c  ON  o.province  =  c.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  d  ON  o.city  =  d.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('region') . "  AS  e  ON  o.district  =  e.region_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('order_goods') . "  b  ON  o.order_id  =  b.order_id
            LEFT  JOIN  " . $GLOBALS['ecs']->table('goods') . "  f  ON  f.goods_id  =  b.goods_id
            LEFT JOIN " .$GLOBALS['ecs']->table('order_activity'). " AS oa ON oa.order_id=o.order_id " . $where;

            $sql = str_replace('\\', '', $sql);
            $data = $db->getAll($sql);
            $obj=new PHPExcel();             //创建表
            $sheet=$obj->getActiveSheet(0);   //确定活动表
            $sheet->getStyle('A')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
            $sheet->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $sheet->setCellValue('A1',$_LANG['order_sn']);
            $sheet->setCellValue('B1',$_LANG['recipients']);
            $sheet->setCellValue('C1',$_LANG['fixed_line']);
            $sheet->setCellValue('D1',$_LANG['mobiles']);
            $sheet->setCellValue('E1',$_LANG['address']);
            $sheet->setCellValue('F1',$_LANG['label_shipping_info']);
            $sheet->setCellValue('G1',$_LANG['action_note']);
            $sheet->setCellValue('H1',$_LANG['collecting_amount']);
            $sheet->setCellValue('I1',$_LANG['insurance_amount']);
            $sheet->setCellValue('J1',$_LANG['business']);
            $sheet->getColumnDimension('A')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(18);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(50);
            $sheet->getColumnDimension('G')->setWidth(18);
            $i = 2;
            foreach ($data as $k=>$v){
                if ($v['suppliers_id'] != $_SESSION['suppliers_id'] && $admin_type == 1) {
                    continue;
                }
                if($admin_type == 1 && is_numeric($_SESSION['suppliers_id']) && ($v['suppliers_id'] == $_SESSION['suppliers_id'])){
                    if ($v['shipping_status'] == SS_UNSHIPPED && $v['order_status'] == OS_CONFIRMED && $v['pay_status'] == PS_PAYED) {
                        edits("shipping_status = " . SS_SHIPPED_ING, $v['order_id']);
                        /* 记录log */
                        order_action($v['order_sn'], OS_CONFIRMED, SS_SHIPPED_ING, PS_PAYED, '导出订单出库'." (sid:{$_SESSION['suppliers_id']})");
                    }
                }
                $sheet->setCellValue('A'.$i,$data[$k]['order_sn']);
                $sheet->setCellValue('B'.$i,$data[$k]['consignee']);
                $sheet->setCellValue('C'.$i,$data[$k]['tel']);
                $sheet->setCellValue('D'.$i,$data[$k]['mobile']);
                $location = $data[$k]['province'] .','.$data[$k]['city'] .','.$data[$k]['district'].','.$data[$k]['address'];
                $sheet->setCellValue('E'.$i,$location);
                $goods_name = str_replace("\t", '', $data[$k]['goods_name']);
                $str = "【" . $data[$k]['goods_sn'] . "】" .$goods_name.$data[$k]['goods_attr']."*".$data[$k]['goods_number'];
                $sheet->setCellValue('F'.$i,$str);
                $sheet->setCellValue('G'.$i,$data[$k]['postscript']);
                $sheet->setCellValue('H'.$i,'');
                $sheet->setCellValue('I'.$i,'');
                $sheet->setCellValue('J'.$i,'');
                $i++;
            }
            addLog($sql,'info',$_SESSION['admin_name'],'MySql');
        }
        else
        {
            sys_msg("请选择下载方式", 0);
        }
        ob_start();
        ob_end_clean();
        header("Content-type: application/octet-stream;charset=utf-8");
        header("content-Disposition:attachement;filename= ".urlencode($filename));
        $write = PHPExcel_IOFactory::createWriter($obj, 'Excel5');
        $write->save('php://output');
        exit;
    }

    /* 去发货 */
    elseif (isset($_POST['to_delivery']))
    {
        $url = 'order.php?act=delivery_list&order_sn='.$_REQUEST['order_sn'];

        ecs_header("Location: $url\n");
        exit;
    }

    /* 直接处理还是跳到详细页面 */
    if (
        ($require_note && $action_note == '') || 
        isset($show_invoice_no) || 
        isset($show_refund) || 
        isset($_POST['after_service']) || 
        isset($_POST['return_offline'])
    )    
    {

        /* 模板赋值 */
        $smarty->assign('require_note', $require_note); // 是否要求填写备注
        $smarty->assign('action_note', $action_note);   // 备注
        $smarty->assign('show_cancel_note', isset($show_cancel_note)); // 是否显示取消原因
        $smarty->assign('show_invoice_no', isset($show_invoice_no)); // 是否显示发货单号
        $smarty->assign('show_refund', isset($show_refund)); // 是否显示退款
        $smarty->assign('anonymous', isset($anonymous) ? $anonymous : true); // 是否匿名
        $smarty->assign('order_id', $order_id); // 订单id
        $smarty->assign('batch', $batch);   // 是否批处理
        $smarty->assign('operation', $operation); // 操作
        $smarty->assign('after_service', isset($after_service)); // 售后操作
        $smarty->assign('order_type', $order['order_type']); // 售后操作
        $smarty->assign('return_offline', isset($_POST['return_offline'])); // 线下退款

        // api host
        $smarty->assign('api_host', API_HOST);
        $smarty->assign('token', getToken());

        /* 显示模板 */
        $smarty->assign('ur_here', $_LANG['order_operate'] . $action);
        assign_query_info();
        $smarty->display('order_operate.htm');
    }
    else
    {
        /* 直接处理 */
        if (!$batch)
        {
            /* 一个订单 */
            ecs_header("Location: order.php?act=operate_post&order_id=" . $order_id .
                "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
            exit;
        }
        else
        {
            /* 多个订单 */
            ecs_header("Location: order.php?act=batch_operate_post&order_id=" . $order_id .
                "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
            exit;
        }
    }
}

/*------------------------------------------------------ */
//-- 操作订单状态（处理批量提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch_operate_post')
{
    /* 检查权限 */
    admin_priv('order_os_edit');

    /* 取得参数 */
    $order_id   = $_REQUEST['order_id'];        // 订单id（逗号格开的多个订单id）
    $operation  = $_REQUEST['operation'];       // 订单操作
    $action_note= $_REQUEST['action_note'];     // 操作备注

    $order_id_list = explode(',', $order_id);

    /* 初始化处理的订单sn */
    $sn_list = array();
    $sn_not_list = array();

    $where = '';
    if(!empty($_SESSION['suppliers_id'])){
        $where .= " and suppliers_id in ({$_SESSION['suppliers_id']})";
    }
    /* 确认 */
    if ('confirm' == $operation)
    {
        foreach($order_id_list as $id_order)
        {
            $sql = "SELECT * FROM " . $ecs->table('order_info') .
                " WHERE order_sn = '$id_order'" .
                " AND order_status = '" . OS_UNCONFIRMED . "'" . $where;
            $order = $db->getRow($sql);

            if($order)
            {
                /* 检查能否操作 */
                $operable_list = operable_list($order);
                if (!isset($operable_list[$operation]))
                {
                    $sn_not_list[] = $id_order;
                    continue;
                }

                $order_id = $order['order_id'];

                /* 标记订单为已确认 */
                update_order($order_id, array('order_status' => OS_CONFIRMED, 'confirm_time' => gmtime()));
                update_order_amount($order_id);

                /* 记录log */
                order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note);

                /* 发送邮件 */
                if ($_CFG['send_confirm_email'] == '1')
                {
                    $tpl = get_mail_template('order_confirm');
                    $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
                    $smarty->assign('order', $order);
                    $smarty->assign('shop_name', $_CFG['shop_name']);
                    $smarty->assign('send_date', local_date($_CFG['date_format']));
                    $smarty->assign('sent_date', local_date($_CFG['date_format']));
                    $content = $smarty->fetch('str:' . $tpl['template_content']);
                    send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                }

                $sn_list[] = $order['order_sn'];
            }
            else
            {
                $sn_not_list[] = $id_order;
            }
        }

        $sn_str = $_LANG['confirm_order'];
    }
    /* 无效 */
    elseif ('invalid' == $operation)
    {
        foreach($order_id_list as $id_order)
        {
            $sql = "SELECT * FROM " . $ecs->table('order_info') .
                " WHERE order_sn = $id_order" . order_query_sql('unpay_unship') . $where;

            $order = $db->getRow($sql);

            if($order)
            {
                /* 检查能否操作 */
                $operable_list = operable_list($order);
                if (!isset($operable_list[$operation]))
                {
                    $sn_not_list[] = $id_order;
                    continue;
                }

                $order_id = $order['order_id'];

                /* 标记订单为“无效” */
                update_order($order_id, array('order_status' => OS_INVALID));

                /* 记录log */
                order_action($order['order_sn'], OS_INVALID, SS_UNSHIPPED, PS_UNPAYED, $action_note);

                /* 如果使用库存，且下订单时减库存，则增加库存 */
                if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
                {
                    change_order_goods_storage($order_id, false, SDT_PLACE);
                }

                /* 发送邮件 */
                if ($_CFG['send_invalid_email'] == '1')
                {
                    $tpl = get_mail_template('order_invalid');
                    $smarty->assign('order', $order);
                    $smarty->assign('shop_name', $_CFG['shop_name']);
                    $smarty->assign('send_date', local_date($_CFG['date_format']));
                    $smarty->assign('sent_date', local_date($_CFG['date_format']));
                    $content = $smarty->fetch('str:' . $tpl['template_content']);
                    send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                }

                /* 退还用户余额、积分、红包 */
                return_user_surplus_integral_bonus($order);

                $sn_list[] = $order['order_sn'];

                // 通知erp取消订单
                // include_once(ROOT_PATH . 'includes/cls_matrix.php');
                $matrix = new matrix();
                $bind_info = $matrix->get_bind_info(array('ecos.ome'));
                if($bind_info){
                    $matrix->set_dead_order($order_id);
                }
            }
            else
            {
                $sn_not_list[] = $id_order;
            }
        }

        $sn_str = $_LANG['invalid_order'];
    }
    elseif ('cancel' == $operation)
    {
        foreach($order_id_list as $id_order)
        {
            $sql = "SELECT * FROM " . $ecs->table('order_info') .
                " WHERE order_sn = $id_order" . order_query_sql('unpay_unship') . $where;

            $order = $db->getRow($sql);
            if($order)
            {
                /* 检查能否操作 */
                $operable_list = operable_list($order);
                if (!isset($operable_list[$operation]))
                {
                    $sn_not_list[] = $id_order;
                    continue;
                }

                $order_id = $order['order_id'];

                /* 标记订单为“取消”，记录取消原因 */
                $cancel_note = trim($_REQUEST['cancel_note']);
                update_order($order_id, array('order_status' => OS_CANCELED, 'to_buyer' => $cancel_note));

                /* 记录log */
                order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $action_note);

                /* 如果使用库存，且下订单时减库存，则增加库存 */
                if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
                {
                    change_order_goods_storage($order_id, false, SDT_PLACE);
                }

                /* 发送邮件 */
                if ($_CFG['send_cancel_email'] == '1')
                {
                    $tpl = get_mail_template('order_cancel');
                    $smarty->assign('order', $order);
                    $smarty->assign('shop_name', $_CFG['shop_name']);
                    $smarty->assign('send_date', local_date($_CFG['date_format']));
                    $smarty->assign('sent_date', local_date($_CFG['date_format']));
                    $content = $smarty->fetch('str:' . $tpl['template_content']);
                    send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                }

                /* 退还用户余额、积分、红包 */
                return_user_surplus_integral_bonus($order);

                $sn_list[] = $order['order_sn'];

                // 通知erp取消订单
                // include_once(ROOT_PATH . 'includes/cls_matrix.php');
                $matrix = new matrix();
                $bind_info = $matrix->get_bind_info(array('ecos.ome'));
                if($bind_info){
                    $matrix->set_dead_order($order_id);
                }
            }
            else
            {
                $sn_not_list[] = $id_order;
            }
        }

        $sn_str = $_LANG['cancel_order'];
    }
    elseif ('remove' == $operation)
    {
        foreach ($order_id_list as $id_order)
        {
            /* 检查能否操作 */
            $order = order_info('', $id_order, $_SESSION['suppliers_id']);
            $operable_list = operable_list($order);
            if (!isset($operable_list['remove']))
            {
                $sn_not_list[] = $id_order;
                continue;
            }

            /* 删除订单 */
            $db->query("DELETE FROM ".$ecs->table('order_info'). " WHERE order_id = '$order[order_id]'");
            $db->query("DELETE FROM ".$ecs->table('order_goods'). " WHERE order_id = '$order[order_id]'");
            $db->query("DELETE FROM ".$ecs->table('order_action'). " WHERE order_id = '$order[order_id]'");
            $action_array = array('delivery', 'back');
            del_delivery($order['order_id'], $action_array);

            /* todo 记录日志 */
            admin_log($order['order_sn'], 'remove', 'order');

            $sn_list[] = $order['order_sn'];
        }

        $sn_str = $_LANG['remove_order'];
    }
    else
    {
        die('invalid params');
    }

    /* 取得备注信息 */
//    $action_note = $_REQUEST['action_note'];

    if(empty($sn_not_list))
    {
        $sn_list = empty($sn_list) ? '' : $_LANG['updated_order'] . join($sn_list, ',');
        $msg = $sn_list;
        $links[] = array('text' => $_LANG['return_list'], 'href' => 'order.php?act=list&' . list_link_postfix());
        sys_msg($msg, 0, $links);
    }
    else
    {
        $order_list_no_fail = array();
        $sql = "SELECT * FROM " . $ecs->table('order_info') .
            " WHERE order_sn " . db_create_in($sn_not_list) . $where;
        $res = $db->query($sql);
        while($row = $db->fetchRow($res))
        {
            $order_list_no_fail[$row['order_id']]['order_id'] = $row['order_id'];
            $order_list_no_fail[$row['order_id']]['order_sn'] = $row['order_sn'];
            $order_list_no_fail[$row['order_id']]['order_status'] = $row['order_status'];
            $order_list_no_fail[$row['order_id']]['shipping_status'] = $row['shipping_status'];
            $order_list_no_fail[$row['order_id']]['pay_status'] = $row['pay_status'];

            $order_list_fail = '';
            foreach(operable_list($row) as $key => $value)
            {
                if($key != $operation)
                {
                    $order_list_fail .= $_LANG['op_' . $key] . ',';
                }
            }
            $order_list_no_fail[$row['order_id']]['operable'] = $order_list_fail;
        }

        /* 模板赋值 */
        $smarty->assign('order_info', $sn_str);
        $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));
        $smarty->assign('order_list',   $order_list_no_fail);

        /* 显示模板 */
        assign_query_info();
        $smarty->display('order_operate_info.htm');
    }
}

/*------------------------------------------------------ */
//-- 操作订单状态（处理提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'operate_post')
{
    /* 检查权限 */
    admin_priv('order_os_edit');

    if('unpay' == $_REQUEST['operation'])$_REQUEST['operation']='cancel';

    /* 取得参数 */
    $order_id   = intval(trim($_REQUEST['order_id']));        // 订单id
    $operation  = $_REQUEST['operation'];       // 订单操作

    /* 查询订单信息 */
    $order = order_info($order_id, 0, $_SESSION['suppliers_id']);
    if(empty($order)){
        die('order does not exist');
    }

    if($order['order_type'] == OT_INSTALMENT){
        $sql = "SELECT * FROM " . $ecs->table('order_instalment') . " WHERE `order_id`=$order[order_id] ";
        $instalment_order = $db->getAll($sql);
        if(empty($instalment_order)){
            die('instalment order does not exist');
        }
    }

    /* 检查能否操作 */
    $operable_list = operable_list($order);
    if (!isset($operable_list[$operation]))
    {
        die('Hacking attempt');
    }

    /* 取得备注信息 */
    $action_note = $_REQUEST['action_note'];

    /* 初始化提示信息 */
    $msg = '';

    /* 确认 */
    if ('confirm' == $operation)
    {
        /* 标记订单为已确认 */
        update_order($order_id, array('order_status' => OS_CONFIRMED, 'confirm_time' => gmtime()));
        update_order_amount($order_id);

        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note);

        /* 如果原来状态不是“未确认”，且使用库存，且下订单时减库存，则减少库存 */
        if ($order['order_status'] != OS_UNCONFIRMED && $_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
        {
            change_order_goods_storage($order_id, true, SDT_PLACE);
        }

        /* 发送邮件 */
        $cfg = $_CFG['send_confirm_email'];
        if ($cfg == '1')
        {
            $tpl = get_mail_template('order_confirm');
            $smarty->assign('order', $order);
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', local_date($_CFG['date_format']));
            $smarty->assign('sent_date', local_date($_CFG['date_format']));
            $content = $smarty->fetch('str:' . $tpl['template_content']);
            if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $msg = $_LANG['send_mail_fail'];
            }
        }
        // 请求crm
        update_order_crm($order['order_sn']);
    }
    /* 付款 */
    elseif ('pay' == $operation)
    {
        /* 检查权限 */
        admin_priv('order_ps_edit');

        /* 标记订单为已确认、已付款，更新付款时间和已支付金额，如果是货到付款，同时修改订单为“收货确认” */
        if ($order['order_status'] != OS_CONFIRMED)
        {
            $arr['order_status']    = OS_CONFIRMED;
            $arr['confirm_time']    = gmtime();
        }
        $arr['pay_status']  = PS_PAYED;
        $arr['pay_time']    = gmtime();
        $arr['money_paid']  = $order['money_paid'] + $order['order_amount'];
        $arr['order_amount']= 0;
        $payment = payment_info($order['pay_id']);
        if ($payment['is_cod'])
        {
            $arr['shipping_status'] = SS_RECEIVED;
            $order['shipping_status'] = SS_RECEIVED;
        }
        update_order($order_id, $arr);
        //订单支付后，创建订单到淘打
        include_once(ROOT_PATH."includes/cls_matrix.php");
        $matrix = new matrix();
        $bind_info = $matrix->get_bind_info(array('taodali'));
        if($bind_info){
            $matrix->createOrder($order['order_sn'],'taodali');
        }
        // 请求crm
        update_order_crm($order['order_sn']);
        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, $order['shipping_status'], PS_PAYED, $action_note);
    }
    /* 设为未付款 */
    elseif ('unpay' == $operation)
    {
        /* 检查权限 */
        admin_priv('order_ps_edit');

        /* 标记订单为未付款，更新付款时间和已付款金额 */
        $arr = array(
            'pay_status'    => PS_UNPAYED,
            'pay_time'      => 0,
            'money_paid'    => 0,
            'order_amount'  => $order['money_paid']
        );
        update_order($order_id, $arr);

        /* todo 处理退款 */
        $refund_type = @$_REQUEST['refund'];
        $refund_note = @$_REQUEST['refund_note'];
        order_refund($order, $refund_type, $refund_note);
        // 更新订单crm
        update_order_crm($order['order_sn']);
        // 退款请求crm
        $data['order_id']=$order['order_sn'];
        $data['cur_money']=$order['total_fee'];
        send_refund_to_crm($data);
        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note);
    }
    /* 配货 */
    elseif ('prepare' == $operation)
    {
        /* 标记订单为已确认，配货中 */
        if ($order['order_status'] != OS_CONFIRMED)
        {
            $arr['order_status']    = OS_CONFIRMED;
            $arr['confirm_time']    = gmtime();
        }
        $arr['shipping_status']     = SS_PREPARING;
        update_order($order_id, $arr);

        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, SS_PREPARING, $order['pay_status'], $action_note);
        /* 清除缓存 */
        clear_cache_files();
    }
    /* 分单确认 */
    elseif ('split' == $operation)
    {
        /* 检查权限 */
        admin_priv('order_ss_edit');

        /* 定义当前时间 */
        define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

        /* 获取表单提交数据 */
        $suppliers_id = isset($_REQUEST['suppliers_id']) ? intval(trim($_REQUEST['suppliers_id'])) : '0';
        array_walk($_REQUEST['delivery'], 'trim_array_walk');
        $delivery = $_REQUEST['delivery'];
        array_walk($_REQUEST['send_number'], 'trim_array_walk');
        array_walk($_REQUEST['send_number'], 'intval_array_walk');
        $send_number = $_REQUEST['send_number'];
        $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';
        $delivery['user_id']  = intval($delivery['user_id']);
        $delivery['country']  = intval($delivery['country']);
        $delivery['province'] = intval($delivery['province']);
        $delivery['city']     = intval($delivery['city']);
        $delivery['district'] = intval($delivery['district']);
        $delivery['agency_id']    = intval($delivery['agency_id']);
        $delivery['insure_fee']   = floatval($delivery['insure_fee']);
        $delivery['shipping_fee'] = floatval($delivery['shipping_fee']);

        /* 订单是否已全部分单检查 */
        if ($order['order_status'] == OS_SPLITED)
        {
            /* 操作失败 */
            $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
            sys_msg(sprintf($_LANG['order_splited_sms'], $order['order_sn'],
                $_LANG['os'][OS_SPLITED], $_LANG['ss'][SS_SHIPPED_ING], $GLOBALS['_CFG']['shop_name']), 1, $links);
        }

        /* 取得订单商品 */
        $_goods = get_order_goods(array('order_id' => $order_id, 'order_sn' => $delivery['order_sn']));
        $goods_list = $_goods['goods_list'];

        /* 检查此单发货数量填写是否正确 合并计算相同商品和货品 */
        if (!empty($send_number) && !empty($goods_list))
        {
            $goods_no_package = array();
            foreach ($goods_list as $key => $value)
            {
                /* 去除 此单发货数量 等于 0 的商品 */
                if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list']))
                {
                    // 如果是货品则键值为商品ID与货品ID的组合
                    $_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);

                    // 统计此单商品总发货数 合并计算相同ID商品或货品的发货数
                    if (empty($goods_no_package[$_key]))
                    {
                        $goods_no_package[$_key] = $send_number[$value['rec_id']];
                    }
                    else
                    {
                        $goods_no_package[$_key] += $send_number[$value['rec_id']];
                    }

                    //去除
                    if ($send_number[$value['rec_id']] <= 0)
                    {
                        unset($send_number[$value['rec_id']], $goods_list[$key]);
                        continue;
                    }
                }
                else
                {
                    /* 组合超值礼包信息 */
                    $goods_list[$key]['package_goods_list'] = package_goods($value['package_goods_list'], $value['goods_number'], $value['order_id'], $value['extension_code'], $value['goods_id']);

                    /* 超值礼包 */
                    foreach ($value['package_goods_list'] as $pg_key => $pg_value)
                    {
                        // 如果是货品则键值为商品ID与货品ID的组合
                        $_key = empty($pg_value['product_id']) ? $pg_value['goods_id'] : ($pg_value['goods_id'] . '_' . $pg_value['product_id']);

                        //统计此单商品总发货数 合并计算相同ID产品的发货数
                        if (empty($goods_no_package[$_key]))
                        {
                            $goods_no_package[$_key] = $send_number[$value['rec_id']][$pg_value['g_p']];
                        }
                        //否则已经存在此键值
                        else
                        {
                            $goods_no_package[$_key] += $send_number[$value['rec_id']][$pg_value['g_p']];
                        }

                        //去除
                        if ($send_number[$value['rec_id']][$pg_value['g_p']] <= 0)
                        {
                            unset($send_number[$value['rec_id']][$pg_value['g_p']], $goods_list[$key]['package_goods_list'][$pg_key]);
                        }
                    }

                    if (count($goods_list[$key]['package_goods_list']) <= 0)
                    {
                        unset($send_number[$value['rec_id']], $goods_list[$key]);
                        continue;
                    }
                }

                /* 发货数量与总量不符 */
                if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list']))
                {
                    $sended = order_delivery_num($order_id, $value['goods_id'], $value['product_id']);
                    if (($value['goods_number'] - $sended - $send_number[$value['rec_id']]) < 0)
                    {
                        /* 操作失败 */
                        $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                        sys_msg($_LANG['act_ship_num'], 1, $links);
                    }
                }
                else
                {
                    /* 超值礼包 */
                    foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value)
                    {
                        if (($pg_value['order_send_number'] - $pg_value['sended'] - $send_number[$value['rec_id']][$pg_value['g_p']]) < 0)
                        {
                            /* 操作失败 */
                            $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                            sys_msg($_LANG['act_ship_num'], 1, $links);
                        }
                    }
                }
            }
        }
        /* 对上一步处理结果进行判断 兼容 上一步判断为假情况的处理 */
        if (empty($send_number) || empty($goods_list))
        {
            /* 操作失败 */
            $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
            sys_msg($_LANG['act_false'], 1, $links);
        }

        /* 检查此单发货商品库存缺货情况 */
        /* $goods_list已经过处理 超值礼包中商品库存已取得 */
        $virtual_goods = array();
        $package_virtual_goods = array();
        foreach ($goods_list as $key => $value)
        {
            // 商品（超值礼包）
            if ($value['extension_code'] == 'package_buy')
            {
                foreach ($value['package_goods_list'] as $pg_key => $pg_value)
                {
                    if ($pg_value['goods_number'] < $goods_no_package[$pg_value['g_p']] && (($_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP) || ($_CFG['use_storage'] == '0' && $pg_value['is_real'] == 0)))
                    {
                        /* 操作失败 */
                        $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                        sys_msg(sprintf($_LANG['act_good_vacancy'], $pg_value['goods_name']), 1, $links);
                    }

                    /* 商品（超值礼包） 虚拟商品列表 package_virtual_goods*/
                    if ($pg_value['is_real'] == 0)
                    {
                        $package_virtual_goods[] = array(
                            'goods_id' => $pg_value['goods_id'],
                            'goods_name' => $pg_value['goods_name'],
                            'num' => $send_number[$value['rec_id']][$pg_value['g_p']]
                        );
                    }
                }
            }
            // 商品（虚货）
            elseif ($value['extension_code'] == 'virtual_card' || $value['is_real'] == 0)
            {
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('virtual_card') . " WHERE goods_id = '" . $value['goods_id'] . "' AND is_saled = 0 ";
                $num = $GLOBALS['db']->GetOne($sql);
                if (($num < $goods_no_package[$value['goods_id']]) && !($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE))
                {
                    /* 操作失败 */
                    $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                    sys_msg(sprintf($GLOBALS['_LANG']['virtual_card_oos'] . '【' . $value['goods_name'] . '】'), 1, $links);
                }

                /* 虚拟商品列表 virtual_card*/
                if ($value['extension_code'] == 'virtual_card')
                {
                    $virtual_goods[$value['extension_code']][] = array('goods_id' => $value['goods_id'], 'goods_name' => $value['goods_name'], 'num' => $send_number[$value['rec_id']]);
                }
            }
            // 商品（实货）、（货品）
            else
            {
                //如果是货品则键值为商品ID与货品ID的组合
                $_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);

                /* （实货） */
                if (empty($value['product_id']))
                {
                    $sql = "SELECT goods_number FROM " . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '" . $value['goods_id'] . "' LIMIT 0,1";
                }
                /* （货品） */
                else
                {
                    $sql = "SELECT product_number
                            FROM " . $GLOBALS['ecs']->table('products') ."
                            WHERE goods_id = '" . $value['goods_id'] . "'
                            AND product_id =  '" . $value['product_id'] . "'
                            LIMIT 0,1";
                }
                $num = $GLOBALS['db']->GetOne($sql);

                if (($num < $goods_no_package[$_key]) && $_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP)
                {
                    /* 操作失败 */
                    $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                    sys_msg(sprintf($_LANG['act_good_vacancy'], $value['goods_name']), 1, $links);
                }
            }
        }

        /* 生成发货单 */
        /* 获取发货单号和流水号 */
        $delivery['delivery_sn'] = get_delivery_sn();
        $delivery_sn = $delivery['delivery_sn'];
        /* 获取当前操作员 */
        $delivery['action_user'] = $_SESSION['admin_name'];
        /* 获取发货单生成时间 */
        $delivery['update_time'] = GMTIME_UTC;
        $delivery_time = $delivery['update_time'];
        $sql ="select add_time from ". $GLOBALS['ecs']->table('order_info') ." WHERE order_sn = '" . $delivery['order_sn'] . "'";
        $delivery['add_time'] =  $GLOBALS['db']->GetOne($sql);
        /* 获取发货单所属供应商 */
        $delivery['suppliers_id'] = $suppliers_id;
        /* 设置默认值 */
        $delivery['status'] = 2; // 正常
        $delivery['order_id'] = $order_id;
        /* 过滤字段项 */
        $filter_fileds = array(
            'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',
            'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',
            'email', 'zipcode', 'tel', 'mobile', 'best_time', 'postscript', 'insure_fee',
            'agency_id', 'delivery_sn', 'action_user', 'update_time',
            'suppliers_id', 'status', 'order_id', 'shipping_name'
        );
        $_delivery = array();
        foreach ($filter_fileds as $value)
        {
            $_delivery[$value] = $delivery[$value];
        }
        /* 发货单入库 */
        $query = $db->autoExecute($ecs->table('delivery_order'), $_delivery, 'INSERT', '', 'SILENT');
        $delivery_id = $db->insert_id();
        if ($delivery_id)
        {
            $delivery_goods = array();

            //发货单商品入库
            if (!empty($goods_list))
            {
                foreach ($goods_list as $value)
                {
                    // 商品（实货）（虚货）
                    if (empty($value['extension_code']) || $value['extension_code'] == 'virtual_card')
                    {
                        $delivery_goods = array('delivery_id' => $delivery_id,
                            'goods_id' => $value['goods_id'],
                            'product_id' => $value['product_id'],
                            'product_sn' => $value['product_sn'],
                            'goods_id' => $value['goods_id'],
                            'goods_name' => addslashes($value['goods_name']),
                            'brand_name' => addslashes($value['brand_name']),
                            'goods_sn' => $value['goods_sn'],
                            'send_number' => $send_number[$value['rec_id']],
                            'parent_id' => 0,
                            'is_real' => $value['is_real'],
                            'goods_attr' => addslashes($value['goods_attr'])
                        );

                        /* 如果是货品 */
                        if (!empty($value['product_id']))
                        {
                            $delivery_goods['product_id'] = $value['product_id'];
                        }

                        $query = $db->autoExecute($ecs->table('delivery_goods'), $delivery_goods, 'INSERT', '', 'SILENT');
                    }
                    // 商品（超值礼包）
                    elseif ($value['extension_code'] == 'package_buy')
                    {
                        foreach ($value['package_goods_list'] as $pg_key => $pg_value)
                        {
                            $delivery_pg_goods = array('delivery_id' => $delivery_id,
                                'goods_id' => $pg_value['goods_id'],
                                'product_id' => $pg_value['product_id'],
                                'product_sn' => $pg_value['product_sn'],
                                'goods_name' => $pg_value['goods_name'],
                                'brand_name' => '',
                                'goods_sn' => $pg_value['goods_sn'],
                                'send_number' => $send_number[$value['rec_id']][$pg_value['g_p']],
                                'parent_id' => $value['goods_id'], // 礼包ID
                                'extension_code' => $value['extension_code'], // 礼包
                                'is_real' => $pg_value['is_real']
                            );
                            $query = $db->autoExecute($ecs->table('delivery_goods'), $delivery_pg_goods, 'INSERT', '', 'SILENT');
                        }
                    }
                }
            }
        }
        else
        {
            /* 操作失败 */
            $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
            sys_msg($_LANG['act_false'], 1, $links);
        }
        unset($filter_fileds, $delivery, $_delivery, $order_finish);

        /* 定单信息更新处理 */
        if (true)
        {
            /* 定单信息 */
            $_sended = & $send_number;
            foreach ($_goods['goods_list'] as $key => $value)
            {
                if ($value['extension_code'] != 'package_buy')
                {
                    unset($_goods['goods_list'][$key]);
                }
            }
            foreach ($goods_list as $key => $value)
            {
                if ($value['extension_code'] == 'package_buy')
                {
                    unset($goods_list[$key]);
                }
            }
            $_goods['goods_list'] = $goods_list + $_goods['goods_list'];
            unset($goods_list);

            /* 更新订单的虚拟卡 商品（虚货） */
            $_virtual_goods = isset($virtual_goods['virtual_card']) ? $virtual_goods['virtual_card'] : '';
            update_order_virtual_goods($order_id, $_sended, $_virtual_goods);

            /* 更新订单的非虚拟商品信息 即：商品（实货）（货品）、商品（超值礼包）*/
            update_order_goods($order_id, $_sended, $_goods['goods_list']);

            /* 标记订单为已确认 “发货中” */
            /* 更新发货时间 */
            $order_finish = get_order_finish($order_id);
            $shipping_status = SS_SHIPPED_ING;
            if ($order['order_status'] != OS_CONFIRMED && $order['order_status'] != OS_SPLITED && $order['order_status'] != OS_SPLITING_PART)
            {
                $arr['order_status']    = OS_CONFIRMED;
                $arr['confirm_time']    = GMTIME_UTC;
            }
            $arr['order_status'] = $order_finish ? OS_SPLITED : OS_SPLITING_PART; // 全部分单、部分分单
            $arr['shipping_status']     = $shipping_status;
            update_order($order_id, $arr);
        }

        /* 记录log */
        order_action($order['order_sn'], $arr['order_status'], $shipping_status, $order['pay_status'], $action_note);
        /* 清除缓存 */
        clear_cache_files();
    }
    /* 设为未发货 */
    elseif ('unship' == $operation)
    {
        /* 检查权限 */
        admin_priv('order_ss_edit');

        /* 标记订单为“未发货”，更新发货时间, 订单状态为“确认” */
        update_order($order_id, array('shipping_status' => SS_UNSHIPPED, 'shipping_time' => 0, 'invoice_no' => '', 'order_status' => OS_CONFIRMED));

        /* 记录log */
        order_action($order['order_sn'], $order['order_status'], SS_UNSHIPPED, $order['pay_status'], $action_note);

        /* 如果订单用户不为空，计算积分，并退回 */
        if ($order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($order['user_id']);

            /* 计算并退回积分 */
            $integral = integral_to_give($order);
            log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf($_LANG['return_order_gift_integral'], $order['order_sn']));

            /* todo 计算并退回红包 */
            return_order_bonus($order_id);
        }

        /* 如果使用库存，则增加库存 */
        if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_SHIP)
        {
            change_order_goods_storage($order['order_id'], false, SDT_SHIP);
        }

        /* 删除发货单 */
        del_order_delivery($order_id);

        /* 将订单的商品发货数量更新为 0 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
                SET send_number = 0
                WHERE order_id = '$order_id'";
        $GLOBALS['db']->query($sql, 'SILENT');
        // 请求crm
        update_order_crm($order['order_sn']);
        /* 清除缓存 */
        clear_cache_files();
    }
    /* 收货确认 */
    elseif ('receive' == $operation)
    {
        /* 标记订单为“收货确认”，如果是货到付款，同时修改订单为已付款 */
        $arr = array('shipping_status' => SS_RECEIVED);
        $payment = payment_info($order['pay_id']);
        if ($payment['is_cod'])
        {
            $arr['pay_status'] = PS_PAYED;
            $order['pay_status'] = PS_PAYED;
        }
        if($order['mlm_id']){
			$sql = "select * from ".$GLOBALS['ecs']->table('mlm_order')." where order_id=".$order['order_id']." and rebate_time =0  for update ";
			$mlmOrder = $GLOBALS['db']->getRow($sql);
			if($mlmOrder){
			    $time = strtotime("+".$mlmOrderTimeLimit." days");
				$sql = "UPDATE ".$GLOBALS['ecs']->table('mlm_order'). " SET rebate_time = {$time}  WHERE id = {$mlmOrder['id']}";
				$res = $GLOBALS['db']->query($sql);
            }
        }

        update_order($order_id, $arr);
        // 请求crm
        update_order_crm($order['order_sn']);
        /* 记录log */
        order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], $action_note);
    }
    /* 取消 */
    elseif ('cancel' == $operation)
    {
        if($order['surplus_back']>0 || $order['cash_back']>0){
            sys_msg('该订单已经发生退款');
        }
        $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
        $db->query('START TRANSACTION');//开启事务
        /* 标记订单为“取消”，记录取消原因 */
        $cancel_note = isset($_REQUEST['cancel_note']) ? trim($_REQUEST['cancel_note']) : '';
        $pay_status = $order['pay_status'];
        $arr = array(
            'order_status'  => OS_CANCELED,
            'to_buyer'      => $cancel_note,
            'pay_status'    => PS_UNPAYED,
            'shipping_status' => SS_UNSHIPPED,
            //'pay_time'      => 0,
            //'money_paid'    => 0,
            //'order_amount'  => $order['money_paid'],
            "surplus_back"  => $order['surplus'],
            "cash_back"     => $order['money_paid'],
            'order_type'    => $order['order_type'],
        );
        $update_order_res = update_order($order_id, $arr);
        if(!$update_order_res){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('订单更新失败');
        }

		/**已结算订单需要从商家账户扣回退款**/

		if($order['suppliers_account_log_id']>0){
			$res = dealOrderToSuppliersSettlement($order['suppliers_id'],$order['order_id'],$order['surplus'],$order['money_paid'],'取消操作', $order['token_type']);
			if(!$res){
				$db->query("ROLLBACK"); //事务回滚
				sys_msg('已结算订单扣除商家账户退款失败');
			}
		}

		/**mlm订单特殊处理**/

		if($order['mlm_id']>0){

			$sql = "select * from ".$GLOBALS['ecs']->table('mlm_order')." where order_id=".$order['order_id']." for update ";
			$mlmOrder = $GLOBALS['db']->getRow($sql);
            if($mlmOrder && $pay_status==PS_PAYED && $order['surplus']>0){
				$change_desc = ($mlmOrder['user_id']?'付佣好友:'.dealUserAlias(user_info($mlmOrder['user_id'])['alias'])."\n":'').'取消订单:'.$order['order_sn'];

                $realMoneyPaid = $order['money_paid'];
				$order['money_paid'] +=$order['surplus'];
                $order['surplus']=0;

                // 判断是否发生过退款，是则直接回滚
                if($mlmOrder['rebate_back'] > 0 || $mlmOrder['pay_cash_back'] > 0){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('该订单已发生过退款，mlm_order_id:'.$mlmOrder['id']);
                }

                $now = time();
                $sql = "UPDATE ".$GLOBALS['ecs']->table('mlm_order'). " SET order_status = 2, rebate_back = {$mlmOrder['rebate']}, pay_cash_back = {$mlmOrder['pay_cash']}, updated_at = {$now} WHERE id = {$mlmOrder['id']}";
                $res = $GLOBALS['db']->query($sql);
                if(!$res){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('更新mlm订单状态失败');
                }

                if ($mlmOrder['custom_profit'] < 0) {
                    $actCustomAmount = $mlmOrder['act_rebate'];
                } else {
                    $actCustomAmount = bcadd($mlmOrder['act_rebate'], $mlmOrder['custom_profit'], 2);
                }

				//代付状态判断  已代付
                if($mlmOrder['pay_status'] == 1){
                    // 扣减换换客冻结现金
					$res = changeMlmUserAccount($mlmOrder['user_id'], $mlmOrder['id'],  0, -$mlmOrder['rebate'], 2, 3, 1, $change_desc); // 221 还是 231

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
                        sys_msg($res['info']);
                    }

                    // 增加换换客权益币
                    $res = changeUserAccount($mlmOrder['user_id'], $mlmOrder['do_surplus'], 0, 0, 0, $change_desc, 95);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

                    // 减去平台多得现金
                    $subPlatMoney = bcsub($mlmOrder['rebate'], bcsub($mlmOrder['pay_cash'], $realMoneyPaid, 2), 2);
                    $res = changeMlmUserAccount($platform_user_id, $mlmOrder['id'],  0, -$subPlatMoney, 1, 2, 0, $change_desc); // 221 还是 231

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

                    // 减去平台多得权益币
                    $res = changeUserAccount($platform_user_id, -bcsub($mlmOrder['do_surplus'], $mlmOrder['mlm_surplus'], 2), 0, 0, 0, $change_desc, 95);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

                }else{
                    // 扣减换换客活动佣金
                    $flatSubForzen = $mlmOrder['mlm_surplus'];
                    if($actCustomAmount > 0){
                        $res = changeMlmUserAccount($mlmOrder['user_id'], $mlmOrder['id'],  0, -$actCustomAmount, 2, 3, 1, $change_desc); // 221 还是 231

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }

                        $flatSubForzen = bcsub(bcsub($mlmOrder['pay_cash'], $realMoneyPaid, 2), $actCustomAmount, 2);
                    }

                    // 扣减平台冻结现金
                    $flatSubForzen < 0 ? $unPayChangeType = 2 : $unPayChangeType = 1;
					$res = changeMlmUserAccount($platform_user_id, $mlmOrder['id'], 0, -$flatSubForzen, $unPayChangeType, 2, 1, $change_desc);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

					/**增加平台权益币**/
					$res = changeUserAccount($platform_user_id, $mlmOrder['mlm_surplus'], 0, 0, 0, $change_desc, 95);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
					}
                }
			}
		}

        /* todo 处理退款 */
        if ($order['money_paid'] > 0)
        {
            //$refund_type = $_REQUEST['refund'];
            //现金退款
            $refund_type = 4;
            $refund_note = $_REQUEST['refund_note'];

            $order_refund_res = order_refund($order, $refund_type, $refund_note);
            if(!$order_refund_res){
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('订单退款失败');
            }
        }

		/* 记录log */
        $order_action_res = order_action($order['order_sn'], OS_CANCELED, SS_UNSHIPPED, PS_UNPAYED, $action_note);
        if(!$order_action_res){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('订单更新失败');
        }
        /* 如果使用库存，且下订单时减库存，则增加库存 */
        if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
        {
            // 取消时过滤掉不加库存的订单
            $change_order_goods_storage_res = change_order_goods_storage($order_id, false, SDT_PLACE);
            if(!$change_order_goods_storage_res)
            {
                $db->query("ROLLBACK"); //事务回滚
                sys_msg('订单更新失败');
            }
            if($pay_status == PS_PAYED || ($order['order_type'] == OT_INSTALMENT && $pay_status == PS_PAYING && $instalment_order[0]['pay_status'] == PS_PAYED)){
                $goods = $db->getRow("SELECT goods_id,goods_number FROM " . $ecs->table('order_goods') . " WHERE order_id = '$order_id'");
                $sql = "select sort_sales from ".$ecs->table('goods')." where goods_id=".$goods['goods_id'];
                $sort_sale = $db->getOne($sql);
                if(intval($sort_sale) >= $goods['goods_number'] ){
                    $sql = "UPDATE ".$ecs->table('goods'). " SET sort_sales = sort_sales - " . $goods['goods_number'] . " WHERE goods_id = '" . $goods['goods_id'] ."' ";
                    $sort_sales_res = $db->query($sql);
                    if(!$sort_sales_res){
                        $db->query("ROLLBACK"); //事务回滚
                        sys_msg($val.'减少销量失败');
                    }
                }
            }
        }

        /* 退还用户余额、积分、红包 */
        $return_user_surplus_integral_bonus_res = return_user_surplus_integral_bonus($order, $instalment_order[0]);
        if(!$return_user_surplus_integral_bonus_res)
        {
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('订单更新失败');
        }
        /* 发送邮件 */
        $cfg = $_CFG['send_cancel_email'];
        if ($cfg == '1')
        {
            $tpl = get_mail_template('order_cancel');
            $smarty->assign('order', $order);
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', local_date($_CFG['date_format']));
            $smarty->assign('sent_date', local_date($_CFG['date_format']));
            $content = $smarty->fetch('str:' . $tpl['template_content']);
            if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $msg = $_LANG['send_mail_fail'];
            }
        }
        error_log("\r\ncancel----",3,__FILE__.".log");
        // 请求crm
        $update_order_crm_res = update_order_crm($order['order_sn']);
        if(!$update_order_crm_res){
            $db->query("ROLLBACK"); //事务回滚
            sys_msg('订单更新失败');
        }
        // 退款请求crm
        $data['order_id']=$order['order_sn'];
        $data['cur_money']=$order['total_fee'];
        send_refund_to_crm($data);
        // 通知erp取消订单
        // include_once(ROOT_PATH . 'includes/cls_matrix.php');
        $matrix = new matrix();
        $bind_info = $matrix->get_bind_info(array('ecos.ome'));
        if($bind_info){
            $matrix->set_dead_order($order_id);
        }
        $db->query("COMMIT"); //提交事务
    }
    /* 设为无效 */
    elseif ('invalid' == $operation)
    {
        if($order['surplus_back']>0 || $order['cash_back']>0){
            sys_msg('该订单已经发生退款');
        }

        /* 标记订单为“无效”、“未付款” */
        update_order($order_id, array('order_status' => OS_INVALID));

        /* 记录log */
        order_action($order['order_sn'], OS_INVALID, $order['shipping_status'], PS_UNPAYED, $action_note);

        /* 如果使用库存，且下订单时减库存，则增加库存 */
        if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
        {
            change_order_goods_storage($order_id, false, SDT_PLACE);
        }

        /* 发送邮件 */
        $cfg = $_CFG['send_invalid_email'];
        if ($cfg == '1')
        {
            $tpl = get_mail_template('order_invalid');
            $smarty->assign('order', $order);
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', local_date($_CFG['date_format']));
            $smarty->assign('sent_date', local_date($_CFG['date_format']));
            $content = $smarty->fetch('str:' . $tpl['template_content']);
            if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $msg = $_LANG['send_mail_fail'];
            }
        }

        /* 退货用户余额、积分、红包 */
        return_user_surplus_integral_bonus($order);
        // 更新订单crm
        update_order_crm($order['order_sn']);
        // 退款请求crm
        if ($order['pay_status'] != PS_UNPAYED) {
            $data['order_id'] = $order['order_sn'];
            $data['cur_money'] = $order['total_fee'];
            send_refund_to_crm($data);
        }
        // 通知erp取消订单
        // include_once(ROOT_PATH . 'includes/cls_matrix.php');
        $matrix = new matrix();
        $matrix->set_dead_order($order_id);
    }
    /* 退货 */
    elseif ('return' == $operation) {
    	/* 定义当前时间 */
    	define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

    	/* 过滤数据 */
    	$_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : '';
    	$_REQUEST['refund_note'] = isset($_REQUEST['refund_note']) ? $_REQUEST['refund'] : '';

        if($order['surplus_back']>0 || $order['cash_back']>0){
            sys_msg('该订单已经发生退款');
        }
    	$db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    	$db->query('START TRANSACTION');//开启事务

		$pay_status = $order['pay_status'];

    	$arr = array('order_status' => OS_RETURNED,
    			'pay_status' => PS_UNPAYED,
    			'shipping_status' => SS_UNSHIPPED,
    			//'money_paid' => 0, 退货不改变金额20190208
    			//'invoice_no' => '',
    			//'order_amount' => $order['money_paid'],
                'surplus_back' => $order['surplus'],
                'cash_back' => $order['money_paid'],
    			);
    	$update_order_info_res = update_order($order_id, $arr);
    	if(!$update_order_info_res){
    		$db->query("ROLLBACK"); //事务回滚
    		sys_msg('更新订单信息失败');
    	}
		/**todo 已结算订单需要从商家账户扣回退款 **/

		if($order['suppliers_account_log_id']>0){
			$res = dealOrderToSuppliersSettlement($order['suppliers_id'],$order['order_id'],$order['surplus'],$order['money_paid'],'退货操作', $order['token_type']);
			if(!$res){
				$db->query("ROLLBACK"); //事务回滚
				sys_msg('已结算订单扣除商家账户退款失败');
			}
		}

        // mlm订单处理
		if($order['mlm_id']>0){
			$sql = "select * from ".$GLOBALS['ecs']->table('mlm_order')." where order_id=".$order['order_id']." for update ";
			$mlmOrder = $GLOBALS['db']->getRow($sql);
            if($mlmOrder && $pay_status==PS_PAYED && $order['surplus']>0){
				$change_desc = ($mlmOrder['user_id']?'付佣好友:'.dealUserAlias(user_info($mlmOrder['user_id'])['alias'])."\n":'').'取消订单:'.$order['order_sn'];

                $realMoneyPaid = $order['money_paid'];
				$order['money_paid'] +=$order['surplus'] ;
                $order['surplus']=0;

                // 判断是否发生过退款，是则直接回滚
                if($mlmOrder['rebate_back'] > 0 || $mlmOrder['pay_cash_back'] > 0){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('该订单已发生过退款，mlm_order_id:'.$mlmOrder['id']);
                }

                $now = time();
                $sql = "UPDATE ".$GLOBALS['ecs']->table('mlm_order'). " SET order_status = 2, rebate_back = {$mlmOrder['rebate']}, pay_cash_back = {$mlmOrder['pay_cash']}, updated_at = {$now} WHERE id = {$mlmOrder['id']}";
                $res = $GLOBALS['db']->query($sql);
                if(!$res){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('更新mlm订单状态失败');
                }

                if ($mlmOrder['custom_profit'] < 0) {
                    $actCustomAmount = $mlmOrder['act_rebate'];
                } else {
                    $actCustomAmount = bcadd($mlmOrder['act_rebate'], $mlmOrder['custom_profit'], 2);
                }

				//代付状态判断  已代付
                if($mlmOrder['pay_status'] == 1){
                    // 扣减换换客冻结现金
					$res = changeMlmUserAccount($mlmOrder['user_id'], $mlmOrder['id'],  0, -$mlmOrder['rebate'], 2, 3, 1, $change_desc); // 221 还是 231

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
                        sys_msg($res['info']);
                    }

                    // 增加换换客权益币
                    $res = changeUserAccount($mlmOrder['user_id'], $mlmOrder['do_surplus'], 0, 0, 0, $change_desc, 95);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

                    // 减去平台多得现金
                    $subPlatMoney = bcsub($mlmOrder['rebate'], bcsub($mlmOrder['pay_cash'],$realMoneyPaid, 2), 2);
                    $res = changeMlmUserAccount($platform_user_id, $mlmOrder['id'],  0, -$subPlatMoney, 1, 2, 0, $change_desc); // 221 还是 231

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

                    // 减去平台多得权益币
                    $res = changeUserAccount($platform_user_id, -bcsub($mlmOrder['do_surplus'], $mlmOrder['mlm_surplus'], 2), 0, 0, 0, $change_desc, 95);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

                }else{
                    // 扣减换换客活动佣金
                    $flatSubForzen = $mlmOrder['mlm_surplus'];
                    if($actCustomAmount > 0){
                        $res = changeMlmUserAccount($mlmOrder['user_id'], $mlmOrder['id'],  0, -$actCustomAmount, 2, 3, 1, $change_desc); // 221 还是 231

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }

                        $flatSubForzen = bcsub(bcsub($mlmOrder['pay_cash'], $realMoneyPaid, 2), $actCustomAmount, 2);
                    }

					// 扣减平台冻结现金
                    $flatSubForzen < 0 ? $unPayChangeType = 2 : $unPayChangeType = 1;
					$res = changeMlmUserAccount($platform_user_id, $mlmOrder['id'], 0, -$flatSubForzen, $unPayChangeType, 2, 1, $change_desc);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
                    }

					/**增加平台权益币**/
					$res = changeUserAccount($platform_user_id, $mlmOrder['mlm_surplus'], 0, 0, 0, $change_desc, 95);

                    if($res['code']){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg($res['info']);
					}
                }
			}
		}


    	/* todo 处理退款 */
    	if ($order['pay_status'] != PS_UNPAYED && $order['money_paid'] > 0) {
    		//$refund_type = $_REQUEST['refund'];
            $refund_type = 4;
    		$refund_note = $_REQUEST['refund_note'];
    		$refund_res = order_refund($order, $refund_type, $refund_note);
    		if(!$refund_res){
    			$db->query("ROLLBACK"); //事务回滚
    			sys_msg('订单信息退款更新失败');
    		}
    		error_log(var_export($order, 1), 3, __FILE__ . ".log");
    		error_log("\r\n", 3, __FILE__ . ".log");
    		error_log(var_export($refund_type, 1), 3, __FILE__ . ".log");
    		error_log("\r\n", 3, __FILE__ . ".log");
    		error_log(var_export($refund_note, 1), 3, __FILE__ . ".log");
    	}

    	order_action($order['order_sn'], OS_RETURNED, SS_UNSHIPPED, PS_UNPAYED, $action_note);


    	/* 如果订单用户不为空，计算积分，并退回 */
    	if ($order['user_id'] > 0) {
    		/* 取得用户信息 */
    		$user = user_info($order['user_id']);

    		$sql = "SELECT  goods_number, send_number FROM" . $GLOBALS['ecs']->table('order_goods') . "
                WHERE order_id = '" . $order['order_id'] . "'";

    		$goods_num = $db->query($sql);
    		$goods_num = $db->fetchRow($goods_num);

    		if ($goods_num['goods_number'] == $goods_num['send_number']) {
    			/* 计算并退回积分 */
    			$integral = integral_to_give($order);
    			log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf($_LANG['return_order_gift_integral'], $order['order_sn']));
    		}
    		/* todo 计算并退回红包 */
    		$return_bonus_res = return_order_bonus($order_id);
    		if(!$return_bonus_res){
    			$db->query("ROLLBACK"); //事务回滚
    			sys_msg('红包退回失败');
    		}

    	}

    	/* 如果使用库存，则增加库存（不论何时减库存都需要） */
    	if ($_CFG['use_storage'] == '1') {
    		if ($_CFG['stock_dec_time'] == SDT_SHIP) {
    			$change_storage_res = change_order_goods_storage($order['order_id'], false, SDT_SHIP);
    			if(!$change_storage_res){
    				$db->query("ROLLBACK"); //事务回滚
    				sys_msg('库存更新是失败');
    			}

    		} elseif ($_CFG['stock_dec_time'] == SDT_PLACE) {
    			$change_storage_res = change_order_goods_storage($order['order_id'], false, SDT_PLACE);
    			if(!$change_storage_res){
    				$db->query("ROLLBACK"); //事务回滚
    				sys_msg('库存更新是失败');
    			}
    		}

            if($pay_status == PS_PAYED ){
                $goods = $db->getRow("SELECT goods_id,goods_number FROM " . $ecs->table('order_goods') . " WHERE order_id = '$order_id'");
                $sql = "select sort_sales from ".$ecs->table('goods')." where goods_id=".$goods['goods_id'];
                $sort_sale = $db->getOne($sql);
                if(intval($sort_sale) >= $goods['goods_number'] ){
                    $sql = "UPDATE ".$ecs->table('goods'). " SET sort_sales = sort_sales - " . $goods['goods_number'] . " WHERE goods_id = '" . $goods['goods_id'] ."' ";
                    $sort_sales_res = $db->query($sql);
                    if(!$sort_sales_res){
                        $db->query("ROLLBACK"); //事务回滚
                        sys_msg($val.'减少销量失败');
                    }
                }
            }


    	}

    	/* 退货用户余额、积分、红包 */
    	$return_user_bonus_res = return_user_surplus_integral_bonus($order);	//退货已经存在的关于钱的数据不变,改变状态即可
    	if(!$return_user_bonus_res){
    		$db->query("ROLLBACK"); //事务回滚
    		sys_msg('用户奖励类数据更新是失败');
    	}


    	/* 获取当前操作员 */
    	$delivery['action_user'] = $_SESSION['admin_name'];
    	/* 添加退货记录 */
    	$delivery_list = array();
    	$sql_delivery = "SELECT *
                         FROM " . $ecs->table('delivery_order') . "
                         WHERE status IN (0, 2)
                         AND order_id = " . $order['order_id'];
    	$delivery_list = $GLOBALS['db']->getAll($sql_delivery);
    	if ($delivery_list) {
    		foreach ($delivery_list as $list) {
    			$sql_back = "INSERT INTO " . $ecs->table('back_order') . " (delivery_sn, order_sn, order_id, add_time, shipping_id, user_id, action_user, consignee, address, Country, province, City, district, sign_building, Email,Zipcode, Tel, Mobile, best_time, postscript, how_oos, insure_fee, shipping_fee, update_time, suppliers_id, return_time, agency_id, invoice_no) VALUES ";

    			$sql_back .= " ( '" . $list['delivery_sn'] . "', '" . $list['order_sn'] . "',
                              '" . $list['order_id'] . "', '" . $list['add_time'] . "',
                              '" . $list['shipping_id'] . "', '" . $list['user_id'] . "',
                              '" . $delivery['action_user'] . "', '" . $list['consignee'] . "',
                              '" . $list['address'] . "', '" . $list['country'] . "', '" . $list['province'] . "',
                              '" . $list['city'] . "', '" . $list['district'] . "', '" . $list['sign_building'] . "',
                              '" . $list['email'] . "', '" . $list['zipcode'] . "', '" . $list['tel'] . "',
                              '" . $list['mobile'] . "', '" . $list['best_time'] . "', '" . $list['postscript'] . "',
                              '" . $list['how_oos'] . "', '" . $list['insure_fee'] . "',
                              '" . $list['shipping_fee'] . "', '" . $list['update_time'] . "',
                              '" . $list['suppliers_id'] . "', '" . GMTIME_UTC . "',
                              '" . $list['agency_id'] . "', '" . $list['invoice_no'] . "'
                              )";
    			$back_res = $GLOBALS['db']->query($sql_back, 'SILENT');
    			if(!$back_res){
    				$db->query("ROLLBACK"); //事务回滚
    				sys_msg('添加退货记录失败');
    			}

    			$back_id = $GLOBALS['db']->insert_id();
    			$sql_back_goods = "INSERT INTO " . $ecs->table('back_goods') . " (back_id, goods_id, product_id, product_sn, goods_name,goods_sn, is_real, send_number, goods_attr)
    			SELECT '$back_id', goods_id, product_id, product_sn, goods_name, goods_sn, is_real, send_number, goods_attr
    			FROM " . $ecs->table('delivery_goods') . "
    			WHERE delivery_id = " . $list['delivery_id'];
                $back_goods_res =  $GLOBALS['db']->query($sql_back_goods, 'SILENT');
                if(!$back_goods_res){
                	$db->query("ROLLBACK"); //事务回滚
                	sys_msg('添加退货记录失败');
                }

    		}
    	}

    	/* 修改订单的发货单状态为退货 */
    	$sql_delivery = "UPDATE " . $ecs->table('delivery_order') . "
                         SET status = 1
                         WHERE status IN (0, 2)
                         AND order_id = " . $order['order_id'];
    	$delivery_res = $GLOBALS['db']->query($sql_delivery, 'SILENT');
    	if(!$delivery_res){
    		$db->query("ROLLBACK"); //事务回滚
    		sys_msg('发货单状态更新失败');
    	}


    	/* 将订单的商品发货数量更新为 0 */
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
    	SET send_number = 0
    	WHERE order_id = '$order_id'";
    	$update_send_res = $GLOBALS['db']->query($sql, 'SILENT');
    	if($update_send_res===false){
    		$db->query("ROLLBACK"); //事务回滚
    		sys_msg('订单的商品发货数量更新失败');
    	}
    	// 更新订单crm
    	$is_succ = update_order_crm($order['order_sn']);
    	if ($is_succ){
    		sleep(3);
    		// 退款请求crm
    		$data['order_id'] = $order['order_sn'];
    		$data['cur_money'] = $order['total_fee'];
    		send_refund_to_crm($data);
    	}

    	$db->query("COMMIT"); //提交事务
    	/* 清除缓存 */
    	clear_cache_files();

    }
    elseif('return_old' == $operation)
    {	//未添加混合支付时的退货
        if($order['surplus_back']>0 || $order['cash_back']>0){
            sys_msg('该订单已经发生退款');
        }

        /* 定义当前时间 */
    	define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

    	/* 过滤数据 */
    	$_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : '';
    	$_REQUEST['refund_note'] = isset($_REQUEST['refund_note']) ? $_REQUEST['refund'] : '';

    	/* 标记订单为“退货”、“未付款”、“未发货” */
    	$arr = array('order_status' => OS_RETURNED,
    			'pay_status' => PS_UNPAYED,
    			'shipping_status' => SS_UNSHIPPED,
    			'money_paid' => 0,
    			'invoice_no' => '',
    			'order_amount' => $order['money_paid']);
    	update_order($order_id, $arr);

    	/* todo 处理退款 */
    	if ($order['pay_status'] != PS_UNPAYED) {
    		$refund_type = $_REQUEST['refund'];
    		$refund_note = $_REQUEST['refund'];
    		order_refund($order, $refund_type, $refund_note);
    		error_log(var_export($order, 1), 3, __FILE__ . ".log");
    		error_log("\r\n", 3, __FILE__ . ".log");
    		error_log(var_export($refund_type, 1), 3, __FILE__ . ".log");
    		error_log("\r\n", 3, __FILE__ . ".log");
    		error_log(var_export($refund_note, 1), 3, __FILE__ . ".log");
    	}

    	/* 记录log */
    	order_action($order['order_sn'], OS_RETURNED, SS_UNSHIPPED, PS_UNPAYED, $action_note);

    	/* 如果订单用户不为空，计算积分，并退回 */
    	if ($order['user_id'] > 0) {
    		/* 取得用户信息 */
    		$user = user_info($order['user_id']);

    		$sql = "SELECT  goods_number, send_number FROM" . $GLOBALS['ecs']->table('order_goods') . "
                WHERE order_id = '" . $order['order_id'] . "'";

    		$goods_num = $db->query($sql);
    		$goods_num = $db->fetchRow($goods_num);

    		if ($goods_num['goods_number'] == $goods_num['send_number']) {
    			/* 计算并退回积分 */
    			$integral = integral_to_give($order);
    			log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf($_LANG['return_order_gift_integral'], $order['order_sn']));
    		}
    		/* todo 计算并退回红包 */
    		return_order_bonus($order_id);

    	}

    	/* 如果使用库存，则增加库存（不论何时减库存都需要） */
    	if ($_CFG['use_storage'] == '1') {
    		if ($_CFG['stock_dec_time'] == SDT_SHIP) {
    			change_order_goods_storage($order['order_id'], false, SDT_SHIP);
    		} elseif ($_CFG['stock_dec_time'] == SDT_PLACE) {
    			change_order_goods_storage($order['order_id'], false, SDT_PLACE);
    		}
    	}

    	/* 退货用户余额、积分、红包 */
    	return_user_surplus_integral_bonus($order);

    	/* 获取当前操作员 */
    	$delivery['action_user'] = $_SESSION['admin_name'];
    	/* 添加退货记录 */
    	$delivery_list = array();
    	$sql_delivery = "SELECT *
                         FROM " . $ecs->table('delivery_order') . "
                         WHERE status IN (0, 2)
                         AND order_id = " . $order['order_id'];
    	$delivery_list = $GLOBALS['db']->getAll($sql_delivery);
    	if ($delivery_list) {
    		foreach ($delivery_list as $list) {
    			$sql_back = "INSERT INTO " . $ecs->table('back_order') . " (delivery_sn, order_sn, order_id, add_time, shipping_id, user_id, action_user, consignee, address, Country, province, City, district, sign_building, Email,Zipcode, Tel, Mobile, best_time, postscript, how_oos, insure_fee, shipping_fee, update_time, suppliers_id, return_time, agency_id, invoice_no) VALUES ";

    			$sql_back .= " ( '" . $list['delivery_sn'] . "', '" . $list['order_sn'] . "',
                              '" . $list['order_id'] . "', '" . $list['add_time'] . "',
                              '" . $list['shipping_id'] . "', '" . $list['user_id'] . "',
                              '" . $delivery['action_user'] . "', '" . $list['consignee'] . "',
                              '" . $list['address'] . "', '" . $list['country'] . "', '" . $list['province'] . "',
                              '" . $list['city'] . "', '" . $list['district'] . "', '" . $list['sign_building'] . "',
                              '" . $list['email'] . "', '" . $list['zipcode'] . "', '" . $list['tel'] . "',
                              '" . $list['mobile'] . "', '" . $list['best_time'] . "', '" . $list['postscript'] . "',
                              '" . $list['how_oos'] . "', '" . $list['insure_fee'] . "',
                              '" . $list['shipping_fee'] . "', '" . $list['update_time'] . "',
                              '" . $list['suppliers_id'] . "', '" . GMTIME_UTC . "',
                              '" . $list['agency_id'] . "', '" . $list['invoice_no'] . "'
                              )";
    			$GLOBALS['db']->query($sql_back, 'SILENT');
    			$back_id = $GLOBALS['db']->insert_id();

    			$sql_back_goods = "INSERT INTO " . $ecs->table('back_goods') . " (back_id, goods_id, product_id, product_sn, goods_name,goods_sn, is_real, send_number, goods_attr)
    			SELECT '$back_id', goods_id, product_id, product_sn, goods_name, goods_sn, is_real, send_number, goods_attr
    			FROM " . $ecs->table('delivery_goods') . "
    			WHERE delivery_id = " . $list['delivery_id'];
                $GLOBALS['db']->query($sql_back_goods, 'SILENT');
    		}
    		}

    		/* 修改订单的发货单状态为退货 */
    		$sql_delivery = "UPDATE " . $ecs->table('delivery_order') . "
    		SET status = 1
    		WHERE status IN (0, 2)
    		AND order_id = " . $order['order_id'];
    				$GLOBALS['db']->query($sql_delivery, 'SILENT');

        /* 将订单的商品发货数量更新为 0 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
    	        SET send_number = 0
    	        WHERE order_id = '$order_id'";
    	        $GLOBALS['db']->query($sql, 'SILENT');
    	        // 更新订单crm
    	        $is_succ = update_order_crm($order['order_sn']);
    	        if ($is_succ){
    	        sleep(3);
    	        // 退款请求crm
    	        $data['order_id'] = $order['order_sn'];
    	        $data['cur_money'] = $order['total_fee'];
    	        send_refund_to_crm($data);
    	}
    	/* 清除缓存 */
    	clear_cache_files();
    }
 	elseif ('after_service' == $operation)
    {
    	//新增售后退款（只有在发货并且售后付款不为空的情况下才执行退款）
        //$after_service_refund_amount = isset($_REQUEST['after_service_refund_amount']) ? $_REQUEST['after_service_refund_amount'] : '';
        $pay_status = $order['pay_status'];
        $realMoneyPaid = $order['money_paid'];

        $after_service_cash_back = isset($_REQUEST['after_service_cash_back']) ? $_REQUEST['after_service_cash_back'] : 0;
        $after_service_surplus_back = isset($_REQUEST['after_service_surplus_back']) ? $_REQUEST['after_service_surplus_back'] : 0;
        $after_service_refund_amount = bcadd($after_service_cash_back,$after_service_surplus_back,2);
        if($after_service_refund_amount <= 0){
            sys_msg('请填写正确的退款金额');
        }
        $after_service_refund_note = isset($_REQUEST['after_service_refund_note']) ? $_REQUEST['after_service_refund_note'] : '';
        if (!empty($after_service_refund_amount) && $order['shipping_status'] > SS_UNSHIPPED)
        {
            $refund_type = 1;
            if(empty($after_service_refund_note))
            {
                $refund_note = '[' . $_LANG['op_after_service'] . '] ' . $action_note;
            }
            else
            {
                $refund_note = '[' . $_LANG['op_after_service'] . '] ' .$after_service_refund_note;
            }


            $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
            $db->query('START TRANSACTION');//开启事务

            /* 是否分销返佣
            if ($order['mlm_id'] > 0) {
                $sql = "SELECT log_time FROM " . $ecs->table('order_action') . " WHERE order_id = '$order_id' AND order_status in (1,5,6)  AND shipping_status = 2 AND pay_status = 2  ORDER BY log_time ASC limit 1";
                $confirm_time = $db->getOne($sql);
                if (!empty($confirm_time) && strtotime("+".$mlmOrderTimeLimit."days", $confirm_time) < time()) {
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg("分销返佣订单确认收货7天后不可进行售后操作");
                }
            }*/
            $log = '{退款明细：';
            /* 处理权益币*/
            if($after_service_surplus_back > 0){
            	if($order['surplus_back']+$after_service_surplus_back > $order['surplus']){
            		$db->query("ROLLBACK"); //事务回滚
            		sys_msg("积分退还数量超过用户剩余可退数量");
            	}
            	$update_surplus_res =  order_refund($order, $refund_type, $refund_note, $after_service_surplus_back,ACT_REFUND);
            	if(!$update_surplus_res){
            		$db->query("ROLLBACK"); //事务回滚
            		sys_msg('积分退回到用户更新失败');
            	}
            	$log .= '退还积分：'.$after_service_surplus_back;
            }
            /* 处理现金*/
            if($after_service_cash_back > 0 ){
            	if($order['cash_back'] > 0){
            		$db->query("ROLLBACK"); //todo 注意 这里不能放开，如果要改，需要配合appserver
            		sys_msg("该笔订单已进行过售后操作[现金退回]");
                }

                /* 取得该笔订单现金支付成功记录的信息 */
                $sql = "SELECT *  FROM " . $GLOBALS['ecs']->table('cash_payment_log') .
                " WHERE status = 1 and user_id = {$order['user_id']} and order_sn = '{$order['order_sn']}'";
                $order_cash_payment_log = $GLOBALS['db']->getRow($sql);
                if(empty($order_cash_payment_log)){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg("未查询到UserId:{$order['user_id']}用户该笔订单号现金支付成功的流水");
                }

                // 如果是分销订单，则退款总额 = 分销价 - (商家底价 - POST价格)
                $mlm_after_service_cash_back = 0;
                if($order['mlm_id'] > 0){
                    $sql = "select * from ".$GLOBALS['ecs']->table('mlm_order')." where order_id=".$order['order_id']." for update ";
					$mlmOrder = $GLOBALS['db']->getRow($sql);
                    $mlm_after_service_cash_back = bcsub($mlmOrder['pay_cash'], bcsub(bcadd($order['surplus'], $order['money_paid'], 2), $after_service_cash_back,2), 2);
                }

                /* 插入权益币帐户变动记录 */
                $cash_payment_log = array(
                    'order_sn'  => $order['order_sn'],
                    'order_id' => $order['order_id'],
                    'trade_no'  => genRandomString(3),
                    'suppliers_id'   => $order_cash_payment_log['suppliers_id'],
                    'user_id'   => $order_cash_payment_log['user_id'],
                    'status'    => 3,
                    'money'     => $mlm_after_service_cash_back > 0 ? $mlm_after_service_cash_back : $after_service_cash_back,
                    'pay_type'  => $order_cash_payment_log['pay_type'],
                    'pay_code'  => $order_cash_payment_log['pay_code'],
                    'is_new_pay'=> $order_cash_payment_log['is_new_pay'],
                    'remark'    => $refund_note,
                    'addtime'   => gmtime()
                );

                $insert_payment_log_res = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cash_payment_log'), $cash_payment_log, 'INSERT');
                if(empty($insert_payment_log_res)){
                    $db->query("ROLLBACK"); //事务回滚
                    sys_msg('现金退回申请失败');
                }
                $log .= '退还现金：'.$after_service_cash_back;

            }
            /* 更新info表 */
            if($after_service_surplus_back > 0 || $after_service_cash_back > 0){

				/**mlm订单特殊处理**/

				// TODO $init_after_service_cash_back 为退现金总额（分销订单部分换客收益全退）
				if($order['mlm_id'] > 0 && $after_service_cash_back > 0 && $pay_status == PS_PAYED){
				    //输入的退现金金额
					$init_after_service_cash_back  = $after_service_cash_back ;

					$after_service_surplus_back  = getScaleNumber($order['surplus'], $init_after_service_cash_back, $order['surplus'] + $order['money_paid']);
					//TODO 此处为分摊后的现金
					$after_service_cash_back     = bcsub($init_after_service_cash_back, $after_service_surplus_back, 2);

					$sql = "select * from ".$GLOBALS['ecs']->table('mlm_order')." where order_id=".$order['order_id']." for update ";
                    $mlmOrder = $GLOBALS['db']->getRow($sql);

                    if(!$mlmOrder){
                        $db->query("ROLLBACK"); //事务回滚
                        sys_msg('不存在分销订单，mlm_order_id:'.$mlmOrder['id']);
                    }

                    //定义退款描述
                    $now = time();
                    $change_desc = ($mlmOrder['user_id']?'付佣好友:'.dealUserAlias(user_info($mlmOrder['user_id'])['alias'])."\n":'').'取消订单:'.$order['order_sn'];

                    // 判断是否发生过退款，是则直接回滚
                    if($mlmOrder['rebate_back'] > 0 || $mlmOrder['pay_cash_back'] > 0){
                        $db->query("ROLLBACK"); //事务回滚
                        sys_msg('该订单已发生过退款，mlm_order_id:'.$mlmOrder['id']);
                    }

                    $sql = "UPDATE ".$GLOBALS['ecs']->table('mlm_order'). " SET order_status = 2, rebate_back = {$mlmOrder['rebate']}, pay_cash_back = {$mlm_after_service_cash_back}, updated_at = {$now} WHERE id = {$mlmOrder['id']}";
                    $res = $GLOBALS['db']->query($sql);

                    if(!$res){
                        $db->query("ROLLBACK"); //事务回滚
                        sys_msg('更新mlm订单状态失败');
                    }


                    if ($mlmOrder['custom_profit'] < 0) {
                        $actCustomAmount = $mlmOrder['act_rebate'];
                    } else {
                        $actCustomAmount = bcadd($mlmOrder['act_rebate'], $mlmOrder['custom_profit'], 2);
                    }

                    //代付状态判断  已代付
                    if($mlmOrder['pay_status'] == 1){
                        // 扣减换换客冻结现金
                        $res = changeMlmUserAccount($mlmOrder['user_id'], $mlmOrder['id'],  0, -$mlmOrder['rebate'], 2, 3, 1, $change_desc); // 221 还是 231

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }

                        // 增加换换客权益币
                        $res = changeUserAccount($mlmOrder['user_id'], $mlmOrder['do_surplus'], 0, 0, 0, $change_desc, 95);

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }

                        // 减去平台多得现金
                        $subPlatMoney = bcsub($mlmOrder['rebate'], bcsub($mlmOrder['pay_cash'], $realMoneyPaid, 2), 2);
                        $res = changeMlmUserAccount($platform_user_id, $mlmOrder['id'],  0, bcsub(bcsub($order['surplus'], $after_service_surplus_back, 2), $subPlatMoney, 2), 1, 2, 0, $change_desc); // 221 还是 231

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }

                        // 减去平台多得权益币
                        $subPlatSurplus = bcsub(bcsub($mlmOrder['do_surplus'], $mlmOrder['mlm_surplus'], 2), bcsub($order['surplus'], $after_service_surplus_back, 2), 2);
                        $res = changeUserAccount($platform_user_id, -$subPlatSurplus, 0, 0, 0, $change_desc, 95);

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }
                    }else{
                        // 扣减换换客活动佣金
                        $flatSubForzen = $mlmOrder['mlm_surplus'];
                        if($actCustomAmount > 0){
                            $res = changeMlmUserAccount($mlmOrder['user_id'], $mlmOrder['id'],  0, -$actCustomAmount, 2, 3, 1, $change_desc); // 221 还是 231

                            if($res['code']){
                                $db->query("ROLLBACK"); //事务回滚
                                sys_msg($res['info']);
                            }

                            $flatSubForzen = bcsub(bcsub($mlmOrder['pay_cash'], $realMoneyPaid, 2), $actCustomAmount, 2);
                        }

                        // 扣减平台冻结现金(按比例扣除)
                        $subPlatMoney = bcsub(bcsub($order['surplus'], $after_service_surplus_back, 2), $flatSubForzen, 2);
                        $subPlatMoney > 0 ? $unPayChangeType = 2 : $unPayChangeType = 1;
                        $res = changeMlmUserAccount($platform_user_id, $mlmOrder['id'], 0, $subPlatMoney, $unPayChangeType, 2, 1, $change_desc);

                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }

                        $log .= '扣减平台现金：'.$mlmOrder['mlm_surplus'];

                        // 增加平台权益币
                        $res = changeUserAccount($platform_user_id, $mlmOrder['mlm_surplus'], 0, 0, 0, $change_desc, 95);
                        if($res['code']){
                            $db->query("ROLLBACK"); //事务回滚
                            sys_msg($res['info']);
                        }
                    }

				}

				/** 已结算订单需要从商家账户扣回退款 **/

				if($order['suppliers_account_log_id']>0){
					$res = dealOrderToSuppliersSettlement($order['suppliers_id'],$order['order_id'],$after_service_surplus_back,$after_service_cash_back,'售后操作', $order['token_type']);
				    if(!$res){
						$db->query("ROLLBACK"); //事务回滚
						sys_msg('已结算订单扣除商家账户退款失败');
                    }
				    $log .= '扣减商家积分：'.$after_service_surplus_back.'扣减商家现金：'.$after_service_cash_back;
				}
				/**更新订单退款金额**/
				$sql_set = '';
				if($after_service_surplus_back > 0){
					$sql_set .= " surplus_back = surplus_back + {$after_service_surplus_back},";
				}
				if($after_service_cash_back > 0){
					$sql_set .= " cash_back = cash_back + {$after_service_cash_back},";
				}
				$sql_set = trim($sql_set, ',');
				$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
					" SET {$sql_set}" .
					" WHERE order_id = $order_id";
				$update_order_info_res = $GLOBALS['db']->query($sql);
				if(empty($update_order_info_res)){
					$db->query("ROLLBACK"); //事务回滚
					sys_msg('售后操作订单详情更新失败');
				}


			}


            $db->query("COMMIT"); //提交事务
            $log .= '}';
            $before_amount = bcadd($order['cash_back'],$order['surplus_back'],2);  //已退
            $sum_refund    = bcadd($before_amount,$after_service_refund_amount,2); //总
            $sum_pay       = bcadd($order['money_paid'],$order['surplus'],2);      //总支付
            if($sum_pay == $sum_refund){
                /* 记录log */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
                    " SET order_status = 2, shipping_status = 0, pay_status = 0" .
                    " WHERE order_id = $order_id";
                $GLOBALS['db']->query($sql);
                order_action($order['order_sn'], 0, 0, 0, $refund_note.$log);
            }else{
                /* 记录log */
                order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $refund_note.$log);
            }
        }
        else
        {
            /* 记录log */
            order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], '[' . $_LANG['op_after_service'] . '] ' . $action_note);
        }


    }
    else
    {
        die('invalid params');
    }

    /* 操作成功 */
    $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
    sys_msg($_LANG['act_ok'] . $msg, 0, $links);
}

elseif ($_REQUEST['act'] == 'async_operation')
{
    if($_REQUEST['error_code'] != 0){
        sys_msg($_REQUEST['msg']);
    }

    $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $_REQUEST['order_id']);
    sys_msg($_LANG['act_ok'] . $_REQUEST['msg'], 0, $links);
}

elseif ($_REQUEST['act'] == 'json')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $func = $_REQUEST['func'];
    if ($func == 'get_goods_info')
    {
        /* 取得商品信息 */
        $goods_id = $_REQUEST['goods_id'];
        $sql = "SELECT goods_id, c.cat_name, goods_sn, goods_name, b.brand_name, " .
            "goods_number, market_price, shop_price, promote_price, " .
            "promote_start_date, promote_end_date, goods_brief, goods_type, is_promote " .
            "FROM " . $ecs->table('goods') . " AS g " .
            "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "LEFT JOIN " . $ecs->table('category') . " AS c ON g.cat_id = c.cat_id " .
            " WHERE goods_id = '$goods_id'";
        $goods = $db->getRow($sql);
        $today = gmtime();
        $goods['goods_price'] = ($goods['is_promote'] == 1 &&
            $goods['promote_start_date'] <= $today && $goods['promote_end_date'] >= $today) ?
            $goods['promote_price'] : $goods['shop_price'];

        /* 取得会员价格 */
        $sql = "SELECT p.user_price, r.rank_name " .
            "FROM " . $ecs->table('member_price') . " AS p, " .
            $ecs->table('user_rank') . " AS r " .
            "WHERE p.user_rank = r.rank_id " .
            "AND p.goods_id = '$goods_id' ";
        $goods['user_price'] = $db->getAll($sql);

        /* 取得商品属性 */
        $sql = "SELECT a.attr_id, a.attr_name, g.goods_attr_id, g.attr_value, g.attr_price, a.attr_input_type, a.attr_type " .
            "FROM " . $ecs->table('goods_attr') . " AS g, " .
            $ecs->table('attribute') . " AS a " .
            "WHERE g.attr_id = a.attr_id " .
            "AND g.goods_id = '$goods_id' ";
        $goods['attr_list'] = array();
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $goods['attr_list'][$row['attr_id']][] = $row;
        }
        $goods['attr_list'] = array_values($goods['attr_list']);

        echo $json->encode($goods);
    }
}

/*------------------------------------------------------ */
//-- 合并订单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'ajax_merge_order')
{
    /* 检查权限 */
    admin_priv('order_os_edit');

    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $from_order_sn = empty($_POST['from_order_sn']) ? '' : json_str_iconv(substr($_POST['from_order_sn'], 1));
    $to_order_sn = empty($_POST['to_order_sn']) ? '' : json_str_iconv(substr($_POST['to_order_sn'], 1));

    $m_result = merge_order($from_order_sn, $to_order_sn);
    $result = array('error'=>0,  'content'=>'');
    if ($m_result === true)
    {
        $result['message'] = $GLOBALS['_LANG']['act_ok'];
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $m_result;
    }
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除订单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove_order')
{
    /* 检查权限 */
    admin_priv('order_edit');

    $order_id = intval($_REQUEST['id']);

    /* 检查权限 */
    check_authz_json('order_edit');

    /* 检查订单是否允许删除操作 */
    $order = order_info($order_id);
    $operable_list = operable_list($order);
    if (!isset($operable_list['remove']))
    {
        make_json_error('Hacking attempt');
        exit;
    }

    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('order_info'). " WHERE order_id = '$order_id'");
    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('order_goods'). " WHERE order_id = '$order_id'");
    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('order_action'). " WHERE order_id = '$order_id'");
    $action_array = array('delivery', 'back');
    del_delivery($order_id, $action_array);

    if ($GLOBALS['db'] ->errno() == 0)
    {
        $url = 'order.php?act=query&' . str_replace('act=remove_order', '', $_SERVER['QUERY_STRING']);

        ecs_header("Location: $url\n");
        exit;
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}

/*------------------------------------------------------ */
//-- 根据关键字和id搜索用户
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_users')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $id_name = empty($_GET['id_name']) ? '' : json_str_iconv(trim($_GET['id_name']));

    $result = array('error'=>0, 'message'=>'', 'content'=>'');
    if ($id_name != '')
    {
        $sql = "SELECT user_id, user_name FROM " . $GLOBALS['ecs']->table('users') .
            " WHERE user_id LIKE '%" . mysql_like_quote($id_name) . "%'" .
            " OR user_name LIKE '%" . mysql_like_quote($id_name) . "%'" .
            " LIMIT 20";
        $res = $GLOBALS['db']->query($sql);

        $result['userlist'] = array();
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $result['userlist'][] = array('user_id' => $row['user_id'], 'user_name' => $row['user_name']);
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO KEYWORDS!';
    }

    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 根据关键字搜索商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $keyword = empty($_GET['keyword']) ? '' : json_str_iconv(trim($_GET['keyword']));

    $result = array('error'=>0, 'message'=>'', 'content'=>'');

    $where = '';
    if(!empty($_SESSION['suppliers_id'])){
        $where .= " and suppliers_id in ({$_SESSION['suppliers_id']})";
    }
    if ($keyword != '')
    {
        $sql = "SELECT goods_id, goods_name, goods_sn FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE is_delete = 0" .
            " AND is_on_sale = 1" .
            " AND is_alone_sale = 1" .
            " AND (goods_id LIKE '%" . mysql_like_quote($keyword) . "%'" .
            " OR goods_name LIKE '%" . mysql_like_quote($keyword) . "%'" .
            " OR goods_sn LIKE '%" . mysql_like_quote($keyword) . "%')" .
            $where .
            " LIMIT 20";
        $res = $GLOBALS['db']->query($sql);

        $result['goodslist'] = array();
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $result['goodslist'][] = array('goods_id' => $row['goods_id'], 'name' => $row['goods_id'] . '  ' . $row['goods_name'] . '  ' . $row['goods_sn']);
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO KEYWORDS';
    }
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 编辑收货单号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_invoice_no')
{
    /* 检查权限 */
    check_authz_json('order_edit');

    $no = empty($_POST['val']) ? 'N/A' : json_str_iconv(trim($_POST['val']));
    $no = $no=='N/A' ? '' : $no;
    $order_id = empty($_POST['id']) ? 0 : intval($_POST['id']);

    if ($order_id == 0)
    {
        make_json_error('NO ORDER ID');
        exit;
    }

    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . " SET invoice_no='$no' WHERE order_id = '$order_id'";
    if ($GLOBALS['db']->query($sql))
    {
        if (empty($no))
        {
            make_json_result('N/A');
        }
        else
        {
            make_json_result(stripcslashes($no));
        }
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}

/*------------------------------------------------------ */
//-- 编辑付款备注
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_pay_note')
{
    /* 检查权限 */
    check_authz_json('order_edit');

    $no = empty($_POST['val']) ? 'N/A' : json_str_iconv(trim($_POST['val']));
    $no = $no=='N/A' ? '' : $no;
    $order_id = empty($_POST['id']) ? 0 : intval($_POST['id']);

    if ($order_id == 0)
    {
        make_json_error('NO ORDER ID');
        exit;
    }

    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . " SET pay_note='$no' WHERE order_id = '$order_id'";
    if ($GLOBALS['db']->query($sql))
    {
        if (empty($no))
        {
            make_json_result('N/A');
        }
        else
        {
            make_json_result(stripcslashes($no));
        }
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}
/*------------------------------------------------------ */
//-- 矩阵接口失败，重试
/*------------------------------------------------------ */
elseif($_REQUEST['act']=='retry'){
    $_GET['id'] and order_retry($_GET['id']);
    make_json_result('true');
}

/*------------------------------------------------------ */
//-- 获取订单商品信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_goods_info')
{
    /* 取得订单商品 */
    $order_id = isset($_REQUEST['order_id'])?intval($_REQUEST['order_id']):0;
    if (empty($order_id))
    {
        make_json_response('', 1, $_LANG['error_get_goods_info']);
    }
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, g.goods_thumb, g.goods_number AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name " .
        "FROM " . $ecs->table('order_goods') . " AS o ".
        "LEFT JOIN " . $ecs->table('goods') . " AS g ON o.goods_id = g.goods_id " .
        "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
        "WHERE o.order_id = '{$order_id}' ";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        /* 虚拟商品支持 */
        if ($row['is_real'] == 0)
        {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
            if (file_exists($filename))
            {
                include_once($filename);
                if (!empty($_LANG[$row['extension_code'].'_link']))
                {
                    $row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']    = price_format($row['goods_price']);
        $_goods_thumb = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $_goods_thumb = (strpos($_goods_thumb, 'http://') === 0 || strpos($_goods_thumb, 'https://') === 0) ? $_goods_thumb : $ecs->url() . $_goods_thumb;
        $row['goods_thumb'] = $_goods_thumb;
        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
        $goods_list[] = $row;
    }
    $attr = array();
    $arr  = array();
    foreach ($goods_attr AS $index => $array_val)
    {
        foreach ($array_val AS $value)
        {
            $arr = explode(':', $value);//以 : 号将属性拆开
            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }

    $smarty->assign('goods_attr', $attr);
    $smarty->assign('goods_list', $goods_list);
    $str = $smarty->fetch('order_goods_info.htm');
    $goods[] = array('order_id' => $order_id, 'str' => $str);
    make_json_result($goods);
}else if($_REQUEST['act'] == 'install_cloud') {
    /**
     *  激活云起页面
     **/
    $smarty->display('install-icloud.htm');
}


elseif ($_REQUEST['act'] == 'cancelErpPanel')
{
    //更新订单总金额
    $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') .
        " SET value = 0
         WHERE code = 'showerpPanel' and value != 0 ";
    return $GLOBALS['db']->query($sql);
}
/**
 *  获取云起开通产品列表
 **/
elseif ($_REQUEST['act']=='getSnList') {
    if($_SESSION['yunqi_login'] && $_SESSION['TOKEN'] ){
        include_once(ROOT_PATH."includes/cls_certificate.php");
        $cert = new certificate();
        $result = $cert->getsnlistoauth($_SESSION['TOKEN'] ,array());
        $result['status']=='success' and $cert->save_snlist($result['data']);
    }
}

/*------------------------------------------------------ */
//-- 导入运单号列表
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'import_invoice_list')
{
    /* 检查权限 */
    admin_priv('import_invoice');

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['11_import_invoice']);
    $smarty->assign('action_link', array('href' => 'order.php?act=import_invoice_add', 'text' => '导入'));

    $smarty->assign('full_page', 1);
    $admin_type = $_SESSION['admin_type'];
    $suppliers_id = $_SESSION['suppliers_id'];
    $admin_name = $_SESSION['admin_name'];
    $result = import_invoice_list($admin_type,$suppliers_id,$admin_name);
    $smarty->assign('list',         $result['list']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('import_invoice_list.htm');
}

/*------------------------------------------------------ */
//-- 搜索、排序、分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'import_invoice_query')
{
    /* 检查权限 */
    admin_priv('import_invoice');

    $result = import_invoice_list($_SESSION['admin_type'],$_SESSION['suppliers_id'],$_SESSION['admin_name']);
    $smarty->assign('list',         $result['list']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    make_json_result($smarty->fetch('import_invoice_list.htm'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 导入运单号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'import_invoice_add')
{
    /* 检查权限 */
    admin_priv('import_invoice');

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['11_import_invoice']);
    $smarty->assign('action_link', array('href' => 'order.php?act=import_invoice_list', 'text' => '返回列表'));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('import_invoice_add.htm');
}

/*------------------------------------------------------ */
//-- 导入运单号模版下载
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'import_invoice_download')
{
    /* 检查权限 */
    admin_priv('import_invoice');

    $path = __DIR__ . '/includes/import_invoice.xls';
    if (!file_exists($path)){
        $obj=new PHPExcel();             //创建表
        $sheet=$obj->getActiveSheet(0);   //确定活动表
        $sheet->setCellValue('A1','订单号');
        $sheet->setCellValue('B1','运单号');
        $sheet->setCellValue('C1','快递公司');
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        $write = PHPExcel_IOFactory::createWriter($obj, 'Excel5');
        $write->save($path);
    }
    ob_start();
    ob_end_clean();
    readfile($path);
    header("Content-type: application/octet-stream;charset=utf-8");
    header('content-Disposition:attachement;filename=import_invoice.xls');

    exit;
}

elseif ($_REQUEST['act'] == 'express_download')
{
    /* 检查权限 */
    admin_priv('import_invoice');

    $obj=new PHPExcel();             //创建表
    $sheet=$obj->getActiveSheet(0);   //确定活动表
    $sheet->setCellValue('A1','创建时间');
    $sheet->setCellValue('B1','订单编号');
    $sheet->setCellValue('C1','收件人');
    $sheet->setCellValue('D1','电话');
    $sheet->setCellValue('E1','地址');
    $sheet->setCellValue('F1','发货信息');
    $sheet->setCellValue('G1','发件人');
    $sheet->setCellValue('H1','发件人电话');
    $sheet->setCellValue('I1','发件人地址');
    $sheet->setCellValue('J1','备注');
    $sheet->setCellValue('K1','代收金额');
    $sheet->setCellValue('L1','保价金额');
    $sheet->setCellValue('M1','业务类型');
    $sheet->setCellValue('N1','快递名称');
    $sheet->setCellValue('O1','快递单号');
    $sheet->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $sheet->getStyle('O')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    header("Content-type: application/octet-stream;charset=utf-8");
    header('content-Disposition:attachement;filename=import_invoice.xls');
    $write = PHPExcel_IOFactory::createWriter($obj, 'Excel5');
    $write->save('php://output');

    exit;
}

/*------------------------------------------------------ */
//-- 导入运单号上传
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'import_invoice_upload')
{
    /* 检查权限 */
    admin_priv('import_invoice');
    $admin_name = $_SESSION['admin_name'];
    $file_name = $_FILES['file']['name'];
    if (empty($file_name)) {
        sys_msg('请选择上传文件', 1);
    }
    @$file_type = end(explode('.', $file_name));
    if ($file_type != 'xls') {
        sys_msg('请上传xls格式文件', 1);
    }
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    $sql = "select count(*) from " . $ecs->table('import_invoice') . " where file_name = '{$file_name}'";
    $row = $db->getOne($sql);
    if (!empty($row)) {
        sys_msg('该文件名已经存在', 1);
    }
    $path = './upload/' . date('Ymd') . '/';
    if (!is_dir($path)) {
        @mkdir($path, 0777, true) || sys_msg('系统错误', 1);
    }
    $new_file_name = md5($file_name . time()) . '.xls';
    @move_uploaded_file($_FILES["file"]["tmp_name"], $path . $new_file_name);
    @chmod($path . $new_file_name, 0777);
    $excel = PHPExcel_IOFactory::load($path . $new_file_name);
    $sheet = $excel->getSheet();

    $rows = $sheet->getHighestRow();
    if ($rows == 1 || $rows > 1001) {
        sys_msg('导入失败：数据不能为空,xls文件最大上传1000条', 1);
    }

    $clos = $sheet->getHighestColumn();
    $str = '';
    for ($j = 'A'; $j <= $clos; $j++) {
        $str .= $sheet->getCell($j . '1')->getValue();
    }
    $type = $clos == 'C' ? 1 : 0;
    if ($type) {
        if ($clos != 'C' || $str != $_LANG['dafault_template']) {
            sys_msg('您上传的文件模板格式暂不支持，请下载支持的模板格式', 0);
            unlink($path . $new_file_name);
        }
    } else {
        if ($clos != 'O' || $str != $_LANG['express_template']) {
            sys_msg('您上传的文件模板格式暂不支持，请下载支持的模板格式', 0);
            unlink($path . $new_file_name);
        }
    }

//    // 展示商家地址信息接口
//    $suppliers_id = $_SESSION['suppliers_id'];
//    $getAddressUrl = EXPRESS_INFO_URL.'/eorder/customerNumber/address/'.$userId;
//    $address = curlData($getAddressUrl,'','GET');
//    $add = json_decode($address,true);
//    // 获取供应商信息
//    $sqlSuppliers = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '$userId'";
//    $resSuppliers = $db->getRow($sqlSuppliers);

    for ($i = 2; $i <= $rows; $i++) {
        if ($type) {
            $order_sn = $sheet->getCell('A' . $i)->getValue();
            $invoice_no = $sheet->getCell('B' . $i)->getValue();
            $shipping = $sheet->getCell('C' . $i)->getValue();
        } else {
            $order_sn = $sheet->getCell('B' . $i)->getValue();
            $invoice_no = $sheet->getCell('O' . $i)->getValue();
            $shipping = $sheet->getCell('N' . $i)->getValue();
        }

        if (empty($order_sn) && empty($invoice_no) && empty($shipping)) {
            continue;
        }
        if(is_object($order_sn))  $order_sn= $order_sn->__toString();
        if(is_object($invoice_no))  $invoice_no= $invoice_no->__toString();
        $invoice_no = numToStr($invoice_no);

        $pre = "/^[0-9a-zA-Z\;\,]+$/";
        if (!preg_match($pre, trim($invoice_no))) {
            sys_msg('第' . $i . '行快递单号为空或格式不正确（格式为数字或字母组成，多个快递单号用英文逗号或英文分号隔开）', 0);
            unlink($path . $new_file_name);
        }
        if (empty($shipping)) {
            sys_msg('第' . $i . '行快递名称不能为空', 0);
            unlink($path . $new_file_name);
        }
        // 订阅订单  wanghai 2019-07-04
//        $url = EXPRESS_INFO_URL.'/logistics/trajectory/subscribe';
//        $sql = "SELECT * FROM " . $ecs->table('order_info') . " WHERE order_sn = '$order_sn'";
//        $res = $db->getRow($sql);
//        $sqlGoods = "SELECT * FROM " . $ecs->table('order_goods') . " WHERE order_id = '$order_sn'";
//        $resGoods = $db->getRow($sqlGoods);
//        $sqlShipping = "SELECT * FROM " . $ecs->table('shipping') . " WHERE shipping_id = '$shipping'";
//        $resShipping = $db->getRow($sqlShipping);
//        $commodity = [
//            'goodsName'=> $resGoods['goods_name'],
//            'goodsCode'=>'',
//            'goodsquantity'=>'',
//            'goodsPrice'=>'',
//            'goodsWeight'=>'',
//            'goodsDesc'=>'',
//            'goodsVol'=>''
//        ];
//        $data = [
//            'suppliersId' => $suppliers_id,
//            'shipperCode' => $resShipping['shipping_code'],
//            'logisticCode' => $invoice_no,
//            'orderCode' => $value,
//            'cost' => $res['shipping_fee'],
//            'receiverName' => $res['consignee'],
//            'receiverTel' => $res['tel'],
//            'receiverMobile' => $res['mobile'] ? $res['mobile'] : '',
//            'receiverPostCode' => '',
//            'receiverProvinceName' => $res['province'],
//            'receiverCityName' => $res['city'],
//            'receiverExpAreaName' => $res['district'],
//            'receiverAddress' => $res['address'],
//            'senderCompany' => '',
//            'senderName' => $resSuppliers['shop_name'],
//            'senderTel' => $resSuppliers['service_tel'],
//            'senderMobile' => $resSuppliers['receiver_tel'] ? $resSuppliers['receiver_tel'] : '',
//            'senderPostCode' => '',
//            'senderProvinceName' => $add['data']['province'],
//            'senderCityName' => $add['data']['city'],
//            'senderExpAreaName' => $add['data']['area'],
//            'senderAddress' => $add['data']['address'],
//            'weight' => '',
//            'quantity' => 1,
//            'volume' => '',
//            'remark' => $res['postscript'],
//            'commodity' => $commodity
//        ];
//        // 订阅订单接口请求
//        $res = curlData($url,json_encode($data),'POST');
//        admin_log($res, 'import_invoice_upload', 'logistics');
    }
    $total_num = $rows - 1;
    $sql = "INSERT INTO " . $ecs->table('import_invoice') . " (file_name, file_path, total_num, addtime,admin_name) " .
        "VALUES ('" . $file_name . "', '" . ($path . $new_file_name) . "', {$total_num}, " . time() . ",'" . $admin_name . "')";
    $row = $db->query($sql);
    if (!$row) {
        sys_msg('数据更新失败', 1);
        unlink($path . $new_file_name);
    }
    admin_log($file_name, 'import_invoice');
    /*添加链接*/
    $link[0]['text'] = '导入运单号';
    $link[0]['href'] = 'order.php?act=import_invoice_list';

    sys_msg('导入成功', 0, $link);
}

/*------------------------------------------------------ */
//--  根据订单退债权
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'debt_rollback_list')
{
    /* 检查权限 */
    admin_priv('debt_rollback');

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['13_debt_rollback']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_debt_rollback.htm');
}

/*------------------------------------------------------ */
//--  根据订单退债权，请求itz
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'user_debt_rollback')
{
    /* 检查权限 */
    admin_priv('debt_rollback');

    /* 是否存在订单编号*/
    if (empty($_POST['order_sn'])) {
        $link[] = array('href' => 'order.php?act=debt_rollback_list', 'text' => $_LANG['order_debt_rollback']);
        sys_msg($_LANG['cannot_found_order'], 1, $link);
    } else {
        $order_sn = htmlspecialchars(trim($_POST['order_sn']));
    }

    $admin_type = $_SESSION['admin_type'] ? $_SESSION['admin_type'] : 0;
    if (isset($_SESSION['suppliers_id'])) {
        $suppliers_id = !empty($_SESSION['suppliers_id']) ? $_SESSION['suppliers_id'] : 0;
    } else {
        $sess->destroy_session();
        sys_msg('登录异常，请重新登录');
    }

    $sql = "SELECT order_id FROM " . $ecs->table('order_info') . " WHERE order_sn = '" . $order_sn."'";
    $oid= $db->getOne($sql);
    if (empty($oid)) {
        sys_msg('订单不存在');
    }

    $order = order_info($oid,'', $suppliers_id);
    $sql = "SELECT order_id,user_id,account,is_rollback,detail FROM " . $ecs->table('debt_order') . " WHERE order_oid = '" . $order['order_id'] . "' AND status = 2";
    $debt_data = $db->getAll($sql);
    if(empty($debt_data)){
        sys_msg("此订单没有绑定的积分兑换记录");
    }
    $uid_data = array();
    $post_data = array();
    $post_data['account'] = 0;
    $detail = array();
    foreach ($debt_data as $key => $val) {
        if ($val['is_rollback']) {
            sys_msg("换换提示：抱歉，此订单已有退回记录，不能重复退回债权");
        }
        if ($key > 0) {
            if (!in_array($val['user_id'], $uid_data)) {
                sys_msg("订单兑换记录存在异常");
            }
        }
        $uid = $uid_data[0] = $val['user_id'];
        $post_data['order_id'][] = $val['order_id'];
        $post_data['account'] = bcadd($post_data['account'], $val['account'], 2);
        $detail[] = $val['detail'];
    }

    if (($order['order_status'] != OS_CANCELED && $order['order_status'] != OS_RETURNED) || $order['shipping_status'] == OS_SHIPPED_PART || $order['pay_status'] != PS_UNPAYED) {
        sys_msg('订单状态异常');
    }

//    if($order['money_paid'] > 0){
//        $sql = "SELECT id FROM " . $ecs->table('cash_payment_log') . " WHERE order_sn = '$order_sn'" . " AND status = 5";
//        $id = $db->getOne($sql);
//        if(!$id){
//            sys_msg('订单现金退款未成功');
//        }
//    }

    if ($order['surplus'] != $order['surplus_back']) {
        sys_msg('换换提示：订单积分支付数量与退还数量不一致');
    }

    //债权兑换活动用户订单不能退还债权。
    $str_order_id = implode(',',$post_data['order_id']);
    $sql = "SELECT id FROM " . $ecs->table('special_debt_users') . " WHERE user_id = $uid and order_id in ($str_order_id) and status = 3";
    $special_debt_user_data = $db->getRow($sql);
    if($special_debt_user_data){
        sys_msg('换换提示：该笔订单因用户参与债权膨胀活动产生，不能退回原债权');
    }

    $sql = "SELECT * FROM " . $ecs->table('users') . " WHERE user_id = '$uid'";
    $user_data = $db->getRow($sql);
    $post_data['uid'] = $user_data['hashid'];
    if ($user_data['user_money'] < $post_data['account']) {
        sys_msg('换换提示：用户积分余额不足');
    }
    $post_data['order_id'] = trim(implode(',', $post_data['order_id']));
    $post_data['serviceName'] = 'USER_DEBT_ROLLBACK';
    $post_data['itzAppKey']   = WX_API_KEY;
    ksort($post_data);
    $str = implode('', $post_data) . WX_API_SECRET;
    $post_data['signature'] = md5($str);

    $db->query('set sql_mode="STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"'); //开启MySQL严格模式
    $db->query('START TRANSACTION');//开启事务


    log_account_change($uid, -1 * $post_data['account'], 0, 0, 0, sprintf($_LANG['return_debt_rollback'], $post_data['account'], $order_sn), ACT_DEBT_ROLLBACK);
    $response = curl_request('https://www.itouzi.com/openApi/shop/Service', 'POST', $post_data);
    if(!$response || $response['code'] != 0){
        $db->query("rollback");
        sys_msg(!empty($response['info']) ?$response['info']: '请求失败');
    }
    if ($response['code'] == 0) {
        include_once(ROOT_PATH . 'includes/Smtp.class.php');
        $sql = "UPDATE" . $ecs->table('debt_order') . "  set is_rollback = 1 where order_id " . db_create_in($post_data['order_id']);
        $res = $db->query($sql);
        if (!$res) {
            MessageService::warningEmail('债权回退报警', '订单id：' . $post_data['order_id'] . '债权回退后is_rollback修改失败', ['lirongze@huanhuanyiwu.com', 'wangyanan@huanhuanyiwu.com']);
        }
        $str_log = '(';
        foreach ($detail as $v) {
            $arr = json_decode($v, true);
            foreach ($arr['detail'] as $val) {
                $str_log .= $val['name'] . ':' . $val['account'] . '元，';
            }
        }
        $str_log = trim($str_log) . ")";
        $order_action_res = order_action($order_sn, $order['order_status'], $order['shipping_status'], $order['pay_status'], sprintf($_LANG['debt_rollback_note'], $order_sn, $post_data['account'], $str_log));
        if (!$order_action_res) {
            MessageService::warningEmail('债权回退报警', '订单日志记录失败:' . sprintf($_LANG['debt_rollback_note'], $order_sn, $post_data['account'], $str_log), ['lirongze@huanhuanyiwu.com', 'wangyanan@huanhuanyiwu.com']);
        }
    }
    $db->query("commit");
    $link[] = array('href' => 'order.php?act=list', 'text' => $_LANG['back_list']);
    sys_msg('您发起的债权退回申请成功，预计10分钟内完成退回操作，请耐心等待',1,$link);

}
/*------------------------------------------------------ */
//--  财务
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'order_finace')
{

    /* 检查权限 */
    admin_priv('order_finace');

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['14_order_finace']);

    /* 载入配送方式 */
    $smarty->assign('shipping_list', shipping_list());

    /* 载入支付方式 */
    $smarty->assign('pay_list', payment_list());

    /* 载入国家 */
    $smarty->assign('country_list', get_regions());

    /* 商家类型列表 */
    $smarty->assign('suppliersTypeList', shopTypeList());

    /* 载入订单状态、付款状态、发货状态 */
    $smarty->assign('os_list', get_status_list('order'));
    $smarty->assign('ps_list', get_status_list('payment'));
    $smarty->assign('ss_list', get_status_list('shipping'));

    /* 商品分类 */
    $sql = "select cat_id, cat_name from ".$GLOBALS['ecs']->table('category')." where parent_id = 0 and is_show = 1";
    $category_list = $GLOBALS['db']->getAll($sql);
    $smarty->assign('category_list', $category_list);


    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_finace.htm');

}


elseif ($_REQUEST['act'] == 'show_suppliers'){
    /* 检查权限 */
    admin_priv('order_finace');
    $type = isset($_GET['suppliers_type']) ? intval($_GET['suppliers_type']) : 0;
    $suppliers = '';
    if($type){
        /* 载入供货商 */
        $suppliers = suppliers_list_info( "type in ({$type})");
    }
    make_json_result($smarty->fetch('order_finace.htm'), '', array('suppliers' => $suppliers));
}

elseif ($_REQUEST['act'] == 'insert_export'){
    /* 检查权限 */
    admin_priv('order_finace');
    $filter = array();
    $filter['suppliers_type']  = empty($_REQUEST['suppliers_type']) ? 0 : intval($_REQUEST['suppliers_type']);
    $filter['suppliers_id']    = isset($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : 0;
    $filter['country']         = empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']);
    $filter['province']        = empty($_REQUEST['province']) ? 0 : intval($_REQUEST['province']);
    $filter['city']            = empty($_REQUEST['city']) ? 0 : intval($_REQUEST['city']);
    $filter['district']        = empty($_REQUEST['district']) ? 0 : intval($_REQUEST['district']);
//    $filter['shipping_id']     = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
    $filter['pay_name']        = empty($_REQUEST['pay_name']) ? 0 : intval($_REQUEST['pay_name']);
    $filter['order_status']    = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
    $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
    $filter['pay_status']      = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
    $filter['cat_id_1']        = isset($_REQUEST['cat_id_1']) ? intval($_REQUEST['cat_id_1']) : 0;
    $filter['cat_id_2']        = isset($_REQUEST['cat_id_2']) ? intval($_REQUEST['cat_id_2']) : 0;
    $filter['cat_id']          = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
    $filter['start_time']      = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
    $filter['end_time']        = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);
    $export_name               = empty($_REQUEST['export_name']) ? '' : htmlspecialchars(trim($_REQUEST['export_name']));
    $email                     = empty($_REQUEST['email']) ? '' : htmlspecialchars(trim($_REQUEST['email']));
    $time = gmtime();
    $where = 'WHERE 1';
    if($filter['suppliers_type']){
        $where .= " AND s.type = '$filter[suppliers_type]'";
    }
    if($filter['suppliers_id'] != -1){
        $where .= " AND o.suppliers_id = '$filter[suppliers_id]'";
    }
    if ($filter['country']) {
        $where .= " AND o.country = '$filter[country]'";
    }
    if ($filter['province']) {
        $where .= " AND o.province = '$filter[province]'";
    }
    if ($filter['city']) {
        $where .= " AND o.city = '$filter[city]'";
    }
    if ($filter['district']) {
        $where .= " AND o.district = '$filter[district]'";
    }
//    if ($filter['shipping_id'] != -1) {
//        $where .= " AND o.shipping_id = '$filter[shipping_id]'";
//    }
    if ($filter['pay_name'] != -1) {
        switch ($filter['pay_name'])
        {
            case 1:
                $where .= " AND o.pay_name = '余额支付'";
                break;
            case 2:
                $where .= " AND o.pay_name = '微信支付'";
                break;
            default:
                $where .= " AND o.pay_name = '支付宝'";
        }
    }
    if ($filter['order_status'] != -1) {
        $where .= " AND o.order_status  = '$filter[order_status]'";
    }
    if ($filter['shipping_status'] != -1) {
        $where .= " AND o.shipping_status = '$filter[shipping_status]'";
    }
    if ($filter['pay_status'] != -1) {
        $where .= " AND o.pay_status = '$filter[pay_status]'";
    }
    $cat_id = 0;
    if ($filter['cat_id_1']) {
        $cat_id = $filter['cat_id_1'];
    }
    if ($filter['cat_id_2']) {
        $cat_id = $filter['cat_id_2'];
    }
    if ($filter['cat_id']) {
        $cat_id = $filter['cat_id'];
    }
    if ($cat_id) {
        $str = $cat_id . ',';
        $str .= get_ids($cat_id);
        $str = trim($str,',');
        $where .= " AND f.cat_id in (".$str.")";
    }
    if ($filter['start_time']) {
        $where .= " AND o.add_time >= '$filter[start_time]'";
    }
    if ($filter['end_time']) {
        $where .= " AND o.add_time < '$filter[end_time]'";
    }
    $sql = "SELECT
            o.order_sn,
            o.suppliers_id,
            o.shipping_time,
            o.invoice_no,
            o.shipping_name,
            o.user_id,
            o.order_id,
            o.payment_log_id,
            o.shipping_status,
            o.order_status,
            o.pay_status,
            o.pay_name,
            o.goods_amount,
            o.money_paid,
            o.surplus,
            o.consignee,
            o.address,
            o.zipcode,
            o.mobile,
            FROM_UNIXTIME(o.add_time) AS add_time,
            b.goods_sn,
            b.goods_name,
            b.goods_attr,
            b.goods_number,
            f.shop_price,
            f.cat_id,
            f.goods_location,
            f.provider_name,
            IFNULL(c.region_name, '') AS province,
            IFNULL(d.region_name, '') AS city,
            IFNULL(e.region_name, '') AS district,
            s.suppliers_name,
            s.type,
            u.user_name,
            u.mobile_phone
        FROM
            " . $GLOBALS['ecs']->table('order_info') . " AS o
        LEFT JOIN " . $GLOBALS['ecs']->table('users') . " AS u ON u.user_id = o.user_id
        LEFT JOIN " . $GLOBALS['ecs']->table('order_goods') . " AS b ON o.order_id = b.order_id
        LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS f ON f.goods_id = b.goods_id
        LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS h ON o.country = h.region_id
        LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS c ON o.province = c.region_id
        LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS d ON o.city = d.region_id
        LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS e ON o.district = e.region_id
        LEFT JOIN " . $GLOBALS['ecs']->table('suppliers') . " AS s ON o.suppliers_id = s.suppliers_id
         " .$where;
    $sql = str_replace("'", "\"", $sql);
    $now = time();
    $insert_sql = "INSERT INTO " . $ecs->table('export_order') . " (export_id, export_name, content, email, status, admin_id, export_type, created_at, updated_at) " .
        "VALUES (null,'$export_name','" . base64_encode($sql) . "','$email',0,$_SESSION[admin_id],1,'$now','$now')";
    $res = $db->query($insert_sql);
    if ($res) {
        $links[] = array('text' => '财务导出', 'href' => 'order.php?act=order_finace');
        sys_msg("执行成功，系统会在5-10分钟內将数据发送至您的邮件", 1, $links);
    } else {
        sys_msg("执行失败");
    }
}

/**
 * 取得状态列表
 * @param   string  $type   类型：all | order | shipping | payment
 */
function get_status_list($type = 'all')
{
    global $_LANG;

    $list = array();

    if ($type == 'all' || $type == 'order')
    {
        $pre = $type == 'all' ? 'os_' : '';
        foreach ($_LANG['os'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }

    if ($type == 'all' || $type == 'shipping')
    {
        $pre = $type == 'all' ? 'ss_' : '';
        foreach ($_LANG['ss'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }

    if ($type == 'all' || $type == 'payment')
    {
        $pre = $type == 'all' ? 'ps_' : '';
        foreach ($_LANG['ps'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }
    return $list;
}


/**
 * 首单红包返现退回
 * @Author   haofuheng
 * @DateTime 2019-08-16T20:34:54+0800
 * @param    [type]                   $user_id  [description]
 * @param    [type]                   $order_id [description]
 * @return   [type]                             [description]
 */
function red_cash_send_go_back($user_id, $order_id)
{

    // 1.查发送的冻结红包
    $cash_user_sql  = 'select * from '.$GLOBALS['ecs']->table("cash_order_users");
    $cash_user_sql  .=' where order_id = '.$order_id.' and user_id = '.$user_id;
    $cash_user_sql  .=' and cash_flag = 0';
    $cash_user_sql  .=' and delete_flag = 0';
    $cash_user_msg   = $GLOBALS['db']->getRow($cash_user_sql);
    //没有发过，直接结束
    if (empty($cash_user_msg)) {
        return true;
    }

    // 2.红包记录变取消
    $cash_user_sql  = "update ".$GLOBALS['ecs']->table("cash_order_users");
    $cash_user_sql  .= " set cash_flag = 2";
    $cash_user_sql  .= " where id = ".$cash_user_msg["id"];
    $GLOBALS['db']->query($cash_user_sql);

    // 3.红包的已发送退回
    $cash_sql       = "update ".$GLOBALS['ecs']->table("cash_activity");
    $cash_sql       .= " set send_amount = send_amount - ".$cash_user_msg['return_cash'];
    $cash_sql       .= " where id = ".$cash_user_msg["cash_id"];
    $GLOBALS['db']->query($cash_sql);

    // 4. 账号信息
    $mlm_users_sql  = 'select * from '.$GLOBALS['ecs']->table("mlm_users");
    $mlm_users_sql  .=' where user_id = '.$user_id;
    $mlm_users_msg   = $GLOBALS['db']->getRow($mlm_users_sql);
    if (empty($mlm_users_msg)) {
        return true;
    }

    // 5.帐户frozen减少
    $mlm_users_sql  = "update ".$GLOBALS['ecs']->table("mlm_users");
    $mlm_users_sql  .= " set frozen_money = frozen_money - ".$cash_user_msg['return_cash'];
    $mlm_users_sql  .= " where user_id = ".$cash_user_msg["user_id"];
    $GLOBALS['db']->query($mlm_users_sql);

    // 6.帐户变动日志
    $users_a_log_sql = "INSERT INTO " . $GLOBALS['ecs']->table('mlm_account_log') .
    $users_a_log_sql .= " (order_info_id, cash_id, user_id, amount";
    $users_a_log_sql .= ",user_money, frozen_money, withdraw";
    $users_a_log_sql .= ",change_type, logic_type, is_frozen, change_desc, created_at)";
    $users_a_log_sql .= " VALUES (";
    $users_a_log_sql .= $cash_user_msg["order_id"].",";
    $users_a_log_sql .= "0,";
    $users_a_log_sql .= $cash_user_msg["user_id"].",";
    $users_a_log_sql .= $cash_user_msg["return_cash"].",";

    $users_a_log_sql .= $mlm_users_msg["user_money"].",";
    $users_a_log_sql .= $mlm_users_msg["frozen_money"].",";
    $users_a_log_sql .= $mlm_users_msg["withdraw"].",";

    $users_a_log_sql .= "2,";
    $users_a_log_sql .= "6,";
    $users_a_log_sql .= "1,";
    $users_a_log_sql .= "'首单红包返现退回',";
    $users_a_log_sql .= time();

    $users_a_log_sql .= ")";
    $GLOBALS['db']->query($users_a_log_sql);

    return true;

}


/**
 * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包设为0
 * @param   array   $order  订单信息
 */
function return_user_surplus_integral_bonus($order, $instalment_order = null)
{
    // 首单红包返现退回
    red_cash_send_go_back($order['user_id'], $order['order_id']);

    /* 处理余额、积分、红包 */
    if ($order['user_id'] > 0 && $order['surplus'] > 0)
    {
        $surplus = $order['money_paid'] < 0 ? $order['surplus'] + $order['money_paid'] : $order['surplus'];
        //是否代付
        if($order['order_type'] == 2){
            $sql = "select user_id from " . $GLOBALS['ecs']->table('cash_payment_log') ." where order_id = '{$order['order_id']}' and pay_type = 9 and is_share = 1 limit 1";
            if($cash_payment_log = $GLOBALS['db']->getRow($sql)){
                $user_id = $cash_payment_log['user_id'];
            }else{
                return false;
            }
        }

        // 根据订单类型修改账户表
        if($order['token_type'] == 2){
            if(!user_token_extend_log_change(
                $order['user_id'],
                $order['order_id'],
                $surplus,
                sprintf($GLOBALS['_LANG']['return_order_surplus'], $order['order_sn']),
                TOKNE_REFUND
            )){
                return false;
            }
        }else{
            log_account_change(
                $user_id ?: $order['user_id'],
                $surplus,
                0,
                0,
                0,
                sprintf($GLOBALS['_LANG']['return_order_surplus'], $instalment_order ? $instalment_order['instalment_sn'] : $order['order_sn']),
                ACT_REFUND_SURPLUS
            );
        }
        //$GLOBALS['db']->query("UPDATE ". $GLOBALS['ecs']->table('order_info') . " SET `order_amount` = '0' WHERE `order_id` =". $order['order_id']);
    }

    if ($order['user_id'] > 0 && $order['integral'] > 0)
    {
        log_account_change($order['user_id'], 0, 0, 0, $order['integral'], sprintf($GLOBALS['_LANG']['return_order_integral'], $order['order_sn']));
    }

    if ($order['bonus_id'] > 0)
    {
        unuse_bonus($order['bonus_id']);
    }

    /* 修改订单 */
    $arr = array(
        'bonus_id'  => 0,
        'bonus'     => 0,
        'integral'  => 0,
        'integral_money'    => 0,
        //'surplus'   => 0
    );
    return update_order($order['order_id'], $arr);
}

/**
 * 更新订单总金额
 * @param   int     $order_id   订单id
 * @return  bool
 */
function update_order_amount($order_id)
{
    include_once(ROOT_PATH . 'includes/lib_order.php');
    //更新订单总金额
    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
        " SET order_amount = " . order_due_field() .
        " WHERE order_id = '$order_id' LIMIT 1";

    return $GLOBALS['db']->query($sql);
}

/**
 * 返回某个订单可执行的操作列表，包括权限判断
 * @param   array   $order      订单信息 order_status, shipping_status, pay_status
 * @param   bool    $is_cod     支付方式是否货到付款
 * @return  array   可执行的操作  confirm, pay, unpay, prepare, ship, unship, receive, cancel, invalid, return, drop
 * 格式 array('confirm' => true, 'pay' => true)
 */
function operable_list($order)
{
    /* 取得订单状态、发货状态、付款状态 */
    $os = $order['order_status'];
    $ss = $order['shipping_status'];
    $ps = $order['pay_status'];
    /* 取得订单操作权限 */
    $actions = $_SESSION['action_list'];
    if ($actions == 'all')
    {
        $priv_list  = array('os' => true, 'ss' => true, 'ps' => true, 'edit' => true);
    }
    else
    {
        $actions    = ',' . $actions . ',';
        $priv_list  = array(
            'os'    => strpos($actions, ',order_os_edit,') !== false,
            'ss'    => strpos($actions, ',order_ss_edit,') !== false,
            'ps'    => strpos($actions, ',order_ps_edit,') !== false,
            'edit'  => strpos($actions, ',order_edit,') !== false
        );
    }

    /* 取得订单支付方式是否货到付款 */
    $payment = payment_info($order['pay_id']);
    $is_cod  = $payment['is_cod'] == 1;

    /* 根据状态返回可执行操作 */
    $list = array();
    if (OS_UNCONFIRMED == $os)
    {
        /* 状态：未确认 => 未付款、未发货 */
        if ($priv_list['os'])
        {
            $list['confirm']    = true; // 确认
            $list['invalid']    = true; // 无效
            $list['cancel']     = true; // 取消
            if ($is_cod)
            {
                /* 货到付款 */
                if ($priv_list['ss'])
                {
                    $list['prepare'] = true; // 配货
                    $list['split'] = true; // 分单
                }
            }
            else
            {
                /* 不是货到付款 */
                if ($priv_list['ps'])
                {
                    $list['pay'] = true;  // 付款
                }
            }
        }
    }
    elseif (OS_CONFIRMED == $os || OS_SPLITED == $os || OS_SPLITING_PART == $os)
    {
        /* 状态：已确认 */
        if (PS_UNPAYED == $ps)
        {
            /* 状态：已确认、未付款 */
            if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss)
            {
                /* 状态：已确认、未付款、未发货（或配货中） */
                if ($priv_list['os'])
                {
                    $list['cancel'] = true; // 取消
                    $list['invalid'] = true; // 无效
                }
                if ($is_cod)
                {
                    /* 货到付款 */
                    if ($priv_list['ss'])
                    {
                        if (SS_UNSHIPPED == $ss)
                        {
                            $list['prepare'] = true; // 配货
                        }
                        $list['split'] = true; // 分单
                    }
                }
                else
                {
                    /* 不是货到付款 */
                    if ($priv_list['ps'])
                    {
                        $list['pay'] = true; // 付款
                    }
                }
            }
            /* 状态：已确认、未付款、发货中 */
            elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss)
            {
                // 部分分单
                if (OS_SPLITING_PART == $os)
                {
                    $list['split'] = true; // 分单
                }
                $list['to_delivery'] = true; // 去发货
            }
            else
            {
                /* 状态：已确认、未付款、已发货或已收货 => 货到付款 */
                if ($priv_list['ps'])
                {
                    $list['pay'] = true; // 付款
                }
                if ($priv_list['ss'])
                {
                    if (SS_SHIPPED == $ss)
                    {
                        $list['receive'] = true; // 收货确认
                    }
                    $list['unship'] = true; // 设为未发货
                    if ($priv_list['os'])
                    {
                        $list['return'] = true; // 退货
                    }
                }
            }
        }
        else
        {
            /* 状态：已确认、已付款和付款中 */
            if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss)
            {
                /* 状态：已确认、已付款和付款中、未发货（配货中） => 不是货到付款 */
                if ($priv_list['ss'])
                {
                    if (SS_UNSHIPPED == $ss)
                    {
                        $list['prepare'] = true; // 配货
                    }
                    $list['split'] = true; // 分单
                }
                if ($priv_list['ps'])
                {
                    $list['unpay'] = true; // 设为未付款
                    if ($priv_list['os'])
                    {
                        $list['cancel'] = true; // 取消
                    }
                }
                if ($_SESSION['admin_type'] == 1 && PS_PAYED == $ps) {
                    $list['cancel'] = true; // 取消
                }
            }
            /* 状态：已确认、未付款、发货中 */
            elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss)
            {
                // 部分分单
                if (OS_SPLITING_PART == $os)
                {
                    $list['split'] = true; // 分单
                }
                $list['to_delivery'] = true; // 去发货
                $list['cancel'] = true; // 取消
            }
            else
            {
                /* 状态：已确认、已付款和付款中、已发货或已收货 */
                if ($priv_list['ss'])
                {
                    if (SS_SHIPPED == $ss)
                    {
                        $list['receive'] = true; // 收货确认
                    }
                    if (!$is_cod)
                    {
                        $list['unship'] = true; // 设为未发货
                    }
                }
                if ($priv_list['ps'] && $is_cod)
                {
                    $list['unpay']  = true; // 设为未付款
                }
                if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps'])
                {
                    $list['return'] = true; // 退货（包括退款）
                }
            }
        }
    }
    elseif (OS_CANCELED == $os)
    {
        /* 状态：取消 */
        if ($priv_list['os'])
        {
            $list['confirm'] = true;
        }
        if ($priv_list['edit'])
        {
            $list['remove'] = true;
        }
    }
    elseif (OS_INVALID == $os)
    {
        /* 状态：无效 */
        if ($priv_list['os'])
        {
            $list['confirm'] = true;
        }
        if ($priv_list['edit'])
        {
            $list['remove'] = true;
        }
    }
    elseif (OS_RETURNED == $os)
    {
        /* 状态：退货 */
        if ($priv_list['os'])
        {
            $list['confirm'] = true;
        }
    }

    /* 修正发货操作 */
    if (!empty($list['split']))
    {
        /* 如果是团购活动且未处理成功，不能发货 */
        if ($order['extension_code'] == 'group_buy')
        {
            include_once(ROOT_PATH . 'includes/lib_goods.php');
            $group_buy = group_buy_info(intval($order['extension_id']));
            if ($group_buy['status'] != GBS_SUCCEED)
            {
                unset($list['split']);
                unset($list['to_delivery']);
            }
        }

        /* 如果部分发货 不允许 取消 订单 */
        if (order_deliveryed($order['order_id']))
        {
            $list['return'] = true; // 退货（包括退款）
            unset($list['cancel']); // 取消
        }
    }

    if($_SESSION['admin_type']){
        $list['to_delivery'] = false; // 去发货
    }

    /* 售后 */

    if(($ss == SS_SHIPPED || $ss == SS_RECEIVED) && $ps == PS_PAYED){
        $list['after_service'] = true;
    }else{
        $list['after_service'] = false;
    }

    // 分期订单取消、退货、售后在收货之前且第一期完成后的24小时前
    if($order['order_type'] == OT_INSTALMENT && $order['money_paid'] > 0 && ($order['pay_status'] == PS_PAYING || $order['pay_status'] == PS_PAYED)){
        $list['after_service'] = true;
        $list['cancel'] = true;
    }
    if($order['order_type'] == OT_INSTALMENT && $order['shipping'] == 2){
        $list['after_service'] = false;
        $list['return'] = false;
        $list['cancel'] = false;
    }

    return $list;
}

/**
 * 处理编辑订单时订单金额变动
 * @param   array   $order  订单信息
 * @param   array   $msgs   提示信息
 * @param   array   $links  链接信息
 */
function handle_order_money_change($order, &$msgs, &$links)
{
    $order_id = $order['order_id'];
    if ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING)
    {
        /* 应付款金额 */
        $money_dues = $order['order_amount'];
        if ($money_dues > 0)
        {
            /* 修改订单为未付款 */
            update_order($order_id, array('pay_status' => PS_UNPAYED, 'pay_time' => 0));
            $msgs[]     = $GLOBALS['_LANG']['amount_increase'];
            $links[]    = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
        }
        elseif ($money_dues < 0)
        {
            $anonymous  = $order['user_id'] > 0 ? 0 : 1;
            $msgs[]     = $GLOBALS['_LANG']['amount_decrease'];
            $links[]    = array('text' => $GLOBALS['_LANG']['refund'], 'href' => 'order.php?act=process&func=load_refund&anonymous=' .
                $anonymous . '&order_id=' . $order_id . '&refund_amount=' . abs($money_dues));
        }
    }
}

/**
 *  获取订单列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function order_list()
{
    $result = get_filter();
    $where = ' WHERE o.is_temp = 0 ';
    $search_activity_order = false;
    if ($result === false)
    {
        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1)
        {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
            //$_REQUEST['address'] = json_str_iconv($_REQUEST['address']);
        }
      /*  print_r($_REQUEST['goods_name']);die;*/
        $filter['act_name'] = empty($_REQUEST['act_name']) ? '' : trim($_REQUEST['act_name']);
        $filter['act_type'] = empty($_REQUEST['act_type']) ? '' : trim($_REQUEST['act_type']);
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['goods_name'] = empty($_REQUEST['goods_name']) ? '' : trim($_REQUEST['goods_name']);
        $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
        $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
        $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : trim($_REQUEST['mobile']);
        $filter['country'] = empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']);
        $filter['province'] = empty($_REQUEST['province']) ? 0 : intval($_REQUEST['province']);
        $filter['city'] = empty($_REQUEST['city']) ? 0 : intval($_REQUEST['city']);
        $filter['district'] = empty($_REQUEST['district']) ? 0 : intval($_REQUEST['district']);
        $filter['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
        $filter['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
        $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
        $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
        $filter['group_buy_id'] = isset($_REQUEST['group_buy_id']) ? intval($_REQUEST['group_buy_id']) : 0;

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'o.add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);
        $filter['suppliers_id'] = isset($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : 0;
        $filter['goods_location'] = isset($_REQUEST['goods_location']) ? htmlspecialchars(trim($_POST['goods_location'])) : '';

        if(!empty($user_name = trim($_REQUEST['user_name'])) ){
            $user_id = -1;
            $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_name = '$user_name'";
            if($userInfo = $GLOBALS['db']->getRow($sql)){
                $user_id = $userInfo['user_id'];
            }
            $filter['user_id'] = $user_id;
        }

		if ( $filter['act_name'] || $filter['act_type'] )
		{
            $search_activity_order = true;
			$actInfo = getActivityInfoByAttribute($filter);
			if(!empty($actInfo)){
			    $act_ids = array_keys($actInfo);
				$where .= " AND oa.act_id in (". implode(',',$act_ids).') AND o.order_id = oa.order_id ';
			}else{
				$where .= ' AND oa.act_id = 0 AND o.order_id = oa.order_id ';
			}
		} else {
            $actInfo = getActivityInfoByAttribute($filter);
        }
        if ($filter['order_sn'])
        {
            $where .= " AND o.order_sn  = '" . mysql_like_quote($filter['order_sn'])."' " ;
        }
        if ($filter['goods_name'])
        {
            $where .= " AND b.goods_name LIKE '%" . mysql_like_quote($filter['goods_name']) . "%'";
        }
        if ($filter['consignee'])
        {
            $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['email'])
        {
            $where .= " AND o.email LIKE '%" . mysql_like_quote($filter['email']) . "%'";
        }
        if ($filter['address'])
        {
            $where .= " AND o.address LIKE '%" . mysql_like_quote($filter['address']) . "%'";
        }
        if ($filter['zipcode'])
        {
            $where .= " AND o.zipcode LIKE '%" . mysql_like_quote($filter['zipcode']) . "%'";
        }
        if ($filter['tel'])
        {
            $where .= " AND o.tel LIKE '%" . mysql_like_quote($filter['tel']) . "%'";
        }
        if ($filter['mobile'])
        {
            $where .= " AND o.mobile = '" .mysql_like_quote($filter['mobile']) . "'";
        }
        if ($filter['country'])
        {
            $where .= " AND o.country = '$filter[country]'";
        }
        if ($filter['province'])
        {
            $where .= " AND o.province = '$filter[province]'";
        }
        if ($filter['city'])
        {
            $where .= " AND o.city = '$filter[city]'";
        }
        if ($filter['district'])
        {
            $where .= " AND o.district = '$filter[district]'";
        }
        if ($filter['shipping_id'])
        {
            $where .= " AND o.shipping_id  = '$filter[shipping_id]'";
        }
        if ($filter['pay_id'])
        {
            $where .= " AND o.pay_id  = '$filter[pay_id]'";
        }
        if ($filter['order_status'] != -1)
        {
            $where .= " AND o.order_status  = '$filter[order_status]'";
        }
        if ($filter['shipping_status'] != -1)
        {
            $where .= " AND o.shipping_status = '$filter[shipping_status]'";
        }
        if ($filter['pay_status'] != -1)
        {
            $where .= " AND o.pay_status = '$filter[pay_status]'";
        }
        if ($filter['user_id'])
        {
            $where .= " AND o.user_id = '$filter[user_id]'";
        }

        if ($filter['start_time'])
        {
            $where .= " AND o.add_time >= '$filter[start_time]'";
        }
        if ($filter['end_time'])
        {
            $where .= " AND o.add_time < '$filter[end_time]'";
        }
        if ($filter['suppliers_id'])
        {
            $where .= " AND f.suppliers_id = '$filter[suppliers_id]'";
        }
        if ($filter['goods_location'])
        {
            $where .= " AND f.goods_location  LIKE '%" . mysql_like_quote($filter['goods_location']) . "%'";
        }
        //综合状态
        switch($filter['composite_status'])
        {
            case CS_AWAIT_PAY :
                $where .= order_query_sql('await_pay');
                break;

            case CS_AWAIT_SHIP :
                $where .= order_query_sql('await_ship');
                break;

            case CS_FINISHED :
                $where .= order_query_sql('finished');
                break;

            case PS_PAYING :
                if ($filter['composite_status'] != -1)
                {
                    $where .= " AND o.pay_status = '$filter[composite_status]' ";
                }
                break;
            case OS_SHIPPED_PART :
                if ($filter['composite_status'] != -1)
                {
                    $where .= " AND o.shipping_status  = '$filter[composite_status]'-2 ";
                }
                break;
            default:
                if ($filter['composite_status'] != -1)
                {
                    $where .= " AND o.order_status = '$filter[composite_status]' ";
                }
        }

        /* 团购订单 */
        if ($filter['group_buy_id'])
        {
            $where .= " AND o.extension_code = 'group_buy' AND o.extension_id = '$filter[group_buy_id]' ";
        }

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的订单 */
        $sql = "SELECT agency_id FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
        $agency_id = $GLOBALS['db']->getOne($sql);
        if ($agency_id > 0)
        {
            $where .= " AND o.agency_id = '$agency_id' ";
        }

        if(!empty($_SESSION['suppliers_id'])||$_SESSION['admin_type']){
            $where .= " AND o.suppliers_id in ({$_SESSION['suppliers_id']})";
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

        $filter['page_size'] = $filter['page_size']>50?50:$filter['page_size'];
        //有查询活动订单时再连order_activity表
        $filter['search_activity_order'] = false;
        $search_activity_order_sql = $group = $tmp_act_id_sql = '';
        if($search_activity_order){
            $search_activity_order_sql = " LEFT JOIN ".$GLOBALS['ecs']->table('order_activity'). " AS oa ON oa.order_id=o.order_id  ";
            $group = " GROUP BY o.order_id ";
            $filter['search_activity_order'] = true;
            $tmp_act_id_sql = 'oa.act_id,';
        }
        $sql = "SELECT COUNT(DISTINCT (o.order_id)) as order_num,sum(b.goods_number) as goods_num FROM ".$GLOBALS['ecs']->table('order_info') . " AS o  JOIN  ".
            $GLOBALS['ecs']->table('order_goods')  ." as b on  o.order_id = b.order_id LEFT JOIN  ".
            $GLOBALS['ecs']->table('goods')  ." as f on  f.goods_id = b.goods_id ".
            $search_activity_order_sql .
            $where ;
        $total = $GLOBALS['db']->getRow($sql);
        $filter['record_count']   = $total['order_num'];
        $filter['goods_num']      = $total['goods_num'];
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;


        /* 查询 */
        $sql = "SELECT ".$tmp_act_id_sql." o.discount, b.goods_name,b.goods_number,o.order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid, o.callback_status," .
            "o.pay_status, o.consignee, o.address, o.email, o.tel, o.extension_code, o.extension_id, o.shipping_fee, o.mlm_id," .
            "(" . order_amount_field('o.') . ") AS total_fee, (b.settlement_money * b.goods_number) as settlement_money_total " .
            " FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " .
            " LEFT JOIN " .$GLOBALS['ecs']->table('order_goods'). " AS b ON b.order_id=o.order_id ".
            " LEFT JOIN " .$GLOBALS['ecs']->table('goods'). " AS f ON f.goods_id=b.goods_id ".
            $search_activity_order_sql.
            $where . $group ." ORDER BY $filter[sort_by] $filter[sort_order] ".
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";
        foreach (array('order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel') AS $val)
        {
            $filter[$val] = stripslashes($filter[$val]);
        }

        $filter['where'] = urlencode($where);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
        $search_activity_order = $filter['search_activity_order'];
        $where  = urldecode($filter['where']);
    }
    $row = $GLOBALS['db']->getAll($sql);

    /* 格式话数据 */
    foreach ($row AS $key => $value)
    {

        if (empty($value['settlement_money_total']) || 0.00 == $value['settlement_money_total']) {
            $value['settlement_money_total']        = $value['total_fee'] - $value['shipping_fee'];
            $row[$key]["settlement_money_total"]    = $value['settlement_money_total'];
        }

        if($search_activity_order){
            if($value['act_id']>0  && isset($actInfo[$value['act_id']]) && $actInfo[$value['act_id']]['start_time'] < $value['add_time'] && ($actInfo[$value['act_id']]['end_time'] > $value['add_time']||$actInfo[$value['act_id']]['end_time']==0)){
                $row[$key]['act_type'] = getActivityType($actInfo[$value['act_id']]['type']);
                $row[$key]['act_name'] = $actInfo[$value['act_id']]['name'];
                $row[$key]['act_desc'] = $actInfo[$value['act_id']]['act_desc'];
            }
        }

        $row[$key]['formated_settlement_money_total_and_shipping']  = price_format($value['settlement_money_total']+$value['shipping_fee']);
        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
        $row[$key]['can_remove'] = 0;
    }
    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'where'=>$where,'goods_num'=>$filter['goods_num']);

    return $arr;
}

/**
 * 更新订单对应的 pay_log
 * 如果未支付，修改支付金额；否则，生成新的支付log
 * @param   int     $order_id   订单id
 */
function update_pay_log($order_id)
{
    $order_id = intval($order_id);
    if ($order_id > 0)
    {
        $sql = "SELECT order_amount FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE order_id = '$order_id'";
        $order_amount = $GLOBALS['db']->getOne($sql);
        if (!is_null($order_amount))
        {
            $sql = "SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') .
                " WHERE order_id = '$order_id'" .
                " AND order_type = '" . PAY_ORDER . "'" .
                " AND is_paid = 0";
            $log_id = intval($GLOBALS['db']->getOne($sql));
            if ($log_id > 0)
            {
                /* 未付款，更新支付金额 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('pay_log') .
                    " SET order_amount = '$order_amount' " .
                    "WHERE log_id = '$log_id' LIMIT 1";
            }
            else
            {
                /* 已付款，生成新的pay_log */
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('pay_log') .
                    " (order_id, order_amount, order_type, is_paid)" .
                    "VALUES('$order_id', '$order_amount', '" . PAY_ORDER . "', 0)";
            }
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 取得供货商列表
 * @return array    二维数组
 */
function get_suppliers_list()
{
    $sql = 'SELECT *
            FROM ' . $GLOBALS['ecs']->table('suppliers') . '
            WHERE is_check = 1
            ORDER BY suppliers_name ASC';
    $res = $GLOBALS['db']->getAll($sql);

    if (!is_array($res))
    {
        $res = array();
    }

    return $res;
}

/**
 * 取得订单商品
 * @param   array     $order  订单数组
 * @return array
 */
function get_order_goods($order)
{
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, g.suppliers_id AS suppliers_id,IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn " .
        "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o ".
        "LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON o.product_id = p.product_id " .
        "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON o.goods_id = g.goods_id " .
        "LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
        "WHERE o.order_id = '$order[order_id]' ";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        // 虚拟商品支持
        if ($row['is_real'] == 0)
        {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php';
            if (file_exists($filename))
            {
                include_once($filename);
                if (!empty($GLOBALS['_LANG'][$row['extension_code'].'_link']))
                {
                    $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']    = price_format($row['goods_price']);

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

        if ($row['extension_code'] == 'package_buy')
        {
            $row['storage'] = '';
            $row['brand_name'] = '';
            $row['package_goods_list'] = get_package_goods_list($row['goods_id']);
        }

        //处理货品id
        $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

        $goods_list[] = $row;
    }

    $attr = array();
    $arr  = array();
    foreach ($goods_attr AS $index => $array_val)
    {
        foreach ($array_val AS $value)
        {
            $arr = explode(':', $value);//以 : 号将属性拆开
            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }

    return array('goods_list' => $goods_list, 'attr' => $attr);
}

/**
 * 取得礼包列表
 * @param   integer     $package_id  订单商品表礼包类商品id
 * @return array
 */
function get_package_goods_list($package_id)
{
    $sql = "SELECT pg.goods_id, g.goods_name, (CASE WHEN pg.product_id > 0 THEN p.product_number ELSE g.goods_number END) AS goods_number, p.goods_attr, p.product_id, pg.goods_number AS
            order_goods_number, g.goods_sn, g.is_real, p.product_sn
            FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
                LEFT JOIN " .$GLOBALS['ecs']->table('goods') . " AS g ON pg.goods_id = g.goods_id
                LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON pg.product_id = p.product_id
            WHERE pg.package_id = '$package_id'";
    $resource = $GLOBALS['db']->query($sql);
    if (!$resource)
    {
        return array();
    }

    $row = array();

    /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
    $good_product_str = '';
    while ($_row = $GLOBALS['db']->fetch_array($resource))
    {
        if ($_row['product_id'] > 0)
        {
            /* 取存商品id */
            $good_product_str .= ',' . $_row['goods_id'];

            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
        }
        else
        {
            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'];
        }

        //生成结果数组
        $row[] = $_row;
    }
    $good_product_str = trim($good_product_str, ',');

    /* 释放空间 */
    unset($resource, $_row, $sql);

    /* 取商品属性 */
    if ($good_product_str != '')
    {
        $sql = "SELECT ga.goods_attr_id, ga.attr_value, ga.attr_price, a.attr_name
                FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a
                WHERE a.attr_id = ga.attr_id
                AND a.attr_type = 1
                AND goods_id IN ($good_product_str)";
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value)
        {
            $_goods_attr[$value['goods_attr_id']] = $value;
        }
    }

    /* 过滤货品 */
    $format[0] = "%s:%s[%d] <br>";
    $format[1] = "%s--[%d]";
    foreach ($row as $key => $value)
    {
        if ($value['goods_attr'] != '')
        {
            $goods_attr_array = explode('|', $value['goods_attr']);

            $goods_attr = array();
            foreach ($goods_attr_array as $_attr)
            {
                $goods_attr[] = sprintf($format[0], $_goods_attr[$_attr]['attr_name'], $_goods_attr[$_attr]['attr_value'], $_goods_attr[$_attr]['attr_price']);
            }

            $row[$key]['goods_attr_str'] = implode('', $goods_attr);
        }

        $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['order_goods_number']);
    }

    return $row;


//    $sql = "SELECT pg.goods_id, CONCAT(g.goods_name, ' -- [', pg.goods_number, ']') AS goods_name,
//            g.goods_number, pg.goods_number AS order_goods_number, g.goods_sn, g.is_real " .
//            "FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg, " .
//                $GLOBALS['ecs']->table('goods') . " AS g " .
//            "WHERE pg.package_id = '$package_id' " .
//            "AND pg.goods_id = g.goods_id ";
//    $row = $GLOBALS['db']->getAll($sql);
//
//    return $row;
}

/**
 * 订单单个商品或货品的已发货数量
 *
 * @param   int     $order_id       订单 id
 * @param   int     $goods_id       商品 id
 * @param   int     $product_id     货品 id
 *
 * @return  int
 */
function order_delivery_num($order_id, $goods_id, $product_id = 0)
{
    $sql = 'SELECT SUM(G.send_number) AS sums
            FROM ' . $GLOBALS['ecs']->table('delivery_goods') . ' AS G, ' . $GLOBALS['ecs']->table('delivery_order') . ' AS O
            WHERE O.delivery_id = G.delivery_id
            AND O.status = 0
            AND O.order_id = ' . $order_id . '
            AND G.extension_code <> "package_buy"
            AND G.goods_id = ' . $goods_id;

    $sql .= ($product_id > 0) ? " AND G.product_id = '$product_id'" : '';

    $sum = $GLOBALS['db']->getOne($sql);

    if (empty($sum))
    {
        $sum = 0;
    }

    return $sum;
}

/**
 * 判断订单是否已发货（含部分发货）
 * @param   int     $order_id  订单 id
 * @return  int     1，已发货；0，未发货
 */
function order_deliveryed($order_id)
{
    $return_res = 0;

    if (empty($order_id))
    {
        return $return_res;
    }

    $sql = 'SELECT COUNT(delivery_id)
            FROM ' . $GLOBALS['ecs']->table('delivery_order') . '
            WHERE order_id = \''. $order_id . '\'
            AND status = 0';
    $sum = $GLOBALS['db']->getOne($sql);

    if ($sum)
    {
        $return_res = 1;
    }

    return $return_res;
}

/**
 * 更新订单商品信息
 * @param   int     $order_id       订单 id
 * @param   array   $_sended        Array(‘商品id’ => ‘此单发货数量’)
 * @param   array   $goods_list
 * @return  Bool
 */
function update_order_goods($order_id, $_sended, $goods_list = array())
{
    if (!is_array($_sended) || empty($order_id))
    {
        return false;
    }

    foreach ($_sended as $key => $value)
    {
        // 超值礼包
        if (is_array($value))
        {
            if (!is_array($goods_list))
            {
                $goods_list = array();
            }

            foreach ($goods_list as $goods)
            {
                if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list'])))
                {
                    continue;
                }

                $goods['package_goods_list'] = package_goods($goods['package_goods_list'], $goods['goods_number'], $goods['order_id'], $goods['extension_code'], $goods['goods_id']);
                $pg_is_end = true;

                foreach ($goods['package_goods_list'] as $pg_key => $pg_value)
                {
                    if ($pg_value['order_send_number'] != $pg_value['sended'])
                    {
                        $pg_is_end = false; // 此超值礼包，此商品未全部发货

                        break;
                    }
                }

                // 超值礼包商品全部发货后更新订单商品库存
                if ($pg_is_end)
                {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
                            SET send_number = goods_number
                            WHERE order_id = '$order_id'
                            AND goods_id = '" . $goods['goods_id'] . "' ";

                    $GLOBALS['db']->query($sql, 'SILENT');
                }
            }
        }
        // 商品（实货）（货品）
        elseif (!is_array($value))
        {
            /* 检查是否为商品（实货）（货品） */
            foreach ($goods_list as $goods)
            {
                if ($goods['rec_id'] == $key && $goods['is_real'] == 1)
                {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . "
                            SET send_number = send_number + $value
                            WHERE order_id = '$order_id'
                            AND rec_id = '$key' ";
                    $GLOBALS['db']->query($sql, 'SILENT');
                    break;
                }
            }
        }
    }

    return true;
}

/**
 * 更新订单虚拟商品信息
 * @param   int     $order_id       订单 id
 * @param   array   $_sended        Array(‘商品id’ => ‘此单发货数量’)
 * @param   array   $virtual_goods  虚拟商品列表
 * @return  Bool
 */
function update_order_virtual_goods($order_id, $_sended, $virtual_goods)
{
    if (!is_array($_sended) || empty($order_id))
    {
        return false;
    }
    if (empty($virtual_goods))
    {
        return true;
    }
    elseif (!is_array($virtual_goods))
    {
        return false;
    }

    foreach ($virtual_goods as $goods)
    {
        $sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "
                SET send_number = send_number + '" . $goods['num'] . "'
                WHERE order_id = '" . $order_id . "'
                AND goods_id = '" . $goods['goods_id'] . "' ";
        if (!$GLOBALS['db']->query($sql, 'SILENT'))
        {
            return false;
        }
    }

    return true;
}

/**
 * 订单中的商品是否已经全部发货
 * @param   int     $order_id  订单 id
 * @return  int     1，全部发货；0，未全部发货
 */
function get_order_finish($order_id)
{
    $return_res = 0;

    if (empty($order_id))
    {
        return $return_res;
    }

    $sql = 'SELECT COUNT(rec_id)
            FROM ' . $GLOBALS['ecs']->table('order_goods') . '
            WHERE order_id = \'' . $order_id . '\'
            AND goods_number > send_number';

    $sum = $GLOBALS['db']->getOne($sql);
    if (empty($sum))
    {
        $return_res = 1;
    }

    return $return_res;
}

/**
 * 判断订单的发货单是否全部发货
 * @param   int     $order_id  订单 id
 * @return  int     1，全部发货；0，未全部发货；-1，部分发货；-2，完全没发货；
 */
function get_all_delivery_finish($order_id)
{
    $return_res = 0;

    if (empty($order_id))
    {
        return $return_res;
    }

    /* 未全部分单 */
    if (!get_order_finish($order_id))
    {
        return $return_res;
    }
    /* 已全部分单 */
    else
    {
        // 是否全部发货
        $sql = "SELECT COUNT(delivery_id)
                FROM " . $GLOBALS['ecs']->table('delivery_order') . "
                WHERE order_id = '$order_id'
                AND status = 2 ";
        $sum = $GLOBALS['db']->getOne($sql);
        // 全部发货
        if (empty($sum))
        {
            $return_res = 1;
        }
        // 未全部发货
        else
        {
            /* 订单全部发货中时：当前发货单总数 */
            $sql = "SELECT COUNT(delivery_id)
            FROM " . $GLOBALS['ecs']->table('delivery_order') . "
            WHERE order_id = '$order_id'
            AND status <> 1 ";
            $_sum = $GLOBALS['db']->getOne($sql);
            if ($_sum == $sum)
            {
                $return_res = -2; // 完全没发货
            }
            else
            {
                $return_res = -1; // 部分发货
            }
        }
    }

    return $return_res;
}

function trim_array_walk(&$array_value)
{
    if (is_array($array_value))
    {
        array_walk($array_value, 'trim_array_walk');
    }else{
        $array_value = trim($array_value);
    }
}

function intval_array_walk(&$array_value)
{
    if (is_array($array_value))
    {
        array_walk($array_value, 'intval_array_walk');
    }else{
        $array_value = intval($array_value);
    }
}

/**
 * 删除发货单(不包括已退货的单子)
 * @param   int     $order_id  订单 id
 * @return  int     1，成功；0，失败
 */
function del_order_delivery($order_id)
{
    $return_res = 0;

    if (empty($order_id))
    {
        return $return_res;
    }

    $sql = 'DELETE O, G
            FROM ' . $GLOBALS['ecs']->table('delivery_order') . ' AS O, ' . $GLOBALS['ecs']->table('delivery_goods') . ' AS G
            WHERE O.order_id = \'' . $order_id . '\'
            AND O.status = 0
            AND O.delivery_id = G.delivery_id';
    $query = $GLOBALS['db']->query($sql, 'SILENT');

    if ($query)
    {
        $return_res = 1;
    }

    return $return_res;
}

/**
 * 删除订单所有相关单子
 * @param   int     $order_id      订单 id
 * @param   int     $action_array  操作列表 Array('delivery', 'back', ......)
 * @return  int     1，成功；0，失败
 */
function del_delivery($order_id, $action_array)
{
    $return_res = 0;

    if (empty($order_id) || empty($action_array))
    {
        return $return_res;
    }

    $query_delivery = 1;
    $query_back = 1;
    if (in_array('delivery', $action_array))
    {
        $sql = 'DELETE O, G
                FROM ' . $GLOBALS['ecs']->table('delivery_order') . ' AS O, ' . $GLOBALS['ecs']->table('delivery_goods') . ' AS G
                WHERE O.order_id = \'' . $order_id . '\'
                AND O.delivery_id = G.delivery_id';
        $query_delivery = $GLOBALS['db']->query($sql, 'SILENT');
    }
    if (in_array('back', $action_array))
    {
        $sql = 'DELETE O, G
                FROM ' . $GLOBALS['ecs']->table('back_order') . ' AS O, ' . $GLOBALS['ecs']->table('back_goods') . ' AS G
                WHERE O.order_id = \'' . $order_id . '\'
                AND O.back_id = G.back_id';
        $query_back = $GLOBALS['db']->query($sql, 'SILENT');
    }

    if ($query_delivery && $query_back)
    {
        $return_res = 1;
    }

    return $return_res;
}

/**
 *  获取发货单列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function delivery_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['order_id'] = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
        if ($aiax == 1 && !empty($_REQUEST['consignee']))
        {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : -1;

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'update_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 ';
        if ($filter['order_sn'])
        {
            $where .= " AND order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }
        if ($filter['consignee'])
        {
            $where .= " AND consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['status'] >= 0)
        {
            $where .= " AND status = '" . mysql_like_quote($filter['status']) . "'";
        }
        if ($filter['delivery_sn'])
        {
            $where .= " AND delivery_sn LIKE '%" . mysql_like_quote($filter['delivery_sn']) . "%'";
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0)
        {
            $where .= " AND agency_id = '" . $admin_info['agency_id'] . "' ";
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0)
        {
            //$where .= " AND suppliers_id = '" . $admin_info['suppliers_id'] . "' ";
            $where .= " AND suppliers_id in (" . $admin_info['suppliers_id'] . ") ";
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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('delivery_order') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT delivery_id, delivery_sn, order_sn, order_id, add_time, action_user, consignee, country,
                       province, city, district, tel, status, update_time, email, suppliers_id
                FROM " . $GLOBALS['ecs']->table("delivery_order") . "
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

    /* 获取供货商列表 */
    $suppliers_list = get_suppliers_list();
    $_suppliers_list = array();
    foreach ($suppliers_list as $value)
    {
        $_suppliers_list[$value['suppliers_id']] = $value['suppliers_name'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    /* 格式化数据 */
    foreach ($row AS $key => $value)
    {
        $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
        $row[$key]['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['update_time']);
        if ($value['status'] == 1)
        {
            $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
        }
        elseif ($value['status'] == 2)
        {
            $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][2];
        }
        else
        {
            $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
        }
        $row[$key]['suppliers_name'] = isset($_suppliers_list[$value['suppliers_id']]) ? $_suppliers_list[$value['suppliers_id']] : '';
    }
    $arr = array('delivery' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 *  获取退货单列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function back_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['order_id'] = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
        if ($aiax == 1 && !empty($_REQUEST['consignee']))
        {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'update_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 ';
        if ($filter['order_sn'])
        {
            $where .= " AND order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }
        if ($filter['consignee'])
        {
            $where .= " AND consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['delivery_sn'])
        {
            $where .= " AND delivery_sn LIKE '%" . mysql_like_quote($filter['delivery_sn']) . "%'";
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0)
        {
            $where .= " AND agency_id = '" . $admin_info['agency_id'] . "' ";
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0)
        {
            //$where .= " AND suppliers_id = '" . $admin_info['suppliers_id'] . "' ";
            $where .= " AND suppliers_id in (" . $admin_info['suppliers_id'] . ") ";
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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') . $where;

        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT back_id, delivery_sn, order_sn, order_id, add_time, action_user, consignee, country,
                       province, city, district, tel, status, update_time, email, return_time
                FROM " . $GLOBALS['ecs']->table("back_order") . "
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

    /* 格式化数据 */
    foreach ($row AS $key => $value)
    {
        $row[$key]['return_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['return_time']);
        $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
        $row[$key]['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['update_time']);
        if ($value['status'] == 1)
        {
            $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
        }
        else
        {
            $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
        }
    }
    $arr = array('back' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 取得发货单信息
 * @param   int     $delivery_order   发货单id（如果delivery_order > 0 就按id查，否则按sn查）
 * @param   string  $delivery_sn      发货单号
 * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function delivery_order_info($delivery_id, $delivery_sn = '')
{
    $return_order = array();
    if (empty($delivery_id) || !is_numeric($delivery_id))
    {
        return $return_order;
    }

    $where = '';
    /* 获取管理员信息 */
    $admin_info = admin_info();

    /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
    if ($admin_info['agency_id'] > 0)
    {
        $where .= " AND agency_id = '" . $admin_info['agency_id'] . "' ";
    }

    /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
    if ($admin_info['suppliers_id'] > 0)
    {
        $where .= " AND suppliers_id in (" . $admin_info['suppliers_id'] . ") ";
    }

    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('delivery_order');
    if ($delivery_id > 0)
    {
        $sql .= " WHERE delivery_id = '$delivery_id'";
    }
    else
    {
        $sql .= " WHERE delivery_sn = '$delivery_sn'";
    }

    $sql .= $where;
    $sql .= " LIMIT 0, 1";
    $delivery = $GLOBALS['db']->getRow($sql);
    if ($delivery)
    {
        /* 格式化金额字段 */
        $delivery['formated_insure_fee']     = price_format($delivery['insure_fee'], false);
        $delivery['formated_shipping_fee']   = price_format($delivery['shipping_fee'], false);

        /* 格式化时间字段 */
        $delivery['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $delivery['add_time']);
        $delivery['formated_update_time']    = local_date($GLOBALS['_CFG']['time_format'], $delivery['update_time']);

        $return_order = $delivery;
    }

    return $return_order;
}

/**
 * 取得退货单信息
 * @param   int     $back_id   退货单 id（如果 back_id > 0 就按 id 查，否则按 sn 查）
 * @return  array   退货单信息（金额都有相应格式化的字段，前缀是 formated_ ）
 */
function back_order_info($back_id)
{
    $return_order = array();
    if (empty($back_id) || !is_numeric($back_id))
    {
        return $return_order;
    }

    $where = '';
    /* 获取管理员信息 */
    $admin_info = admin_info();

    /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
    if ($admin_info['agency_id'] > 0)
    {
        $where .= " AND agency_id = '" . $admin_info['agency_id'] . "' ";
    }

    /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
    if ($admin_info['suppliers_id'] > 0)
    {
        $where .= " AND suppliers_id = '" . $admin_info['suppliers_id'] . "' ";
    }

    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('back_order') . "
            WHERE back_id = '$back_id'
            $where
            LIMIT 0, 1";
    $back = $GLOBALS['db']->getRow($sql);
    if ($back)
    {
        /* 格式化金额字段 */
        $back['formated_insure_fee']     = price_format($back['insure_fee'], false);
        $back['formated_shipping_fee']   = price_format($back['shipping_fee'], false);

        /* 格式化时间字段 */
        $back['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $back['add_time']);
        $back['formated_update_time']    = local_date($GLOBALS['_CFG']['time_format'], $back['update_time']);
        $back['formated_return_time']    = local_date($GLOBALS['_CFG']['time_format'], $back['return_time']);

        $return_order = $back;
    }

    return $return_order;
}

/**
 * 超级礼包发货数处理
 * @param   array   超级礼包商品列表
 * @param   int     发货数量
 * @param   int     订单ID
 * @param   varchar 虚拟代码
 * @param   int     礼包ID
 * @return  array   格式化结果
 */
function package_goods(&$package_goods, $goods_number, $order_id, $extension_code, $package_id)
{
    $return_array = array();

    if (count($package_goods) == 0 || !is_numeric($goods_number))
    {
        return $return_array;
    }

    foreach ($package_goods as $key=>$value)
    {
        $return_array[$key] = $value;
        $return_array[$key]['order_send_number'] = $value['order_goods_number'] * $goods_number;
        $return_array[$key]['sended'] = package_sended($package_id, $value['goods_id'], $order_id, $extension_code, $value['product_id']);
        $return_array[$key]['send'] = ($value['order_goods_number'] * $goods_number) - $return_array[$key]['sended'];
        $return_array[$key]['storage'] = $value['goods_number'];


        if ($return_array[$key]['send'] <= 0)
        {
            $return_array[$key]['send'] = $GLOBALS['_LANG']['act_good_delivery'];
            $return_array[$key]['readonly'] = 'readonly="readonly"';
        }

        /* 是否缺货 */
        if ($return_array[$key]['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1')
        {
            $return_array[$key]['send'] = $GLOBALS['_LANG']['act_good_vacancy'];
            $return_array[$key]['readonly'] = 'readonly="readonly"';
        }
    }

    return $return_array;
}

/**
 * 获取超级礼包商品已发货数
 *
 * @param       int         $package_id         礼包ID
 * @param       int         $goods_id           礼包的产品ID
 * @param       int         $order_id           订单ID
 * @param       varchar     $extension_code     虚拟代码
 * @param       int         $product_id         货品id
 *
 * @return  int     数值
 */
function package_sended($package_id, $goods_id, $order_id, $extension_code, $product_id = 0)
{
    if (empty($package_id) || empty($goods_id) || empty($order_id) || empty($extension_code))
    {
        return false;
    }

    $sql = "SELECT SUM(DG.send_number)
            FROM " . $GLOBALS['ecs']->table('delivery_goods') . " AS DG, " . $GLOBALS['ecs']->table('delivery_order') . " AS o
            WHERE o.delivery_id = DG.delivery_id
            AND o.status IN (0, 2)
            AND o.order_id = '$order_id'
            AND DG.parent_id = '$package_id'
            AND DG.goods_id = '$goods_id'
            AND DG.extension_code = '$extension_code'";
    $sql .= ($product_id > 0) ? " AND DG.product_id = '$product_id'" : '';

    $send = $GLOBALS['db']->getOne($sql);

    return empty($send) ? 0 : $send;
}

/**
 * 改变订单中商品库存
 * @param   int     $order_id  订单 id
 * @param   array   $_sended   Array(‘商品id’ => ‘此单发货数量’)
 * @param   array   $goods_list
 * @return  Bool
 */
function change_order_goods_storage_split($order_id, $_sended, $goods_list = array())
{
    /* 参数检查 */
    if (!is_array($_sended) || empty($order_id))
    {
        return false;
    }

    foreach ($_sended as $key => $value)
    {
        // 商品（超值礼包）
        if (is_array($value))
        {
            if (!is_array($goods_list))
            {
                $goods_list = array();
            }
            foreach ($goods_list as $goods)
            {
                if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list'])))
                {
                    continue;
                }

                // 超值礼包无库存，只减超值礼包商品库存
                foreach ($goods['package_goods_list'] as $package_goods)
                {
                    if (!isset($value[$package_goods['goods_id']]))
                    {
                        continue;
                    }

                    // 减库存：商品（超值礼包）（实货）、商品（超值礼包）（虚货）
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') ."
                            SET goods_number = goods_number - '" . $value[$package_goods['goods_id']] . "'
                            WHERE goods_id = '" . $package_goods['goods_id'] . "' ";
                    $GLOBALS['db']->query($sql);
                }
            }
        }
        // 商品（实货）
        elseif (!is_array($value))
        {
            /* 检查是否为商品（实货） */
            foreach ($goods_list as $goods)
            {
                if ($goods['rec_id'] == $key && $goods['is_real'] == 1)
                {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . "
                            SET goods_number = goods_number - '" . $value . "'
                            WHERE goods_id = '" . $goods['goods_id'] . "' ";
                    $GLOBALS['db']->query($sql, 'SILENT');
                    break;
                }
            }
        }
    }

    return true;
}

/**
 *  超值礼包虚拟卡发货、跳过修改订单商品发货数的虚拟卡发货
 *
 * @access  public
 * @param   array      $goods      超值礼包虚拟商品列表数组
 * @param   string      $order_sn   本次操作的订单
 *
 * @return  boolen
 */
function package_virtual_card_shipping($goods, $order_sn)
{
    if (!is_array($goods))
    {
        return false;
    }

    /* 包含加密解密函数所在文件 */
    include_once(ROOT_PATH . 'includes/lib_code.php');

    // 取出超值礼包中的虚拟商品信息
    foreach ($goods as $virtual_goods_key => $virtual_goods_value)
    {
        /* 取出卡片信息 */
        $sql = "SELECT card_id, card_sn, card_password, end_date, crc32
                FROM ".$GLOBALS['ecs']->table('virtual_card')."
                WHERE goods_id = '" . $virtual_goods_value['goods_id'] . "'
                AND is_saled = 0
                LIMIT " . $virtual_goods_value['num'];
        $arr = $GLOBALS['db']->getAll($sql);
        /* 判断是否有库存 没有则推出循环 */
        if (count($arr) == 0)
        {
            continue;
        }

        $card_ids = array();
        $cards = array();

        foreach ($arr as $virtual_card)
        {
            $card_info = array();

            /* 卡号和密码解密 */
            if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY))
            {
                $card_info['card_sn'] = decrypt($virtual_card['card_sn']);
                $card_info['card_password'] = decrypt($virtual_card['card_password']);
            }
            elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY))
            {
                $card_info['card_sn'] = decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
                $card_info['card_password'] = decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
            }
            else
            {
                return false;
            }
            $card_info['end_date'] = date($GLOBALS['_CFG']['date_format'], $virtual_card['end_date']);
            $card_ids[] = $virtual_card['card_id'];
            $cards[] = $card_info;
        }

        /* 标记已经取出的卡片 */
        $sql = "UPDATE ".$GLOBALS['ecs']->table('virtual_card')." SET ".
            "is_saled = 1 ,".
            "order_sn = '$order_sn' ".
            "WHERE " . db_create_in($card_ids, 'card_id');
        if (!$GLOBALS['db']->query($sql))
        {
            return false;
        }

        /* 获取订单信息 */
        $sql = "SELECT order_id, order_sn, consignee, email FROM ".$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '$order_sn'";
        $order = $GLOBALS['db']->GetRow($sql);

        $cfg = $GLOBALS['_CFG']['send_ship_email'];
        if ($cfg == '1')
        {
            /* 发送邮件 */
            $GLOBALS['smarty']->assign('virtual_card',                   $cards);
            $GLOBALS['smarty']->assign('order',                          $order);
            $GLOBALS['smarty']->assign('goods',                          $virtual_goods_value);

            $GLOBALS['smarty']->assign('send_time', date('Y-m-d H:i:s'));
            $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
            $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
            $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));

            $tpl = get_mail_template('virtual_card');
            $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
            send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
        }
    }

    return true;
}

/**
 * 删除发货单时进行退货
 *
 * @access   public
 * @param    int     $delivery_id      发货单id
 * @param    array   $delivery_order   发货单信息数组
 *
 * @return  void
 */
function delivery_return_goods($delivery_id, $delivery_order)
{
    /* 查询：取得发货单商品 */
    $goods_sql = "SELECT *
                 FROM " . $GLOBALS['ecs']->table('delivery_goods') . "
                 WHERE delivery_id = " . $delivery_order['delivery_id'];
    $goods_list = $GLOBALS['db']->getAll($goods_sql);
    /* 更新： */
    foreach ($goods_list as $key=>$val)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') .
            " SET send_number = send_number-'".$goods_list[$key]['send_number']. "'".
            " WHERE order_id = '".$delivery_order['order_id']."' AND goods_id = '".$goods_list[$key]['goods_id']."' LIMIT 1";
        $GLOBALS['db']->query($sql);
    }
    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
        " SET shipping_status = '0' , order_status = 1".
        " WHERE order_id = '".$delivery_order['order_id']."' LIMIT 1";
    $GLOBALS['db']->query($sql);
}

/**
 * 删除发货单时删除其在订单中的发货单号
 *
 * @access   public
 * @param    int      $order_id              定单id
 * @param    string   $delivery_invoice_no   发货单号
 *
 * @return  void
 */
function del_order_invoice_no($order_id, $delivery_invoice_no)
{
    /* 查询：取得订单中的发货单号 */
    $sql = "SELECT invoice_no
            FROM " . $GLOBALS['ecs']->table('order_info') . "
            WHERE order_id = '$order_id'";
    $order_invoice_no = $GLOBALS['db']->getOne($sql);

    /* 如果为空就结束处理 */
    if (empty($order_invoice_no))
    {
        return;
    }

    /* 去除当前发货单号 */
    $order_array = explode('<br>', $order_invoice_no);
    $delivery_array = explode('<br>', $delivery_invoice_no);

    foreach ($order_array as $key => $invoice_no)
    {
        if ($ii = array_search($invoice_no, $delivery_array))
        {
            unset($order_array[$key], $delivery_array[$ii]);
        }
    }

    $arr['invoice_no'] = implode('<br>', $order_array);
    update_order($order_id, $arr);
}

/**
 * 获取站点根目录网址
 *
 * @access  private
 * @return  Bool
 */
function get_site_root_url()
{
    return defined('FORCE_SSL_LOGIN') ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/' . ADMIN_PATH . '/order.php', '', PHP_SELF);

}


/**
 * 判断管理员是否是超级管理员（绑定云起的）
 */
function is_super_admin(){
    $sql = "SELECT action_list
            FROM " . $GLOBALS['ecs']->table('admin_user') . "
            WHERE user_id = {$_SESSION['admin_id']}";
    $rs=$GLOBALS['db']->getOne($sql);
    if(!empty($rs) and $rs=='all'){
        return 1;
    }
    return 0;
}

// 更新订单到crm
function update_order_crm($order_sn){
    $matrix = new matrix();
    $bind_info = $matrix->get_bind_info(array('ecos.taocrm'));
    if($bind_info){
        $is_succ = $matrix->updateOrder($order_sn,'ecos.taocrm');
        return $is_succ;
    }
    return true;
}
// 退款通知到crm
function send_refund_to_crm($data){
    $msg['tid'] = $data['order_id'];
    $msg['refund_id'] = $data['order_id'];
    $msg['refund_fee'] = $data['cur_money'];
    $msg['status'] = 'SUCC';
    $msg['t_begin'] = date('Y-m-d H:i:s',time());
    include_once(ROOT_PATH . 'includes/cls_matrix.php');
    $matrix = new matrix;
    $bind_info = $matrix->get_bind_info(array('ecos.taocrm'));
    if($bind_info){
        $is_succ = $matrix->send_refund_to_crm($msg);
    }
}

/**
 *  获取导入运单号列表
 *
 * @access  public
 * @param
 *
 * @return array
 */
function import_invoice_list($admin_type,$suppliers_id,$admin_name)
{
    if(!isset($admin_type) || !isset($suppliers_id)){
        $url = 'privilege.php?act=login';
        ecs_header("Location: $url\n");
        exit;
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

    if($admin_type == 0 && $suppliers_id == 0){
        $where = '';
    }else{
        $suppliers_arr= explode(',',$suppliers_id);
        $str = '';
        foreach ($suppliers_arr as $v){
            $str.= "'".$v."',";
        }
       $str = trim($str,',');
        $where = "where b.suppliers_id in($str)";
    }

    $num = strpos($str,',');
    /* 记录总数 */
    $sql = "SELECT id FROM " . $GLOBALS['ecs']->table('import_invoice') ." as a left join ".$GLOBALS['ecs']->table('admin_user')." as b on a.admin_name=b.user_name ". $where;
    $arr = $GLOBALS['db']->getAll($sql);
    if($num){
        $sql = "select count(*) from ". $GLOBALS['ecs']->table('import_invoice') ." where admin_name = '".$admin_name."'";
        $number = $GLOBALS['db']->getOne($sql);
        $count = intval(count($arr)+ $number);
    }else{
        $count = count($arr);
    }

    $filter['record_count']   = $count;
    $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

    /* 查询 */
    $sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('import_invoice') ." as a left join ".$GLOBALS['ecs']->table('admin_user')." as b on a.admin_name=b.user_name ". $where ." ORDER BY a.addtime desc LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";
    $row = $GLOBALS['db']->getAll($sql);
    if($num){
        $sql = "select * from ". $GLOBALS['ecs']->table('import_invoice') ." where admin_name = '".$admin_name."'";
        $array = $GLOBALS['db']->getAll($sql);
        $row = array_merge($row,$array);
    }
    /* 格式话数据 */
    $status = ['待处理','处理中','处理成功','处理失败'];
    foreach ($row AS $key => $value)
    {
        $row[$key]['formated_addtime'] = date('Y-m-d H:i:s', $value['addtime']);
        $row[$key]['formated_status'] = $status[$value['status']];
    }
    $arr = array('list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

/**
 * 生成随机数
 */
function genRandomString($len){
	$str1 = array(
			"a", "b", "c", "d", "e", "f", "g", "h", "j", "k",
			"m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
			"w", "x", "y", "z",
	);
	$str2 = array(
			"1",  "2", "3", "4", "5", "6", "7", "8", "9"
	);


	$str1Len = count($str1) - 1;
	$str2Len = count($str2) - 1;
	shuffle($str1);shuffle($str2);// 将数组打乱
	$output = "";
	for ($i=0; $i<$len; $i++){
		$output .= $str1[mt_rand(0, $str1Len)];
		$output .= $str2[mt_rand(0, $str2Len)];
	}
	$res = 'hhyw'.date('Ymd'.'His').$output;
	return $res;
}

/**
 * 编辑某个字段
 *
 * @access  public
 * @param   string      $set        要更新集合如" col = '$name', value = '$value'"
 * @param   int         $id         要更新的记录编号
 *
 * @return bool     成功或失败
 */
function edits($set, $id)
{
    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . '  SET ' . $set . " WHERE  order_id = '$id'";
    if ($GLOBALS['db']->query($sql)) {
        return true;
    } else {
        return false;
    }
}


function get_ids($cat_id = 1)
{
    $ids = '';
    $sql    = "select cat_id from ". $GLOBALS['ecs']->table('category') ." where parent_id = {$cat_id}";
    $result = $GLOBALS['db']->getAll($sql);
    if (!empty($result)) {
        foreach ($result as $k => $v){
            $ids .= $v['cat_id'].',';
            $ids .= get_ids($v['cat_id']);
        }
    }
    return $ids;
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

function numToStr($num)
{
    $result = "";
    if (stripos($num, '.') === false) {
        return $num;
    }
    while ($num > 0) {
        $v = $num - floor($num / 10) * 10;
        $num = floor($num / 10);
        $result = $v . $result;
    }
    return $result;
}
// 计算分期逾期时间
function getOverTime($exceptTime, $realTime){
    if($realTime == 0){
        $realTime = time();
    }
    $exceptDay = date('Ymd', $exceptTime);
    $realDay   = date('Ymd', $realTime);
    if($realDay <= $exceptDay){
        return '无逾期';
    } else {
        return '逾期' . ($realDay - $exceptDay) . '天';
    }
}

// 获取分期订单状态
function getInstalmentStatus($status, $real_pay_time){
    global $_LANG;
    if($status == INSTALMENT_OS_CONFIRMED){
        return $_LANG['instalment_os'][1];
    } else if($status == INSTALMENT_OS_CANCEL) {
        return $real_pay_time > 0 ? $_LANG['instalment_os'][2] : $_LANG['instalment_os'][3];
    } else {
        return  $_LANG['instalment_os'][0];
    }
}

// 获取分期支付方式
function getInstalmentMethod($pay_id, $methods){
    // dd($pay_id);
    foreach($methods as $item){
        if($item['pay_id'] == $pay_id){
            return $item['pay_name'];
        }
    }

    return '';
}


/**
 * 数字转字母 （类似于Excel列标）
 * @param Int $index 索引值
 * @param Int $start 字母起始值
 * @return String 返回字母
 */
function IntToChr($index, $start = 65) {
    $str = '';
    if (floor($index / 26) > 0) {
        $str .= IntToChr(floor($index / 26)-1);
    }
    return $str . chr($index % 26 + $start);
}


?>
