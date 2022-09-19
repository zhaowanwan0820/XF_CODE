<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------

if (!defined('THINK_PATH')) exit();

//过滤请求
filter_request($_REQUEST);
filter_request($_GET);
filter_request($_POST);
define("AUTH_NOT_LOGIN", 1); //未登录的常量
define("AUTH_NOT_AUTH", 2);  //未授权常量

use core\service\user\UserService;
use core\service\user\BankService;
use core\enum\UserEnum;
use core\enum\EnterpriseEnum;
// 全站公共函数库
// 更改系统配置, 当更改数据库配置时为永久性修改， 修改配置文档中配置为临时修改
function conf($name,$value = false,$site_id=0)
{
    if($value === false)
    {
        return C($name);
    }
    else
    {
        $sql_where = "is_effect=1 and name='".$name."' and site_id=".$site_id;
        if(M("Conf")->where($sql_where)->count()>0)
        {
            if(in_array($name,array('EXPIRED_TIME','SUBMIT_DELAY','SEND_SPAN','WATER_ALPHA','MAX_IMAGE_SIZE','INDEX_LEFT_STORE','INDEX_LEFT_TUAN','INDEX_LEFT_YOUHUI','INDEX_LEFT_DAIJIN','INDEX_LEFT_EVENT','INDEX_RIGHT_STORE','INDEX_RIGHT_TUAN','INDEX_RIGHT_YOUHUI','INDEX_RIGHT_DAIJIN','INDEX_RIGHT_EVENT','SIDE_DEAL_COUNT','DEAL_PAGE_SIZE','PAGE_SIZE','BATCH_PAGE_SIZE','HELP_CATE_LIMIT','HELP_ITEM_LIMIT','REC_HOT_LIMIT','REC_NEW_LIMIT','REC_BEST_LIMIT','REC_CATE_GOODS_LIMIT','SALE_LIST','INDEX_NOTICE_COUNT','RELATE_GOODS_LIMIT')))
            {
                $value = intval($value);
            }
            M("Conf")->where($sql_where)->setField("value",$value);
        }
        C($name,$value);
    }
}


function write_timezone($zone='')
{
    if($zone=='')
        $zone = conf('TIME_ZONE');
    $var = array(
        '0'    =>    'UTC',
        '8'    =>    'PRC',
    );

    //开始将$db_config写入配置
    $timezone_config_str      =     "<?php\r\n";
    $timezone_config_str    .=    "return array(\r\n";
    $timezone_config_str.="'DEFAULT_TIMEZONE'=>'".$var[$zone]."',\r\n";

    $timezone_config_str.=");\r\n";
    $timezone_config_str.='?'.'>';

    //@file_put_contents(get_real_path()."public/timezone_config.php",$timezone_config_str);
}

/**
 * 后台操作日志记录
 * @param string $msg 日志摘要
 * @param integer $status 操作结果 0 失败， 1 成功
 * @param mixed $oldData 旧数据 一般记录业务变更前数据或者访问信息
 * @param mixed $newData 新数据 用于记录数据更新后的状态，比如说UPDATE操作修改后的记录值，INSERT操作记录新添加的记录数据
 * @param integer $storage_type 存储类型 1 db， 2 日志文件
 * @return bool
 */
function save_log($msg, $status, $oldData = '', $newData = '', $storage_type = 1)
{
    $migrate_switch = app_conf('NCFPH_MIGRATE_SWITH');
    if (!empty($migrate_switch)) {
        return true;
    }

    if (conf("ADMIN_LOG") == 1) {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $log_data['log_info'] = $msg;
        $log_data['log_time'] = get_gmtime();
        $log_data['log_admin'] = intval($adm_session['adm_id']);
        $log_data['log_ip'] = get_client_ip();
        $log_data['log_status'] = $status;
        $log_data['module'] = MODULE_NAME;
        $log_data['action'] = ACTION_NAME;
        $log_data['extra_info'] = var_export($oldData, true);
        $log_data['new_info'] = var_export($newData, true);
        if ($storage_type == C('SAVE_LOG_DB')) {   // db 存储
            $GLOBALS['db']->autoExecute('firstp2p_log', $log_data, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0) {
              return false;
            }
        } else {    // 文件存储
            $log = new libs\utils\Logger();
            $msg = ''; //json_encode($log_data);
            $msgs = array();
            foreach ($log_data as $key => $val) {
                $msgs[] = "{$key}:{$val}";
            }
            if (count($msgs)) {
                $msg = implode("\t", $msgs);
            }
            $log->wLog($msg, $log::ADMIN);
        }
    }
    return true;
}


//状态的显示
function get_toogle_status($tag,$id,$field)
{
    if($tag)
    {
        return "<span class='is_effect' onclick=\"toogle_status(".$id.",this,'".$field."');\">".l("YES")."</span>";
    }
    else
    {
        return "<span class='is_effect' onclick=\"toogle_status(".$id.",this,'".$field."');\">".l("NO")."</span>";
    }
}

//状态的显示
function get_is_effect($tag,$id)
{
    if($tag)
    {
        return "<span class='is_effect' onclick='set_effect(".$id.",this);'>".l("IS_EFFECT_1")."</span>";
    }
    else
    {
        return "<span class='is_effect' onclick='set_effect(".$id.",this);'>".l("IS_EFFECT_0")."</span>";
    }
}

//企业用户-状态的显示 Add By guofeng At 20151225 16:32
function get_is_effect_enterprise($tag, $id)
{
    if($tag)
    {
        return "<span class='is_effect' onclick='set_effect_enterprise(".$id.",this);'>".l("IS_EFFECT_1")."</span>";
    }
    else
    {
        return "<span class='is_effect' onclick='set_effect_enterprise(".$id.",this);'>".l("IS_EFFECT_0")."</span>";
    }
}

//优惠码状态的显示
function get_coupon_disbale($tag,$id)
{
    if($tag)
    {
        return "<span class='is_effect' onclick='set_coupon_disable(".$id.",this);'>".l("IS_EFFECT_0")."</span>";
    }
    else
    {
        return "<span class='is_effect' onclick='set_coupon_disable(".$id.",this);'>".l("IS_EFFECT_1")."</span>";
    }
}

//状态的显示
function get_is_delete($tag,$id)
{
    if($tag)
    {
        return "<a href='javascript:restore(".$id.",this);'>".l("RESTORE")."</a>";
    }
    else
    {
        return "<a href='javascript:del(".$id.",this);'>".l("DELETE")."</a>";
    }
}

//排序显示
function get_sort($sort,$id)
{
    if($tag)
    {
        return "<span class='sort_span' onclick='set_sort(".$id.",".$sort.",this);'>".$sort."</span>";
    }
    else
    {
        return "<span class='sort_span' onclick='set_sort(".$id.",".$sort.",this);'>".$sort."</span>";
    }
}
function get_nav($nav_id)
{
    return M("RoleNav")->where("id=".$nav_id)->getField("name");
}
function get_module($module_id)
{
    return M("RoleModule")->where("id=".$module_id)->getField("module");
}
function get_group($group_id)
{
    if($group_data = M("RoleGroup")->where("id=".$group_id)->find())
        $group_name = $group_data['name'];
    else
        $group_name = L("SYSTEM_NODE");
    return $group_name;
}
function get_role_name($role_id)
{
    return M("Role")->where("id=".$role_id)->getField("name");
}

$_adminNameMap = array();

function get_admin_name($admin_id)
{
    if (empty($admin_id)) {
        return 'system';
    }

    global $_adminNameMap;
    if (!isset($_adminNameMap[$admin_id])) {
        $_adminNameMap[$admin_id] = M("Admin")->where("id=".$admin_id)->getField("adm_name");
    }

    return $_adminNameMap[$admin_id];
}

function get_log_status($status)
{
    return l("LOG_STATUS_".$status);
}
//验证相关的函数
//验证排序字段
function check_sort($sort)
{
    if(!is_numeric($sort))
    {
        return false;
    }
    if(intval($sort)<=0)
    {
        return false;
    }
    return true;
}
function check_empty($data)
{
    if(trim($data)=='')
    {
        return false;
    }
    return true;
}

function set_default($null,$adm_id)
{

    $admin_name = M("Admin")->where("id=".$adm_id)->getField("adm_name");
    if($admin_name == conf("DEFAULT_ADMIN"))
    {
        return "<span style='color:#f30;'>".l("DEFAULT_ADMIN")."</span>";
    }
    else
    {
        return "<a href='".u("Admin/set_default",array("id"=>$adm_id))."'>".l("SET_DEFAULT_ADMIN")."</a>";
    }
}
function get_order_sn($order_id)
{
    return M("DealOrder")->where("id=".$order_id)->getField("order_sn");
}
function get_order_sn_with_link($notice_sn)
{
    return l('DEAL_ORDER_TYPE_1') . '：' . $notice_sn;
//     $order_info = M("DealOrder")->where("id=".$order_id)->find();
//     if($order_info['type']==0)
//         $str = l("DEAL_ORDER_TYPE_0")."：<a href='".u("DealOrder/deal_index",array("order_sn"=>$order_info['order_sn']))."'>".$order_info['order_sn']."</a>";
//     else
//         $str = l("DEAL_ORDER_TYPE_1")."：<a href='".u("DealOrder/incharge_index",array("order_sn"=>$order_info['order_sn']))."'>".$order_info['order_sn']."</a>";

//     if($order_info['is_delete']==1)
//         $str ="<span style='text-decoration:line-through;'>".$str."</span>";
//     return $str;
}
function get_user_name($user_id, $field = 'user_name')
{
    if(intval($user_id)<=0){
        return "";
    }
    // Edit By guofeng 20160612 11:25
    static $globalUserList = array();
    if (isset($globalUserList[$user_id]) && !empty($globalUserList[$user_id]))
    {
        $user_info = $globalUserList[$user_id];
    }else{
        $user_info = $globalUserList[$user_id] = UserService::getUserByCondition("id=".$user_id." and is_delete = 0");
    }

    // 会员类型
    if (!empty($user_info) && (UserEnum::USER_TYPE_ENTERPRISE == $user_info['user_type']) && (UserEnum::TABLE_FIELD_REAL_NAME == $field)) {
        $user_action = 'Enterprise';
        $field = EnterpriseEnum ::TABLE_FIELD_COMPANY_NAME;
        // 获取企业名称
        $enterpriseInfo = UserService::getEnterpriseInfo($user_info['id']);
        $user_name = $enterpriseInfo[$field];
    } else {
        $user_action = 'User';
        $user_name = $user_info[$field];
    }


    if(!$user_name)
        return $field == 'user_name' ? l("NO_USER") : '';
    else
        return "<a href='".u("{$user_action}/index",array($field=>$user_name))."' target='_blank'>".$user_name."</a>";
}

function get_user_name_js($user_id)
{
    if(intval($user_id)<=0){
        return "";
    }
    $userInfo = UserService::getUserByCondition("id=".$user_id." and is_delete = 0");
    $user_name = !empty($userInfo['user_name']) ? $userInfo['user_name'] : '';

    if(!$user_name)
        return l("NO_USER");
    else
        return "<a href='javascript:void(0);' onclick='account(".$user_id.")'>".$user_name."</a>";
}

// 查询优惠券短码
function get_user_coupons($user_id) {
    $coupon_service = new \core\service\CouponService();
    $user_coupons = $coupon_service->getUserCoupons($user_id);
    return implode(" | ", array_keys($user_coupons));
}

function get_user_coupon_level($user_id) {
    $coupon_level_service = new \core\service\CouponLevelService();
    $user_level = $coupon_level_service->getUserLevel($user_id);
    return $user_level['group_name'] . " - " . $user_level['level'];
}

function get_pay_status($status)
{
    return L("PAY_STATUS_".$status);
}
function get_delivery_status($status,$order_id)
{
    //,notice_sn|get_notice_info=$deal_order['notice_id']:{%DELIVERY_SN}
    $order_item_ids = $GLOBALS['db']->getOne("select group_concat(id) from ".DB_PREFIX."deal_order_item where order_id = ".intval($order_id));
    if(!$order_item_ids)
        $order_item_ids = 0;
    $rs = $GLOBALS['db']->getAll("select dn.notice_sn,dn.id from ".DB_PREFIX."delivery_notice as dn where dn.order_item_id in (".$order_item_ids.") ");
    $result = "";
    foreach($rs as $row)
    {
        $result .= "&nbsp;".get_notice_info($row['notice_sn'],$row['id'])."<br />";
    }
    return L("ORDER_DELIVERY_STATUS_".$status)."<br />".$result;
}
function get_notice_info($sn,$notice_id)
{
    $express_name = M()->query("select e.name as ename from ".DB_PREFIX."express as e left join ".DB_PREFIX."delivery_notice as dn on dn.express_id = e.id where dn.id = ".$notice_id);
    $express_name = $express_name[0]['ename'];
    if($express_name)
        $str = $express_name."<br/>&nbsp;".$sn;
    else
        $str = $sn;
    return $str;
}
function get_payment_name($payment_id)
{
    // Edit By guofeng 20160612 11:25
    static $globalPaymentList = array();
    if (isset($globalPaymentList[$payment_id]) && !empty($globalPaymentList[$payment_id]))
    {
        $paymentInfo = $globalPaymentList[$payment_id];
    }else{
        $paymentInfo = $globalPaymentList[$payment_id] = MI("Payment")->where("id=".$payment_id)->getField("name");
    }
    return $paymentInfo;
}
function get_delivery_name($delivery_id)
{
    return M("Delivery")->where("id=".$delivery_id)->getField("name");
}
function get_region_name($region_id)
{
    return M("DeliveryRegion")->where("id=".$region_id)->getField("name");
}
function get_city_name($id)
{
    return M("DealCity")->where("id=".$id)->getField("name");
}
function get_message_is_effect($status)
{
    return $status==1?l("YES"):l("NO");
}
function get_message_type($type_name,$rel_id)
{
    $show_name = M("MessageType")->where("type_name='".$type_name."'")->getField("show_name");
    if($type_name=='deal_order')
    {
        $order_sn = M("DealOrder")->where("id=".$rel_id)->getField("order_sn");
        if($order_sn)
            return "[".$order_sn."] <a href='".u("DealOrder/deal_index",array("id"=>$rel_id))."'>".$show_name."</a>";
        else
            return $show_name;
    }
    elseif($type_name=='deal')
    {
        $sub_name = M("Deal")->where("id=".$rel_id)->getField("sub_name");
        if($sub_name)
            return "[".$sub_name."]" .$show_name;
        else
            return $show_name;
    }
    elseif($type_name=='supplier')
    {
        $name = M("Supplier")->where("id=".$rel_id)->getField("name");
        if($name)
            return "[".$name."] <a href='".u("Supplier/index",array("id"=>$rel_id))."'>".$show_name."</a>";
        else
            return $show_name;
    }
    else
    {
        if($show_name)
            return $show_name;
        else
            return $type_name;
    }
}

function get_send_status($status)
{
    return L("SEND_STATUS_".$status);
}
function get_send_mail_type($deal_id)
{
    if($deal_id>0)
        return l("DEAL_NOTICE");
    else
        return l("COMMON_NOTICE");
}
function get_send_type($send_type)
{
    return l("SEND_TYPE_".$send_type);
}

function get_all_files( $path )
{
    $list = array();
    $dir = @opendir($path);
    if (empty($dir)) {
        return $list;
    }
    while (false !== ($file = @readdir($dir)))
    {
        if($file!='.'&&$file!='..')
            if( is_dir( $path.$file."/" ) ){
                $list = array_merge( $list , get_all_files( $path.$file."/" ) );
            }
            else
            {
                $list[] = $path.$file;
            }
    }
    @closedir($dir);
    return $list;
}
function get_order_item_name($id)
{
    return M("DealOrderItem")->where("id=".$id)->getField("name");
}
function get_supplier_name($id)
{
    return M("Supplier")->where("id=".$id)->getField("name");
}

function get_send_type_msg($status)
{
    if($status==0)
    {
        return l("SMS_SEND");
    }
    else if($status==2)
    {
        return l("ADVISER_SEND");
    }
    else
    {
        return l("MAIL_SEND");
    }
}
function show_content($content,$id)
{
    return "<a title='".l("VIEW")."' href='javascript:void(0);' onclick='show_content(".$id.")'>".l("VIEW")."</a>";
}



function get_is_send($is_send)
{
    if($is_send==0)
        return L("NO");
    else
        return L("YES");
}
function get_send_result($result)
{
    if($result==0)
    {
        return L("FAILED");
    }
    else
    {
        return L("SUCCESS");
    }
}

function get_is_buy($is_buy)
{
    return l("IS_BUY_".$is_buy);
}

function get_point($point)
{
    return l("MESSAGE_POINT_".$point);
}

function get_status($status)
{
    if($status)
    {
        return l("YES");
    }
    else
        return l("NO");
}


function getMPageName($page)
{
    return L('MPAGE_'.strtoupper($page));
}

function getMTypeName($type)
{
    return L('MTYPE_'.strtoupper($type));
}

function get_submit_user($uid)
{
    if($uid==0)
        return "管理员发布";
    else
    {
        $uname = M("SupplierAccount")->where("id=".$uid)->getField("account_name");
        return $uname?$uname:"商家不存在";
    }

}

function get_event_cate_name($id)
{
    return M("EventCate")->where("id=".$id)->getField("name");
}


/**
 * 通过curl方式获取远程的数据
 * @param string $url @notice--查询字符串--请求远程地址 GET方式请求的数据必须把相关数据封装成数组
 * @param array  $params 请求时所需要的参数
 * @param string $method 请求的方法
 * @return 根据$json array string
 */
function http_request($url, $params=array(), $method='GET',$json=TRUE)
{
    $ch = curl_init();
    $method = strtoupper($method);
    switch ($method)
    {
    case 'GET':
        if(strpos($url, '?'))
        {
            $url = rtrim($url,'&');
            $url .= '&'.http_build_query($params, '', '&');
        }
        else
        {
            $url .= '?'.http_build_query($params, '', '&');
        }
        curl_setopt($ch, CURLOPT_URL, $url);
    case 'POST':
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        break;
    default:
        break;
    }
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch,CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);
    if($json)
    {
        return json_decode($content, true);
    }
    else
    {
        return $content;
    }
}

/**
 * 获得oauth端user_name的用户信息
 * @author wenyanlei  2013-8-2
 * @param $user_name 用户名
 * @param $url oauth端api接口地址
 * @param $key 加密key
 * @param $type 加密类型
 * @return bool/array
 */
function get_userinfo_in_oauth($user_name,$url = '',$key = '5oiR55qE5ZCN5a2X5piv77',$type = 'MCRYPT_DES')
{
    require_once APP_ROOT_PATH.'system/libs/CryptRc4.php';

    $url = empty($url) ? $GLOBALS['sys_config']['USERINFO_OAUTH_API_URL'] : '';

    if(empty($url) || empty($user_name))    return false;

    $rc4 = new CryptRc4($key);
    $str_encrypt = $rc4->encrypt($user_name);

    $user_info = http_request($url, array('data' => $str_encrypt), 'GET', false);

    if(empty($user_info))    return false;

    $user_info = $rc4->decrypt($user_info);

    if(empty($user_info))    return false;

    $user_arr = json_decode($user_info, true);

    return $user_arr;
}
/**
 * 根据渠道id获取　渠道值
 * @param int $id
 * @param string $field
 * @return return
 */
function get_deal_channel_value($id,$field="*"){
    if(!$id){
        return false;
    }
    if($field == "*"){
        $value = M("DealChannel")->where("id=".$id)->find();
    }else{
        $value = M("DealChannel")->where("id=".$id)->getField($field);
    }
    return $value;
}

/**
 * 把oauth端存在的用户名注册到本地user表
 * @author wenyanlei  2013-8-5
 * @param $regist_info 用户信息数组
 * @return int/bool
 */
function register_user_in_oauth($regist_info){
    //把oauth的数据同步到firstp2p用户表
    $regarr['user_name'] = $regist_info['user_login_name'];
    $regarr['email'] = $regist_info['user_email'];
    $regarr['mobile'] = $regist_info['user_name'];
    $regarr['real_name'] = $regist_info['user_nickname'];
    if($regist_info['user_sex'] == 1)  $regarr['sex'] = 0;
    if($regist_info['user_sex'] == 0)  $regarr['sex'] = 1;
    //$regarr['idno'] = $regist_info['user_idcard'];
    $regarr['oauth'] = 1;

    $user_id = 0;
    require_once APP_ROOT_PATH."system/libs/user.php";
    $res = save_user($regarr);
    //var_dump($res);
    //如果返回值错误则查询本地库里的记录
    if(is_array($res['data'])){
        $user_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where user_name='".$regarr['user_name']."' and is_delete = 0");
    }else{
        $user_id = $res['data'];
    }

    if(!$user_id) return false;
    return $user_id;
}

/**
 * 批量导入借款时 保证人处理
 *
 * @param $deal_id 订单ID
 * @param $guarantor_arr 保证人信息
 * @param $is_agree 是否同意担保
 * @return NULL/BOOL/INT
 * @author wenyanlei 2013/08/06
 */
function upload_set_guarantor($deal_id = NULL, $user_id = NULL, $guarantor_arr, $is_agree = false) {

    if (empty ( $deal_id ) || empty($user_id) || empty($guarantor_arr))    return false;
    $guarantor_total = count ( $guarantor_arr ['name'] );

    // 插入贷款担保人数据入库
    for( $i = 0; $i < $guarantor_total; $i ++ ) {
        $guarantor_data = array (
            'deal_id' => $deal_id,
            'name' => $guarantor_arr ['name'] [$i],
            'email' => $guarantor_arr ['email'] [$i],
            'mobile' => $guarantor_arr ['mobile'] [$i],
            'relationship' => $guarantor_arr ['relation'] [$i],
            'create_time' => get_gmtime(),
            'status' => 0,
            'user_id' => $user_id
        );

        if ($is_agree === true) {
            $guarantor_data ['to_user_id'] = $guarantor_arr ['to_user_id'] [$i];
            $guarantor_data ['status'] = 2;
            $guarantor_data ['active_time'] = get_gmtime();
        }

        $GLOBALS ['db']->autoExecute ( DB_PREFIX . "deal_guarantor", $guarantor_data, "INSERT", '' );
    }
}

/**
 * 获取短信通道类型显示文本
 *
 * @return void
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
function get_sms_channel_type($type)
{
    return L("SMS_CHANNEL_TYPE_$type");
}


/**
 * 获取某个deal的购买状态
 * @param unknown $tag
 * @param unknown $deal_id
 * @return string
 * @author zhanglei5@ucfgroup.com
 */
function get_buy_status($tag,$deal_id) {
    $deal_status = M("Deal")->where("id=".$deal_id)->getField("deal_status");
    if($deal_status == 1) {
        return '是';
    }else {
        return '否';
    }
}

function get_push_send_status($status) {
    return L('PUSH_SEND_STATUS_'.$status);
}

/**
 * 获取用户提现的支付状态
 *
 * @param int $status
 * @return string
 */
function get_withdraw_status($status)
{
    return core\dao\UserCarryModel::$withdrawDesc[$status];
}

/**
 * 获取用户绑定银行卡信息
 *
 * @param int $user_id
 * @param string $field
 * @return string
 */
function get_user_bank_info($user_id, $field = '')
{
    $info = BankService::getNewCardByUserId($user_id);
    $bankId = intval($info['bank_id']);
    $info['bankName'] = '';
    if ($bankId != 0) {
        $bankData = BankService::getBankInfoByBankId($bankId);
        $info['bankName'] = !empty($bankData['name']) ? $bankData['name'] : '';
    }

    return empty($field) ? $info : (empty($info) ? '' : $info[$field]);
}

/**
 * 获取警告信息
 *
 * @param intger $warningStat
 * @access public
 * @return string
 */
function getWarningInfo($warningStat, $split = "<br>", $moneyLimit) {

    $userCarryService = new \core\service\UserCarryService();
    return $userCarryService->getWarningInfo($warningStat, $split, $moneyLimit);
}

function getListUser($id, $text) {
    return "<a href='".u("User/index",array('id'=>$id))."' target='_blank'>".$text."</a>";
}

/**
 * 系统日志中log过长处理，增加显示全部模态窗口
 * @param  [type] $string [description]
 * @return [type]         [description]
 */
function short($string) {
    if (mb_strlen($string) < 200) return "<div class='strnormal'>" . $string . "</div>";
    $cellHtml = "<a id='go' rel='leanModal' name='showDetail' href='#showDetail' onclick=showAll(this)>显示全部</a>";
    $cellHtml .= "<div class='str2long'>";
    $cellHtml .= $string;
    $cellHtml .= "</div>";
    return $cellHtml;
}

/**
 * 获取企业用户的账户用途
 * @param int $userId
 */
function getUserPurpose($userId, $userType = 0)
{
    // 获取用户信息
    if ($userType == 0) {
        $userInfo = UserService::getUserById($userId);
        // 账户类型
        if (strlen($userInfo['user_purpose']) > 0 && !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE'][(int)$userInfo['user_purpose']]['bizName']))
        {
            return $GLOBALS['dict']['ENTERPRISE_PURPOSE'][(int)$userInfo['user_purpose']]['bizName'];
        }
    }else{
        $enterpriseBaseInfo = UserService::getEnterpriseInfo($userId);
        // 账户类型
        if (strlen($enterpriseBaseInfo['company_purpose']) > 0 && !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE'][(int)$enterpriseBaseInfo['company_purpose']]['bizName']))
        {
            return $GLOBALS['dict']['ENTERPRISE_PURPOSE'][(int)$enterpriseBaseInfo['company_purpose']]['bizName'];
        }
    }
    return '';
}
