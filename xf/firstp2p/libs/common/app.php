<?php



//[CODE_BLOCK_START][app/Lib/common.php]

use app\models\service\ContractType;
// for gearman
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\UserMsgEvent;

//app项目用到的函数库

/**
 * 获取页面的标题，关键词与描述
 */
function get_site_info()
{
    $shop_info['SHOP_TITLE']    =    app_conf('SHOP_TITLE');
    $shop_info['SHOP_KEYWORD']    =    app_conf('SHOP_KEYWORD');
    $shop_info['SHOP_DESCRIPTION']    =    app_conf('SHOP_DESCRIPTION');

    return $shop_info;
}

/**
 * 获取导航菜单
 */
function format_nav_list($nav_list)
{
        foreach($nav_list as $k=>$v)
        {
            if($v['url']!='')
            {
                if(substr($v['url'],0,7)!="http://")
                {
                    //开始分析url
                    $nav_list[$k]['url'] = APP_ROOT."/".$v['url'];
                }
            }
            else
            {
                $r = preg_match("/id=(\d+)/i",$v['u_param'],$matches);
                if ($r) {
                    $id = intval($matches[1]);
                    if($v['u_module']=='article'&&$id>0)
                    {
                        $article = get_article($id);
                        if($article['type_id']==1)
                        {
                            $nav_list[$k]['u_module'] = "help";
                        }
                        elseif($article['type_id']==2)
                        {
                            $nav_list[$k]['u_module'] = "notice";
                        }
                        elseif($article['type_id']==3)
                        {
                            $nav_list[$k]['u_module'] = "sys";
                        }
                        else
                        {
                            $nav_list[$k]['u_module'] = 'article';
                        }
                    }
                }
            }
        }
        return $nav_list;
}
function get_nav_list()
{
    $list = load_auto_cache("cache_nav_list");
    if(!is_duotou_inner_user()) {//不是多投内部用户
        foreach ($list as $key => $val) {
            if(trim($val['name']) == '智多新') {
                unset($list[$key]);
            }
        }
    }
    return $list;
}

function init_nav_list($nav_list, $isroot)
{
    $u_param = "";
    foreach($_GET as $k=>$v)
    {
        if(strtolower($k)!="ctl"&&strtolower($k)!="act"&&strtolower($k)!="city")
        {
            $u_param.=$k."=".$v."&";
        }
    }
    if(substr($u_param,-1,1)=='&')
    $u_param = substr($u_param,0,-1);
    foreach($nav_list as $k=>$v)
    {
        if($v['url']=='')
        {
            $route = $v['u_module'];
            if($v['u_action']!='')
            $route.="#".$v['u_action'];

            $app_index = $v['app_index'];

            if($v['u_module']=='index')
            {
                $route="index";
                $v['u_module'] = "index";
            }

            if($v['u_action']=='')
                $v["u_action"] = "index";

            $str = "u:".$app_index."|".$route."|".$v['u_param'];
            $nav_list[$k]['url'] =  parse_url_tag($str);

            if ($isroot) {
                if($v['u_module']=='deals' && MODULE_NAME=='tool') {
                    $nav_list[$k]['current'] = 1;
                } elseif (MODULE_NAME==$v['u_module']) {
                    $nav_list[$k]['current'] = 1;
                } elseif ($v['u_module'] == 'uc_center' && strpos(MODULE_NAME, "uc_") === 0) {
                    // HACK(jiankangzhang): 现在UCCENTER下面有很多tab不在nav的config里面，现在将uc_开头的module归属到uc的tab下面.
                    $nav_list[$k]['current'] = 1;
                }
            } else {
                if ($v['u_module'] == 'help') {
                    if (MODULE_NAME == 'help') {
                        $menu_id = substr($v['u_param'], strpos($v['u_param'], '=') + 1);
                        if (in_array($menu_id, array('9', '12', '27'))) {
                            if ($menu_id == $_REQUEST['id']) {
                                $nav_list[$k]['current'] = 1;
                            }
                        } else {
                            if (!in_array($_REQUEST['id'], array('9', '12', '27'))) {
                                $nav_list[$k]['current'] = 1;
                            }
                        }
                    }
                } else {
                    if (ACTION_NAME==$v['u_action']&&MODULE_NAME==$v['u_module']) {
                        $nav_list[$k]['current'] = 1;
                    }
                }
            }
        }
    }
    return $nav_list;
}

function get_help()
{
    return load_auto_cache("get_help_cache");
}



//获取所有子集的类
class ChildIds
{
    public function __construct($tb_name)
    {
        $this->tb_name = $tb_name;
    }
    private $tb_name;
    private $childIds;
    private function _getChildIds($pid = '0', $pk_str='id' , $pid_str ='pid')
    {
        $childItem_arr = $GLOBALS['db']->getAll("select id from ".DB_PREFIX.$this->tb_name." where ".$pid_str."=".intval($pid));
        if($childItem_arr)
        {
            foreach($childItem_arr as $childItem)
            {
                $this->childIds[] = $childItem[$pk_str];
                $this->_getChildIds($childItem[$pk_str],$pk_str,$pid_str);
            }
        }
    }
    public function getChildIds($pid = '0', $pk_str='id' , $pid_str ='pid')
    {
        $this->childIds = array();
        $this->_getChildIds($pid,$pk_str,$pid_str);
        return $this->childIds;
    }
}

/**
 * 显示错误
 *
 * @param $msg 消息内容
 * @param int $ajax
 * @param string $jump 调整链接
 * @param int $stay 是否停留不跳转
 * @param int $time 跳转等待时间
 */
function showErr($msg,$ajax=0,$jump='',$stay=0,$refresh_time=3)
{
    if($ajax==1)
    {
        $result['status'] = 0;
        $result['info'] = $msg;
        $result['jump'] = $jump;
        $result['refresh_time'] = $refresh_time;
        header("Content-type: application/json; charset=utf-8");
        echo(json_encode($result));
    }
    else
    {
        $GLOBALS['tmpl']->assign('page_title',$GLOBALS['lang']['ERROR_TITLE']." - ".$msg);
        $GLOBALS['tmpl']->assign('msg',$msg);

        $GLOBALS['tmpl']->assign('error_title',$GLOBALS['lang']['ERROR_TITLE']);
        $GLOBALS['tmpl']->assign('error_msg',$msg);


        if($jump==''){
            $jump = $_SERVER['HTTP_REFERER'];
        }
        if(!$jump&&$jump==''){
            $jump = APP_ROOT."/";
        }

        $GLOBALS['tmpl']->assign('jump',$jump);
        $GLOBALS['tmpl']->assign("stay",$stay);
        $GLOBALS['tmpl']->assign("refresh_time",$refresh_time);
        $GLOBALS['tmpl']->display("error.html");
        $trace_obj = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2);
        $trace_obj = $trace_obj[1];
        if($trace_obj && isset($trace_obj['object'])){
            $trace_obj['object']->template = null;
        }
    }
    return false;
}

//显示提示
function showTip($msg,$ajax=0,$jump='/account',$stay=0, $data=array(),$refresh_time=3)
{
    if($ajax==1)
    {
        $result['status'] = 1;
        $result['info'] = $msg;
        $result['jump'] = $jump;
        if(!empty($data)){
            $result['data'] = $data;
        }
        header("Content-Type:text/html; charset=utf-8");
        echo(json_encode($result));
    }
    else
    {
        $GLOBALS['tmpl']->assign('page_title',$GLOBALS['lang']['SUCCESS_TITLE']." - ".$msg);
        $GLOBALS['tmpl']->assign('msg',$msg);

        if($jump==''){
            $jump = $_SERVER['HTTP_REFERER'];
        }
        if(!$jump&&$jump==''){
            $jump = APP_ROOT."/";
        }

        $GLOBALS['tmpl']->assign('jump',$jump);
        $GLOBALS['tmpl']->assign("stay",$stay);
        $GLOBALS['tmpl']->assign("refresh_time",$refresh_time);
        $GLOBALS['tmpl']->display("being.html");

    }
    return false;
}

//显示成功
function showSuccess($msg,$ajax=0,$jump='',$stay=0, $data=array(),$refresh_time=3)
{
    if($ajax==1)
    {
        $result['status'] = 1;
        $result['info'] = $msg;
        $result['jump'] = $jump;
        if(!empty($data)){
            $result['data'] = $data;
        }
        header("Content-Type:text/html; charset=utf-8");
        echo(json_encode($result));
    }
    else
    {
        $GLOBALS['tmpl']->assign('page_title',$GLOBALS['lang']['SUCCESS_TITLE']." - ".$msg);
        $GLOBALS['tmpl']->assign('msg',$msg);

        if($jump==''){
            $jump = $_SERVER['HTTP_REFERER'];
        }
        if(!$jump&&$jump==''){
            $jump = APP_ROOT."/";
        }

        $GLOBALS['tmpl']->assign('jump',$jump);
        $GLOBALS['tmpl']->assign("stay",$stay);
        $GLOBALS['tmpl']->assign("refresh_time",$refresh_time);
        $GLOBALS['tmpl']->display("success.html");
        $trace_obj = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2);
        $trace_obj = $trace_obj[1];
        if($trace_obj && isset($trace_obj['object'])){
            $trace_obj['object']->template = null;
        }
    }
    return false;
}

/**
 * 获取用户名
 */
if(!function_exists("get_user_name")){
    function get_user_name($id,$show_tag=true)
    {
        if(intval($id)<=0){
            return "";
        }
        $key = md5("USER_NAME_LINK_".$id."_".$show_tag);
        if(isset($GLOBALS[$key]))
        {
            return $GLOBALS[$key];
        }
        else
        {
            $uname = load_dynamic_cache($key);
            if($uname===false)
            {
                $u = $GLOBALS['db']->getRow("select id,user_name from ".DB_PREFIX."user where id = ".intval($id));
                if($show_tag){
                    $u['user_name'] = get_deal_username($u['id']);
                    $uname = "<a href='".url("index","space",array("id"=>$id))."'  class='user_name'  onmouseover='userCard.load(this,".$u['id'].");' >".$u['user_name']."</a>";
                }else {
                    //$uname = $u['user_name'];
                    $uname = get_deal_username($u['id']);
                }
                set_dynamic_cache($key,$uname);
            }
            $GLOBALS[$key] = $uname;
            return $GLOBALS[$key];
        }
    }
}

/**
 *
 * 获取投标的用户名称
 * @author Liwei
 * @date Jul 2, 2013 11:02:10 AM
 *
 */
function get_deal_username($user_id){
    if (empty($user_id)){
        return false;
    }
    $user_info = $GLOBALS['db']->getRow("SELECT real_name,sex,idno,user_type FROM ".DB_PREFIX."user WHERE id = ".intval($user_id));

    // 这里影响了通知贷跟普通标-投资记录里面的投资人姓名(DealModel->updatePercent) Edit By guofeng 20160118 18:45
    if (isset($user_info['user_type']) && (int)$user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
        return 'XX公司';
    }

    $sex = $user_info['sex'];
    $real_name = trim($user_info['real_name']);

    if($sex == -1){
        $sexnum = -1;
        if(strlen($user_info['idno']) == 15){
            $sexnum = substr($user_info['idno'], -1);
        }elseif(strlen($user_info['idno']) == 18){
            $sexnum = substr($user_info['idno'], -2, 1);
        }
        if($sexnum > 0){
            $sex = $sexnum % 2 ? 1 : 0;
        }
    }

    $user_sex_name = $GLOBALS['dict']['USER_SEX'][$sex];

    //先取第一串英文字母，取不到的话，按中文截取
    if(preg_match('/^[a-zA-Z0-9]+/', $real_name, $out)){
        $pre_name = ($out[0] == $real_name) ? substr_replace($real_name, '******', 1, -1) : $out[0];
    }else{
        $pre_name = mb_substr($real_name, 0, 1, 'utf-8');
    }
    return $pre_name.$user_sex_name;
}

/**
 * 获取用户相应字段内容
 */
function get_user($extfield,$uid){
    /* $key = md5("USER_FILED_INFO_".$extfield.$uid);
    if(isset($GLOBALS[$key]))
    {
        return $GLOBALS[$key];
    }
    else
    {
        $user = load_dynamic_cache($key);
        if($user===false)
        { */
            $user = $GLOBALS['db']->getRow("select $extfield from ".DB_PREFIX."user where id = ".intval($uid));
            if($user){
                $user['point_level'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."user_level where id = ".intval($user['level_id']));
                $user['url'] = url("index","space",array("id"=>$uid));
                if($user['city_id'])
                    $user['region'] = $user['region_city'] = $GLOBALS['db']->getOne("select name from  ".DB_PREFIX."region_conf where id = ".intval($user['city_id']));

                if($user['province_id']){
                    $user['region_province'] = $GLOBALS['db']->getOne("select name from  ".DB_PREFIX."region_conf where id = ".intval($user['province_id']));
                    if(!$user['region'])
                        $user['region'] = $user['region_province'];
                }
                if($user['id']){
                    $work_info = $GLOBALS['db']->getRow("select * from  ".DB_PREFIX."user_work where user_id = ".intval($user['id']));
                    $user['workinfo'] = $work_info;
                    $user['work_province'] = $GLOBALS['db']->getOne("select name from  ".DB_PREFIX."region_conf where id = ".intval($work_info['province_id']));
                    $user['work_city'] = $GLOBALS['db']->getOne("select name from  ".DB_PREFIX."region_conf where id = ".intval($work_info['city_id']));
                }

            }
            return $user;
            /* set_dynamic_cache($key,$user);
        }
        $GLOBALS[$key] = $user;
        return $GLOBALS[$key];
    } */
}

/**
 * 获取用户所上传的严重材料
 */
function get_user_credit_file($uid){
    $key = md5("USER_CREDIT_FILE_".$uid);
    if(isset($GLOBALS[$key]))
    {
        return $GLOBALS[$key];
    }
    else
    {
        $user_credit_file = load_dynamic_cache($key);
        if($user_credit_file===false)
        {
            $t_user_credit_file = $GLOBALS['db']->get_slave()->getAll("select * from ".DB_PREFIX."user_credit_file where user_id = ".intval($uid));
            foreach($t_user_credit_file as $k=>$v){
                $file_list = array();
                if($v['file'])
                    $file_list = unserialize($v['file']);

                if(is_array($file_list))
                    $v['file_list']= $file_list;

                $user_credit_file[$v['type']] = $v;
            }
            set_dynamic_cache($key,$user_credit_file);
        }
        $GLOBALS[$key] = $user_credit_file;
        return $GLOBALS[$key];
    }
}

/**
 * 获取用户的信息绑定资料
 */

function get_user_msg_conf($id){
    $key = md5("USER_MSG_CONF_".$id);
    if(isset($GLOBALS[$key]))
    {
        return $GLOBALS[$key];
    }
    else
    {
        $conf = load_dynamic_cache($key);
        if($conf===false)
        {
            $conf = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_conf where user_id = ".intval($id));
            set_dynamic_cache($key,$conf);
        }
        $GLOBALS[$key] = $conf;
        return $GLOBALS[$key];
    }
}

function get_message_rel_data($message,$field='name')
{
    return $GLOBALS['db']->getOne("select ".$field." from ".DB_PREFIX.$message['rel_table']." where id = ".intval($message['rel_id']));
}

function get_order_item_list($order_id)
{
    $deal_order_item = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_order_item where order_id = ".$order_id);
    $str = '';
    foreach($deal_order_item as $k=>$v)
    {
        $str .="<br /><span title='".$v['name']."'>".msubstr($v['name'])."</span>[".$v['number']."]";
    }
    return $str;
}

//用于获取可同步登录的API
function get_api_login()
{
    if(trim($_REQUEST['act'])!='api_login')
    {
        $apis = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."api_login");
        $str = "<div class='blank'></div>";
        foreach($apis as $k=>$api)
        {
            $str .= $url."<span id='api_".$api['class_name']."_0'><script type='text/javascript'>load_api_url('".$api['class_name']."',0);</script></span>";
        }
        return $str;
    }
    else
    return '';
}

//获取已过时间
function pass_date($time)
{
        $time_span = get_gmtime() - $time;
        if($time_span>3600*24*365)
        {
            //一年以前
//            $time_span_lang = round($time_span/(3600*24*365)).$GLOBALS['lang']['SUPPLIER_YEAR'];
            //$time_span_lang = to_date($time,"Y".$GLOBALS['lang']['SUPPLIER_YEAR']."m".$GLOBALS['lang']['SUPPLIER_MON']."d".$GLOBALS['lang']['SUPPLIER_DAY']);
            $time_span_lang = to_date($time,"Y-m-d");
        }
        elseif($time_span>3600*24*30)
        {
            //一月
//            $time_span_lang = round($time_span/(3600*24*30)).$GLOBALS['lang']['SUPPLIER_MON'];
            //$time_span_lang = to_date($time,"Y".$GLOBALS['lang']['SUPPLIER_YEAR']."m".$GLOBALS['lang']['SUPPLIER_MON']."d".$GLOBALS['lang']['SUPPLIER_DAY']);
            $time_span_lang = to_date($time,"Y-m-d");
        }
        elseif($time_span>3600*24)
        {
            //一天
            //$time_span_lang = round($time_span/(3600*24)).$GLOBALS['lang']['SUPPLIER_DAY'];
            $time_span_lang = to_date($time,"Y-m-d");
        }
        elseif($time_span>3600)
        {
            //一小时
            $time_span_lang = round($time_span/(3600)).$GLOBALS['lang']['SUPPLIER_HOUR'];
        }
        elseif($time_span>60)
        {
            //一分
            $time_span_lang = round($time_span/(60)).$GLOBALS['lang']['SUPPLIER_MIN'];
        }
        else
        {
            //一秒
            $time_span_lang = $time_span.$GLOBALS['lang']['SUPPLIER_SEC'];
        }
        return $time_span_lang;
}

/**
* 获取用户对应的借款公司信息
* @author wenyanlei  2013-11-7
* @param $user_id 用户id
* @return int/bool/object/array
*/
function get_user_company_info($user_id){

    $user_id = intval($user_id);
    $company_info = array();

    if($user_id > 0){
        $company_sql = "select * from ".DB_PREFIX."user_company where is_effect = 1 and is_delete = 0 and user_id = ".$user_id;
        $company_info = $GLOBALS['db']->getRow($company_sql);
    }

    return $company_info;
}

/**
* 根据标的合同类型 获取借款人的个人或公司信息
* @author wenyanlei 2013-11-7
* @param $deal 借款信息
* @return array
*/
function get_deal_borrow_info($deal){

    if(empty($deal)){
        return false;
    }

    $tpl_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_category where type_tag = '".$deal['contract_tpl_type']."'");

    $return_info = array();
    $return_info['is_company'] = 0;

    //个人借款
    $user_info = get_user_info($deal['user_id'],true);
    if($user_info){
        $return_info['show_name'] = $user_info['user_name'];
        //$return_info = array_merge($return_info,$user_info);
        //合同变量
        $return_info['borrow_real_name'] = $user_info['real_name'];//真实姓名
        $return_info['borrow_user_name'] = $user_info['user_name'];//用户名
        $return_info['borrow_user_idno'] = $user_info['idno'];//身份证
        $return_info['borrow_address'] = $user_info['address'];//地址
        $return_info['borrow_mobile'] = $user_info['mobile'];//手机
        $return_info['borrow_postcode'] = $user_info['email'];//邮箱 （历史错误）
        $return_info['borrow_email'] = $user_info['email'];//邮箱
        $return_info['real_name'] = $user_info['real_name'];//邮
    }

    if(!empty($tpl_info) && $tpl_info['contract_type'] == ContractType::TYPE_COMPANY){
        //公司借款
        $company_info = get_user_company_info($deal['user_id']);

        $return_info['is_company'] = 1;
        $return_info['show_name'] = isset($company_info['name']) ? $company_info['name'] : '';
        $return_info['company_name'] = isset($company_info['name']) ? $company_info['name'] : '';//名称
        $return_info['company_address'] = isset($company_info['address']) ? $company_info['address'] : '';//注册地址
        $return_info['company_legal_person'] = isset($company_info['legal_person']) ? $company_info['legal_person'] : '';//法定代表人
        $return_info['company_tel'] = isset($company_info['tel']) ? $company_info['tel'] : '';//联系电话
        $return_info['company_license'] = isset($company_info['license']) ? $company_info['license'] : '';//营业执照号
        $return_info['company_description'] = isset($company_info['description']) ? $company_info['description'] : '';//简介
        $return_info['company_address_current'] = isset($company_info['domicile']) ? $company_info['domicile'] : ''; //借款公司住所地
    }

    $return_info['user_id'] = $deal['user_id'];
    return $return_info;
}



//获取用户的可用额度
function get_can_use_quota($uid){
    return 100000000;
    /*
    if(empty($uid)) return false;
    //用户的总额度
    $quota = $GLOBALS['db']->getOne("select quota from ".DB_PREFIX."user where id = ".$uid);
    //获取用户借款用去的额度
    $borrow_quota = $GLOBALS['db']->getOne("select sum(borrow_amount) from ".DB_PREFIX."deal where is_delete=0 AND publish_wait=0 AND deal_status in(0,1,2,4) AND user_id = ".$uid);
    return ($quota-$borrow_quota);*/
}

// $type = middle,big,small

function show_avatar($u_id,$type="middle")
{
    $key = md5("AVATAR_".$u_id.$type);
    if(isset($GLOBALS[$key]))
    {
        return $GLOBALS[$key];
    }
    else
    {
        $avatar_key = md5("USER_AVATAR_".$u_id);
        $avatar_data = $GLOBALS['dynamic_avatar_cache'][$avatar_key];// 当前用户所有头像的动态缓存
        if(!isset($avatar_data)||!isset($avatar_data[$key]))
        {
            $avatar_file = get_user_avatar($u_id,$type);
            $avatar_str = "<a href='".url("index","space",array("id"=>$u_id))."' style='text-align:center; display:inline-block;'>".
                   "<img src='".$avatar_file."'  />".
                   "</a>";
            $avatar_data[$key] = $avatar_str;
            if(count($GLOBALS['dynamic_avatar_cache'])<500) //保存500个用户头像缓存
            {
                $GLOBALS['dynamic_avatar_cache'][$avatar_key] = $avatar_data;
            }
        }
        else
        {
            $avatar_str = $avatar_data[$key];
        }
        $GLOBALS[$key]= $avatar_str;
        return $GLOBALS[$key];
    }
}

function update_avatar($u_id)
{
    $avatar_key = md5("USER_AVATAR_".$u_id);
    unset($GLOBALS['dynamic_avatar_cache'][$avatar_key]);
    $GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/avatar_cache/");
    $GLOBALS['fcache']->set("AVATAR_DYNAMIC_CACHE",$GLOBALS['dynamic_avatar_cache']); //头像的动态缓存
}

//获取用户头像的文件名
function get_user_avatar($id,$type)
{
    $uid = sprintf("%09d", $id);
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    $path = $dir1.'/'.$dir2.'/'.$dir3;

    $id = str_pad($id, 2, "0", STR_PAD_LEFT);
    $id = substr($id,-2);
    $avatar_file = APP_ROOT."/avatar/".$path."/".$id."virtual_avatar_".$type.".jpg";
    $avatar_check_file = APP_ROOT_PATH."public/avatar/".$path."/".$id."virtual_avatar_".$type.".jpg";
    if(file_exists($avatar_check_file))
    return $avatar_file;
    else
    return APP_ROOT."/avatar/noavatar_".$type.".gif";
    //@file_put_contents($avatar_check_file,@file_get_contents(APP_ROOT_PATH."public/avatar/noavatar_".$type.".gif"));
}


function check_user_avatar($id,$type)
{
    $uid = sprintf("%09d", $id);
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    $path = $dir1.'/'.$dir2.'/'.$dir3;

    $id = str_pad($id, 2, "0", STR_PAD_LEFT);
    $id = substr($id,-2);
    $avatar_file = APP_ROOT."/avatar/".$path."/".$id."virtual_avatar_".$type.".jpg";
    $avatar_check_file = APP_ROOT_PATH."public/avatar/".$path."/".$id."virtual_avatar_".$type.".jpg";
    if(file_exists($avatar_check_file))
        return $avatar_file;
    else
        return false;
}

//添加一则日志
/**
 * @param $type            转发的类型标识    见下代码中的范围
 * @param $relay_id        转发的主题ID
 * @param $fav_id        喜欢主题的ID
 */
function insert_topic($type='', $fav_id = 0,$user_id = 0,$user_name = '',$l_user_id = 0)
{
    //定义类型的范围
    $type_array = array(
        "focus", //分享
        "message", //留言
        "message_reply",//回复
        "deal_collect",//关注FAV
    );
    if(!in_array($type,$type_array))
        return ;

    if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."topic WHERE `type`='$type' AND fav_id='$fav_id' AND user_id='$user_id' AND l_user_id='$l_user_id' ")==0){
        //添加
        $data['type'] = $type;
        $data['fav_id'] = $fav_id;
        $data['user_id'] = $user_id;
        $data['user_name'] = $user_name;
        $data['l_user_id'] = $l_user_id;
        $data['is_effect'] = 1;
        $data['create_time'] = get_gmtime();
        $GLOBALS['db']->autoExecute(DB_PREFIX."topic",$data,"INSERT");
    }
}

function get_topic_list($limit,$condition='',$orderby='create_time desc',$keywords_array=array())
{
    if($orderby=='')$orderby='create_time desc';
    if($condition!='')
    $condition = " and ".$condition;
    $list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."topic where is_effect = 1 ".$condition." order by ".$orderby." limit ".$limit);
    $total = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."topic where is_effect = 1  ".$condition);
    foreach($list as $k=>$v){
        if($v['type']=="message" || $v['type']=="message_reply" || $v['type']=="deal_collect"|| $v['type']=="deal_bad"){
            FP::import('app.deal');
            $list[$k]['deal'] = get_deal($v['fav_id']);
        }
        $list[$k]['user_name'] = get_deal_username($v['user_id']);
    }
    return array('list'=>$list,'total'=>$total);
}


//获取相应规格的图片地址
//gen=0:保持比例缩放，不剪裁,如高为0，则保证宽度按比例缩放  gen=1：保证长宽，剪裁
function get_spec_image($img_path,$width=0,$height=0,$gen=0,$is_preview=true)
{
    if($width==0)
        $new_path = $img_path;
    else
    {
        $img_name = substr($img_path,0,-4);
        $img_ext = substr($img_path,-3);
        if($is_preview)
        $new_path = $img_name."_".$width."x".$height.".jpg";
        else
        $new_path = $img_name."o_".$width."x".$height.".jpg";
        if(!file_exists(APP_ROOT_PATH.$new_path))
        {
            FP::import('libs.utils.es_imagecls');
            $imagec = new es_imagecls();
            $thumb = $imagec->thumb(APP_ROOT_PATH.$img_path,$width,$height,$gen,true,"",$is_preview);

            if(app_conf("PUBLIC_DOMAIN_ROOT")!='')
            {
                $paths = pathinfo($new_path);
                $path = str_replace("./","",$paths['dirname']);
                $filename = $paths['basename'];
                $pathwithoupublic = str_replace("public/","",$path);
                $syn_url = app_conf("PUBLIC_DOMAIN_ROOT")."/es_file.php?username=".app_conf("IMAGE_USERNAME")."&password=".app_conf("IMAGE_PASSWORD")."&file=".get_domain().APP_ROOT."/".$path."/".$filename."&path=".$pathwithoupublic."/&name=".$filename."&act=0";
                @file_get_contents($syn_url);
            }

        }
    }
    return $new_path;
}

function get_spec_gif_anmation($url,$width,$height)
{
    FP::import('libs.utils.gif_encoder');
    FP::import('libs.utils.gif_reader');
    FP::import('libs.utils.es_imagecls');
    $gif = new GIFReader();
    $gif->load($url);
    $imagec = new es_imagecls();
    foreach($gif->IMGS['frames'] as $k=>$img)
    {
        $im = imagecreatefromstring($gif->getgif($k));
        $im = $imagec->make_thumb($im,$img['FrameWidth'],$img['FrameHeight'],"gif",$width,$height,$gen=1);
        ob_start();
        imagegif($im);
        $content = ob_get_contents();
        ob_end_clean();
        $frames [ ] = $content;
           $framed [ ] = $img['frameDelay'];
    }

    $gif_maker = new GIFEncoder (
           $frames,
           $framed,
           0,
           2,
           0, 0, 0,
           "bin"   //bin为二进制   url为地址
      );
    return $gif_maker->GetAnimation ( );
}

function load_comment_list()
{
    return $GLOBALS['tmpl']->fetch("inc/comment_list.html");
}
function load_message_list()
{
    return $GLOBALS['tmpl']->fetch("inc/message_list.html");
}
function load_reply_list()
{
    return $GLOBALS['tmpl']->fetch("inc/topic_page_reply_list.html");
}

//解析URL标签
// $str = u:shop|acate#index|id=10&name=abc
function parse_url_tag($str)
{
    $key = md5("URL_TAG_".$str);
    if(isset($GLOBALS[$key]))
    {
        return $GLOBALS[$key];
    }

    $url = load_dynamic_cache($key);
    if($url!==false)
    {
        $GLOBALS[$key] = $url;
        return $url;
    }
    $str = substr($str,2);
    $str_array = explode("|",$str);
    $app_index = $str_array[0];
    $route = $str_array[1];
    $param_tmp = isset($str_array[2]) ? explode("&",$str_array[2]) : array();
    $param = array();
    foreach($param_tmp as $k=>$item)
    {
        if($item!=''){
            $item_arr = explode("=",$item);
            if($item_arr[0]&&$item_arr[1])
            $param[$item_arr[0]] = $item_arr[1];
        }
    }
    $GLOBALS[$key]= url($app_index,$route,$param);
    set_dynamic_cache($key,$GLOBALS[$key]);
    return $GLOBALS[$key];
}


// 生成 CSS 引入列表
// by weakow

function import_css($urls) {
    $html = '';
    foreach ($urls as $url) {
        $html .= '<link rel="stylesheet" type="text/css" href="/';
        $html .= substr($url, strpos($url, "app/"));
        $html .= '">';
    }
    return $html;
}

//编译生成css文件
function parse_css_old($urls,$is_nocache=0)
{
    $url = md5(implode(',',$urls));
    if (!is_dir(APP_STATIC_PATH."cache")){
        mkdir(APP_STATIC_PATH."cache");
    }
    $css_url = APP_WEB_STATIC."cache/".$url.'.css';
    $url_path = APP_STATIC_PATH."cache/".$url.'.css';
    if(!file_exists($url_path) || $is_nocache == 1)
    {
        $tmpl_path = $GLOBALS['tmpl']->_var['TMPL'];

        $css_content = '';
        foreach($urls as $url)
        {
            $css_content .= @file_get_contents($url);
        }
        $css_content = preg_replace("/[\r\n]/",'',$css_content);
        $css_content = str_replace("../images/",$tmpl_path."/images/",$css_content);
        $css_content = str_replace("../img/",$tmpl_path."/img/",$css_content);
//        @file_put_contents($url_path, unicode_encode($css_content));
        @file_put_contents($url_path, $css_content);
    }
    return get_domain().APP_ROOT."/".ltrim($css_url,"/");
}


function parse_css($urls){
    if (APP_STATIC_OPTION == "NOCACHE"){
        return parse_css_old($urls,1);
    } elseif (APP_STATIC_OPTION == "CACHE"){
        return parse_css_old($urls,0);
    }
    $url_res = str_replace('__RAND__',1,APP_STATIC_PATH_MODEL).app_conf("TEMPLATE").'/pub/';
    $url_md5 = array();
    foreach ($urls as $k=>$url){
        $u_tmp = explode("/", $url);
        $url_md5[] = $u_tmp[count($u_tmp)-1];
    }
    $url_res .= md5(implode(',',$url_md5)).'.css?V='.app_conf('APP_SUB_VER');
    return $url_res;
}

function parse_script($urls,$encode_url=array()){
    if (APP_STATIC_OPTION == "NOCACHE"){
        return parse_script_old($urls,1,$encode_url);
    } elseif (APP_STATIC_OPTION == "CACHE"){
        return parse_script_old($urls,0,$encode_url);
    }
    $url_res = str_replace('__RAND__',1,APP_STATIC_PATH_MODEL).app_conf("TEMPLATE").'/pub/';
    $url_md5 = array();
    foreach ($urls as $k=>$url){
        $u_tmp = explode("/", $url);
        $url_md5[] = $u_tmp[count($u_tmp)-1];
    }
    $url_res .= md5(implode(',',$url_md5)).'.js?V='.app_conf('APP_SUB_VER');
    return $url_res;
}

// 分站样式引用
function parse_css_site($css_file_path) {
    return \SiteApp::init()->asset->makeUrl('skins/' . app_conf('TPL_SITE_DIR') . "/" . $css_file_path);
}

// 分站js引用
function parse_js_site($js_file_path) {
    return \SiteApp::init()->asset->makeUrl('skins/' . app_conf('TPL_SITE_DIR') . "/" . $js_file_path);
}

// 生成 JS 引入列表
// by weakow

function import_js($urls) {
    $html = '';
    foreach ($urls as $url) {
        $html .= '<script src="';
        //$html .= substr($url, strpos($url, "app/"));
        $html .= $url;
        $html .= '"></script>';
    }
    return $html;
}

/**
 *
 * @param $urls 载入的脚本
 * @param $encode_url 需加密的脚本
 */
function parse_script_old($urls,$is_nocache=0,$encode_url=array())
{
    $url = md5(implode(',',$urls));
    if (!is_dir(APP_STATIC_PATH."cache")){
        mkdir(APP_STATIC_PATH."cache");
    }
    $js_url = APP_WEB_STATIC."cache/".$url.'.js';
    $url_path = APP_STATIC_PATH."cache/".$url.'.js';
    if(!file_exists($url_path) || $is_nocache == 1)
    {
        if(count($encode_url)>0)
        {
            FP::import('libs.libs.javascriptpacker');
        }

        $js_content = '';
        foreach($urls as $url)
        {
            $append_content = @file_get_contents($url)."\r\n";
            if(in_array($url,$encode_url))
            {
                $packer = new JavaScriptPacker($append_content);
                $append_content = $packer->pack();
            }
            $js_content .= $append_content;
        }
//        require_once APP_ROOT_PATH."system/libs/javascriptpacker.php";
//        $packer = new JavaScriptPacker($js_content);
//        $js_content = $packer->pack();
        @file_put_contents($url_path,$js_content);
    }
    return get_domain().APP_ROOT."/".ltrim($js_url,"/");
}

//获取商城公告
function get_notice($limit=0)
{
    if($limit == 0)
    $limit = app_conf("INDEX_NOTICE_COUNT");
    if($limit>0)
    {
        $limit_str = "limit ".$limit;
    }
    else
    {
        $limit_str = "";
    }
    $list = $GLOBALS['db']->getAll("select a.*,ac.type_id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where ac.type_id = 2 and ac.is_effect = 1 and ac.is_delete = 0 and a.is_effect = 1 and a.is_delete = 0 order by a.sort desc ".$limit_str);

    foreach($list as $k=>$v)
    {
            if($v['type_id']==1)
            {
                $module = "help";
            }
            elseif($v['type_id']==2)
            {
                $module = "notice";
            }
            elseif($v['type_id']==3)
            {
                $module = "sys";
            }
            else
            {
                $module = 'article';
            }

            if($v['uname']!='')
            $aurl = url("index",$module,array("id"=>$v['uname']));
            else
            $aurl = url("index",$module,array("id"=>$v['id']));
            $list[$k]['url'] = $aurl;
    }
    return $list;
}

function jump_deal($goods,$module)
{

    if($goods['buy_type']==1)
    {
                if($goods['uname']!='')
                $url = url("index","exchange#index",array("id"=>$goods['uname']));
                else
                $url = url("index","exchange#index",array("id"=>$goods['id']));
                if($module!="exchange")
                return app_redirect($url);
    }
    else
    {
        if($goods['is_shop']==0)
        {
                    if($goods['uname']!='')
                    $url = url("index","deal#index",array("id"=>$goods['uname']));
                    else
                    $url = url("index","deal#index",array("id"=>$goods['id']));
                    if($module!="deal")
                    return app_redirect($url);
        }
        if($goods['is_shop']==1)
        {
                    if($goods['uname']!='')
                    $url = url("index","goods",array("id"=>$goods['uname']));
                    else
                    $url = url("index","goods",array("id"=>$goods['id']));
                    if($module!="goods")
                    return app_redirect($url);
        }
        if($goods['is_shop']==2)
        {
                    if($goods['uname']!='')
                    $url = url("store","ydetail",array("id"=>$goods['uname']));
                    else
                    $url = url("store","ydetail",array("id"=>$goods['id']));
                    if($module!="ydetail")
                    return app_redirect($url);
        }
    }
}
/**
 * 获取文章列表
 */
function get_article_list($limit, $cate_id=0, $where='',$orderby = '',$cached = true)
{
        $key = md5("ARTICLE".$limit.$cate_id.$where.$orderby);
        if($cached)
        {
            $res = $GLOBALS['cache']->get($key);
        }
        else
        {
            $res = false;
        }
        if($res===false)
        {

            $count_sql = "select count(*) from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where a.is_effect = 1 and a.is_delete = 0 and ac.is_delete = 0 and ac.is_effect = 1 ";
            $sql = "select a.*,ac.type_id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where a.is_effect = 1 and a.is_delete = 0 and ac.is_delete = 0 and ac.is_effect = 1 ";

            if($cate_id>0)
            {
                $ids = load_auto_cache("deal_shop_acate_belone_ids",array("cate_id"=>$cate_id));
                $sql .= " and a.cate_id in (".implode(",",$ids).")";
                $count_sql .= " and a.cate_id in (".implode(",",$ids).")";
            }


            if($where != '')
            {
                $sql.=" and ".$where;
                $count_sql.=" and ".$where;
            }

            if($orderby=='')
            $sql.=" order by a.sort desc limit ".$limit;
            else
            $sql.=" order by ".$orderby." limit ".$limit;

            $articles = $GLOBALS['db']->getAll($sql);
            foreach($articles as $k=>$v)
            {
                if($v['type_id']==1)
                {
                    $module = "help";
                }
                elseif($v['type_id']==2)
                {
                    $module = "notice";
                }
                elseif($v['type_id']==3)
                {
                    $module = "sys";
                }
                else
                {
                    $module = 'article';
                }

                if($v['uname']!='')
                $aurl = url("index",$module,array("id"=>$v['uname']));
                else
                $aurl = url("index",$module,array("id"=>$v['id']));

                $articles[$k]['url'] = $aurl;
            }
            $articles_count = $GLOBALS['db']->getOne($count_sql);


            $res = array('list'=>$articles,'count'=>$articles_count);
            $GLOBALS['cache']->set($key,$res);
        }
        return $res;
}

function load_page_png($img)
{
    return load_auto_cache("page_image",array("img"=>$img));
}

function get_article($id)
{
    return $GLOBALS['db']->getRow("select a.*,ac.type_id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where a.id = ".intval($id)." and a.is_effect = 1 and a.is_delete = 0");
}
function get_article_buy_uname($uname)
{
    return $GLOBALS['db']->getRow("select a.*,ac.type_id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where a.uname = '".$uname."' and a.is_effect = 1 and a.is_delete = 0");
}
//会员信息发送
/**
 *
 * @param $title 标题
 * @param $content 内容
 * @param $from_user_id 发件人
 * @param $to_user_id 收件人
 * @param $create_time 时间
 * @param $sys_msg_id 系统消息ID
 * @param $only_send true为只发送，生成发件数据，不生成收件数据
 * @param $fav_id 相关ID
 *
 * ----------------------------------------------------
 * 此函数已不建议使用，推荐使用：
 * $msgbox = new MsgBoxService();
 * $msgbox->create($userId, $type, $title, $content);
 * ----------------------------------------------------
 *
 */
function send_user_msg($title,$content,$from_user_id,$to_user_id,$create_time,$sys_msg_id=0,$only_send=false,$is_notice = false,$fav_id = 0)
{
    if (app_conf('MSG_BOX_ENABLE')) {
        $event = new UserMsgEvent($title, $content, $from_user_id, $to_user_id, $create_time, $sys_msg_id, $only_send, $is_notice, $fav_id);
        $obj = new GTaskService();
        $obj->doBackground($event, 3, "NORMAL", null, "domq_message");
    }
    // 以下为旧逻辑，注释保留
    //$group_arr = array($from_user_id,$to_user_id);
    //sort($group_arr);
    //if($sys_msg_id>0){
    //    $group_arr[] = $sys_msg_id;
    //}
    //if($is_notice > 0){
    //    $group_arr[] = $is_notice;
    //}
    //$msg = array();
    //$msg['title'] = $title;
    //$msg['content'] = addslashes($content);
    //$msg['from_user_id'] = $from_user_id;
    //$msg['to_user_id'] = $to_user_id;
    //$msg['create_time'] = $create_time;
    //$msg['system_msg_id'] = $sys_msg_id;
    //$msg['type'] = 0;
    //$msg['group_key'] = implode("_",$group_arr);
    //$msg['is_notice'] = intval($is_notice);
    //$msg['fav_id'] = intval($fav_id);
    //$GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg);
    //$id = $GLOBALS['db']->insert_id();
    //if($is_notice)
    //$GLOBALS['db']->query("update ".DB_PREFIX."msg_box set group_key = '".$msg['group_key']."_".$id."' where id = ".$id);
    //if(!$only_send)
    //{
    //    $msg['type'] = 1; //记录发件
    //    $GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg);
    //}
}


function show_ke_image($id,$cnt="")
{
    if($cnt)
    {
        $image_path = $cnt;
        $is_show="display:inline-block;";
        $script = "onclick='window.open(this.src);'";
    }
    else{
        $image_path =APP_ROOT."/static/admin/Common/images/no_pic.gif";
        $is_show="display:none;";
    }
    return    "<div style='width:120px; height:40px; margin-left:10px; display:inline-block;  float:left;' class='none_border'>
                            <script type='text/javascript'>var eid = '".$id."';KE.show({urlType:'domain', id:eid, items : ['upload_image'],skinType: 'tinymce',allowFileManager : false,resizeMode : 0,afterBlur:function(){this.sync();}});</script>
                            <div style='font-size:0px;'>
                            <textarea id='".$id."' name='".$id."' style='width:125px; height:25px;' >".$cnt."</textarea>
                            <input type='text' id='focus_".$id."' style='font-size:0px; border:0px; padding:0px; margin:0px; line-height:0px; width:0px; height:0px;' />
                            </div>
                        </div>
                        <img src='".$image_path."' $script  style='display:inline-block; float:left; cursor:pointer; margin-left:10px; border:#ccc solid 1px; width:35px; height:35px;' id='img_".$id."' />
                        <img src='".APP_ROOT."/static/admin/Common/images/del.gif' style='".$is_show." margin-left:10px; float:left; border:#ccc solid 1px; width:35px; height:35px; cursor:pointer;' id='img_del_".$id."' onclick='delimg(\"".$id."\")' title='删除' />";

}

function show_ke_textarea($id,$width=630,$height=350,$cnt="")
{
    return "<script type='text/javascript'> var eid = '".$id."';KE.show({urlType:'domain', id:eid, items : ['fullscreen', 'fsource', 'undo', 'redo', 'cut', 'copy', 'paste','plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright','justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript','superscript', 'selectall', '-','title', 'fontname', 'fontsize', 'textcolor', 'bold','italic', 'underline', 'strikethrough', 'removeformat', 'image','flash', 'media', 'table', 'hr', 'link', 'unlink'], skinType: 'tinymce',allowFileManager : false,resizeMode : 0});</script><div  style='margin-bottom:5px; '><textarea id='".$id."' name='".$id."' style='width:".$width."px; height:".$height."px;' >".$cnt."</textarea> </div>";
}

function replace_public($content)
{
     $domain = app_conf("PUBLIC_DOMAIN_ROOT")==''?get_domain().APP_ROOT:app_conf("PUBLIC_DOMAIN_ROOT");
     $domain_origin = get_domain().APP_ROOT;
     $content = str_replace($domain."/public/","./public/",$content);
     $content = str_replace($domain_origin."/public/","./public/",$content);
     return $content;
}

function check_user_auth($m_name,$a_name,$rel_id)
{
    $rs = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_auth where m_name = '".$m_name."' and a_name = '".$a_name."' and user_id = ".intval($GLOBALS['user_info']['id']));
    foreach($rs as $row)
    {
        if($row['rel_id']==0||$row['rel_id']==$rel_id)
        {
            return true;
        }
    }
    return false;
}

function get_user_auth()
{
    $user_auth = array();
    //定义用户权限
    $user_auth_rs = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_auth where user_id = ".intval($GLOBALS['user_info']['id']));
    foreach($user_auth_rs as $k=>$row)
    {
        $user_auth[$row['m_name']][$row['a_name']][$row['rel_id']] = true;
    }
    return $user_auth;
}


function get_op_change_show($m_name,$a_name)
{
    if($a_name=="replydel"||$a_name=='del')
    {
        //删除
        $money = doubleval(app_conf("USER_DELETE_MONEY"));
        $money_f = "-".format_price(0-$money);
        $score = intval(app_conf("USER_DELETE_SCORE"));
        $score_f = "-".format_score(0-$score);
        $point = intval(app_conf("USER_DELETE_POINT"));
        $point_f = "-".(0-$point)."经验";
    }
    else
    {
        //增加
        $money = doubleval(app_conf("USER_ADD_MONEY"));
        $money_f = "+".format_price($money);
        $score = intval(app_conf("USER_ADD_SCORE"));
        $score_f = "+".format_score($score);
        $point = intval(app_conf("USER_ADD_POINT"));
        $point_f = "+".$point."经验";
    }
    $str = "";
    if($money!=0)$str .= $money_f;
    if($score!=0)$str .= $score_f;
    if($point!=0)$str .= $point_f;
    return $str;

}

function get_op_change($m_name,$a_name)
{
    if($a_name=="replydel"||$a_name=='del')
    {
        //删除
        $money = doubleval(app_conf("USER_DELETE_MONEY"));

        $score = intval(app_conf("USER_DELETE_SCORE"));

        $point = intval(app_conf("USER_DELETE_POINT"));

    }
    else
    {
        //增加
        $money = doubleval(app_conf("USER_ADD_MONEY"));

        $score = intval(app_conf("USER_ADD_SCORE"));

        $point = intval(app_conf("USER_ADD_POINT"));

    }
    return array("money"=>$money,"score"=>$score,"point"=>$point);

}

function show_topic_form($text_name,$width="300px",$height="80px",$is_img = false,$is_topic = false,$is_event = false,$id="topic_form_textarea",$show_btn=false,$show_tag=false)
{

    $GLOBALS['tmpl']->caching = true;
    $cache_id  = md5("show_topic_form".$text_name.$width.$height.$is_img.$is_topic.$is_event.$id.$show_btn);
    if (!$GLOBALS['tmpl']->is_cached('inc/topic_form.html', $cache_id))
    {
        $GLOBALS['tmpl']->assign("text_name",$text_name);
        //输出表情数据html
        $result = $GLOBALS['db']->getAll("select `type`,`title`,`emotion`,`filename` from ".DB_PREFIX."expression order by type");
        $expression = array();
        foreach($result as $k=>$v)
        {
            $v['filename'] = "./public/expression/".$v['type']."/".$v['filename'];
            $v['emotion'] = str_replace(array('[',']'),array('',''),$v['emotion']);
            $expression[$v['type']][] = $v;
        }

        $tag_list =$GLOBALS['db']->getAll("select name from ".DB_PREFIX."topic_tag where is_preset = 1 order by count desc limit 5");

        $GLOBALS['tmpl']->assign("tag_list",$tag_list);
        $GLOBALS['tmpl']->assign("expression",$expression);
        $GLOBALS['tmpl']->assign("is_img",$is_img);
        $GLOBALS['tmpl']->assign("width",$width);
        $GLOBALS['tmpl']->assign("height",$height);
        $GLOBALS['tmpl']->assign("is_event",$is_event);
        if($is_event)
        {
            $fetch_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."fetch_topic where is_effect = 1 order by sort desc");
            $GLOBALS['tmpl']->assign("fetch_list",$fetch_list);
        }
        $GLOBALS['tmpl']->assign("is_topic",$is_topic);
        $GLOBALS['tmpl']->assign("box_id",$id);
        $GLOBALS['tmpl']->assign("show_btn",$show_btn);
        $GLOBALS['tmpl']->assign("show_tag",$show_tag);
    }
    return $GLOBALS['tmpl']->fetch("inc/topic_form.html",$cache_id);
}

function get_gopreview()
{
        $gopreview = es_session::get("gopreview");
        if(!isset($gopreview)||$gopreview=="")
        {
            $gopreview = es_session::get('before_login')?es_session::get('before_login'):url("index");
        }
        es_session::delete("before_login");
        es_session::delete("gopreview");
        return $gopreview;
}

/**
 * 只获取需要登录的url
 */
function get_login_gopreview(){
    $gopreview = es_session::get("gopreview");
    if(!isset($gopreview)||$gopreview=="")
    {
        $gopreview = url("index");
    }
    es_session::delete("gopreview");
    return $gopreview;
}
function set_gopreview()
{
    $url  =  $_SERVER['REQUEST_URI'].((strpos($_SERVER['REQUEST_URI'],'?')===FALSE)?'':"?");
    $parse = parse_url($url);
    if(isset($parse['query'])) {
            if(stripos($parse['query'], 'ajax') !== false){
                return;
            }
            if(stripos($url, 'api') !== false){
                return;
            }
            parse_str($parse['query'],$params);
            $url   =  $parse['path'].'?'.http_build_query($params);
    }
//    if(app_conf("URL_MODEL")==1)$url = $GLOBALS['current_url'];
//    $url = trim($url,'-');

    es_session::set("gopreview",$url);
}

function app_recirect_preview()
{
    return app_redirect(get_gopreview());
}


/**
 * 剩余时间
 */
function remain_time($remain_time){
    $d = intval($remain_time/86400);
    $h = floor(($remain_time%86400)/3600);
    $m = floor(($remain_time%3600)/60);
    if($d>0){
        return '剩余<em>'.$d.'</em>'.$GLOBALS['lang']['DAY'].'<em>';
    }
    return '<em>'.$h.'</em>'.$GLOBALS['lang']['HOUR'].'<em>'.$m.'</em>'.$GLOBALS['lang']['MIN'];
}

/**
 * 数字转为字符串
 * @author zhang ruoshi
 * @param int $integer
 * @return string
 */
function base62encode($integer) {
    $base = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = strlen($base);
    while($integer > $length - 1)
    {
        $out = $base[fmod($integer, $length)] . $out;
        $integer = floor( $integer / $length );
    }
    return $base[$integer] . $out;
}

/**
 * 字符串转为数字
 * @author zhang ruoshi
 * @param string $string
 * @return int
 */
function base62decode($string) {
    $base = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = strlen($base);
    $size = strlen($string) - 1;
    $string = str_split($string);
    $out = strpos($base, array_pop($string));
    foreach($string as $i => $char)
    {
        $out += strpos($base, $char) * pow($length, $size - $i);
    }
    return $out;
}

/**
 *  获取贷款用途
 */
function get_loan_type(){
    return $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_loan_type");
}

/**
 *  获取担保机构
 */
function get_agency_list(){
    return $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_agency");
}

/**
 * 获取担保公司信息
 */
function get_agency_info($agency_id){
    if(empty($agency_id)) return false;
    return $GLOBALS['db']->get_slave()->getRow("SELECT * FROM ".DB_PREFIX."deal_agency WHERE id = ".intval($agency_id));
}

/**
*数字金额转换成中文大写金额的函数
*String Int  $num  要转换的小写数字或小写字符串
*return 大写字母
*小数位为两位
**/
/* function get_amount($num){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    $num = round($num, 2);
    $num = $num * 100;
    if (strlen($num) > 10) {
        return "---";
    }
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            $n = substr($num, strlen($num)-1, 1);
        } else {
            $n = $num % 10;
        }
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        $num = $num / 10;
        $num = (int)$num;
        if ($num == 0) {
            break;
        }
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        $m = substr($c, $j, 6);
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j-3;
            $slen = $slen-3;
        }
        $j = $j + 3;
    }

    if (substr($c, strlen($c)-3, 3) == '零') {
        $c = substr($c, 0, strlen($c)-3);
    }
    if (empty($c)) {
        return "零元整";
    }else{
        return $c . "整";
    }
} */

/**
 * 人民币小写转大写
 *
 * @param string $number 数值
 * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
 * @param bool $is_round 是否对小数进行四舍五入
 * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
 * @return string
 */
function get_amount($number = 0)
{
    $int_unit = '元';
    $is_round = true;
    $is_extra_zero = false;

    // 将数字切分成两段
    $parts = explode('.', $number, 2);
    $int = isset($parts[0]) ? strval($parts[0]) : '0';
    $dec = isset($parts[1]) ? strval($parts[1]) : '';

    // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
    $dec_len = strlen($dec);
    if (isset($parts[1]) && $dec_len > 2) {
        $dec = $is_round ? substr(
                strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1) : substr(
                        $parts[1], 0, 2);
    }

    // 当number为0.001时，小数点后的金额为0元
    if ((empty($int) && empty($dec)) || $number == 0) {
        return '零元整';
    }

    // 定义
    $chs = array(
            '0',
            '壹',
            '贰',
            '叁',
            '肆',
            '伍',
            '陆',
            '柒',
            '捌',
            '玖'
    );
    $uni = array(
            '',
            '拾',
            '佰',
            '仟'
    );
    $dec_uni = array(
            '角',
            '分'
    );
    $exp = array(
            '',
            '万'
    );
    $res = '';

    // 整数部分从右向左找
    for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k ++) {
        $str = '';
        // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
        for ($j = 0; $j < 4 && $i >= 0; $j ++, $i --) {
            $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
            $str = $chs[$int{$i}] . $u . $str;
        }
        $str = rtrim($str, '0'); // 去掉末尾的0
        $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
        if (! isset($exp[$k])) {
            $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
        }
        $u2 = $str != '' ? $exp[$k] : '';
        $res = $str . $u2 . $res;
    }

    // 如果小数部分处理完之后是00，需要处理下
    $dec = rtrim($dec, '0');

    $res .= empty($int) ? '' : $int_unit;

    // 小数部分从左向右找
    if (! empty($dec)) {

        // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
        if ($is_extra_zero) {
            if (substr($int, - 1) === '0') {
                $res .= '零';
            }
        }

        for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i ++) {
            $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
            $res .= $chs[$dec{$i}] . $u;
        }
        $tag = $number < 0.1 ? '' : '零'; // 兼容0.03的情况
        $res = rtrim($res, '0'); // 去掉末尾的0
        $res = preg_replace("/0+/", $tag, $res); // 替换多个连续的0
    } else {
        $res .= '整';
    }
    return $res;
}

/**
* 根据用户id获取银行卡信息
* @author wenyanlei  2013-7-15
* @param  $userid    用户id
* @return array
*/
function get_user_bank( $userid = 0 ){
    if($userid <= 0) return array();
    $bank_list = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM ".DB_PREFIX."bank ORDER BY is_rec DESC,sort DESC,id ASC");
    //用户银行卡信息
    $bankcard_info = \core\dao\UserBankcardModel::instance()->getNewCardByUserId($userid);
    if($bankcard_info){
        foreach($bank_list as $k=>$v){
            if($v['id'] == $bankcard_info['bank_id']){
                $bankcard_info['bankname'] = $v['name'];
                break;
            }
        }
        return $bankcard_info;
    }
    return array();
}

/**
 * 根据借款合同记录反查借款金额、期限信息 @todo by optimize
 * @author wenyanlei  2013-10-12
 * @param $contract_id 合同id
 * @return array
 */
function getLoadByConid($contract_id){

    $return = array();
    $contract_id = intval($contract_id);
    if($contract_id <= 0){
        return $return;
    }

    $contract = $GLOBALS ['db']->getRow( "select * from " . DB_PREFIX . "contract where id = ". $contract_id ." limit 1");
    if(empty($contract)){
        return $return;
    }

    $load_list = $GLOBALS ['db']->getAll( "select id,user_id,money from " . DB_PREFIX . "deal_load where deal_id = ". $contract['deal_id'] );

    if($load_list){
        $deal_info = $GLOBALS ['db']->getRow( "select id,parent_id,repay_time from " . DB_PREFIX . "deal where id = ". $contract['deal_id'] ." limit 1");
        foreach($load_list as $load_one){
            $number = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],1);

            if($number == $contract['number']){
                $return = array('money' => $load_one['money'], 'repay_time' => $deal_info['repay_time']);
                break;
            }
        }
    }
    return $return;
}

/**
 * 根据合同id，反查投标记录id
 * @author wenyanlei  2013-10-12
 * @param $contract_id 合同id
 * @return array
 */
/* function getLoadidByConid($contract_id, $role, $borrow_user_id, $guarantor_list){

    $return = array();
    $contract_id = intval($contract_id);

    if($contract_id <= 0){
        return $return;
    }

    $contract = $GLOBALS ['db']->getRow( "select * from " . DB_PREFIX . "contract where id = ". $contract_id ." limit 1");

    if(empty($contract)){
        return $return;
    }

    $contract_type = $contract['type'];

    $load_list = $GLOBALS ['db']->getAll( "select * from " . DB_PREFIX . "deal_load where deal_id = ". $contract['deal_id'] );

    if($load_list){

        $deal_info = $GLOBALS ['db']->getRow( "select id,parent_id from " . DB_PREFIX . "deal where id = ". $contract['deal_id'] ." limit 1");

        foreach($load_list as $load_one){

            //1借款合同，2委托担保合同，3保证反担保合同，4保证合同,5出借人平台服务协议(借款人平台服务协议)，6付款委托书
            $number = array();

            //下面生成合同编号的过程，请对比send_contract.php
            if($contract_type == 1){

                $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],1);//借款合同

            }elseif($contract_type == 2){

                $number[] = get_contract_number($deal_info, $borrow_user_id, $load_one['id'],2);//委托担保合同

            }elseif($contract_type == 3){

                foreach($guarantor_list as $guarantor){

                    $number[] = get_contract_number($deal_info, $guarantor['to_user_id'], $load_one['id'],3);//保证反担保
                }

            }elseif($contract_type == 4){

                $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],4);//保证合同

            }elseif($contract_type == 5){

                if($role == 2){
                    $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],5);//出借人平台服务协议
                }elseif($role == 1){
                    $number[] = get_contract_number($deal_info, $borrow_user_id, 000,5);//借款人平台服务协议
                }

            }elseif($contract_type == 6){
                $number[] = get_contract_number($deal_info, $borrow_user_id, 000,6);//出借人平台服务协议

            }elseif($contract_type == 7){    //资产权益回购通知
                $number[] = get_contract_number($deal_info, $load_one['user_id'], $load_one['id'],7);
            }

            if($number){
                foreach($number as $num){
                    if($num == $contract['number']){
                        $return = $load_one;
                        break;
                    }
                }
            }

        }
    }
    return $return;
} */


/**
 * 生成设置表单token值,session
 * @param int $token_id
 * @return string
 */
function mktoken($token_id)
{
    $k = 'ql_token_' . $token_id;
    if (isset($_SESSION[$k]) && $_SESSION[$k]) {
        return $_SESSION[$k];
    }
    $token = md5(uniqid(rand(), true) . $token_id);
    $_SESSION[$k] = $token;

    return $token;
}


/**
 * 生成表单令牌 Html
 */
function token_input($token_id = 0)
{
    //$token_id = 1 . intval($token_id);
    if (!$token_id) {
        $token_id = round(microtime(true) * 1000);
    }
    $token = mktoken($token_id);
    return "<input type='hidden' id='token_id' name='token_id' value='{$token_id}' ><input type='hidden' id='token' name='token' value='{$token}' >";
}

/**
 * 验证表单令牌
 * @param string $token_id
 * @return number 返回1为通过，0为失败
 */
function check_token($token_id = '')
{
    $token_id = empty($token_id) ? $_REQUEST['token_id'] : $token_id;
    $token = $_REQUEST['token'];
    if( empty($token_id) || empty($token) )
        return 0;

    $k = 'ql_token_' . $token_id;

    if($token == $_SESSION[$k])
    {
        $_SESSION[$k] = "";
        unset($_SESSION[$k]);
        return 1;
    }
    else
        return 0;
}


/**
 * 验证确认投资表单令牌，修复投资失败，令牌时效问题
 * @param number de_token  true为删除 false不删除
 * @return number 返回1为通过，0为失败
 */
function bid_check_token($de_token = false)
{
    $token_id = $_REQUEST['token_id'];
    $token = $_REQUEST['token'];
    if( empty($token_id) || empty($token) ){
        return 0;
    }

    $k = 'ql_token_' . $token_id;

    if(isset($_SESSION[$k]) && $token == $_SESSION[$k]){
        if($de_token){
            $_SESSION[$k] = "";
            unset($_SESSION[$k]);
        }
        return 1;
    }else{
        return 0;
    }
}

//[/CODE_BLOCK_END]





//[CODE_BLOCK_START][app/Lib/site_lib.php]


//获取指定的分类列表
function get_cate_tree($pid = 0,$is_all = 0)
{
    return load_auto_cache("cache_site_cate_tree",array("pid"=>$pid,"is_all"=>$is_all));
}

//获取指定的文章分类列表
function get_acate_tree($pid = 0)
{
    return load_auto_cache("cache_shop_acate_tree",array("pid"=>$pid));
}


/**
 * 获取指定的产品
 */
function get_goods($id=0,$preview=0)
{

        syn_deal_status($id);
        if($preview)
        $deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".intval($id)." and is_delete = 0 ");
        else
        $deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".intval($id)." and is_effect = 1 and is_delete = 0 ");
        if($deal)
        {

            //格式化数据
            $deal['origin_price_format'] = format_price($deal['origin_price']);
            $deal['current_price_format'] = format_price($deal['current_price']);

            if($deal['origin_price']>0&&floatval($deal['discount'])==0) //手动折扣
            $deal['save_price'] = $deal['origin_price'] - $deal['current_price'];
            else
            $deal['save_price'] = $deal['origin_price']*((10-$deal['discount'])/10);

            if($deal['origin_price']>0&&floatval($deal['discount'])==0)
            $deal['discount'] = round(($deal['current_price']/$deal['origin_price'])*10,2);

            $deal['discount'] = round($deal['discount'],2);

            $deal['save_price_format'] = format_price($deal['save_price']);

            //团购图片集
            $img_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_gallery where deal_id=".intval($deal['id'])." order by sort asc");
            foreach($img_list as $k=>$v)
            {
                $img_list[$k]['origin_img'] = preg_replace("/\/big\//","/origin/",$v['img']);
            }
            $deal['image_list'] = $img_list;

            //商户信息
            $deal['supplier_info'] = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supplier where id = ".intval($deal['supplier_id']));
            $deal['supplier_address_info'] = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supplier_location where supplier_id = ".intval($deal['supplier_id'])." and is_main = 1");

            //品牌信息
            $deal['brand_info'] = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."brand where id = ".intval($deal['brand_id']));


            //属性列表
            $deal_attrs_res = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_attr where deal_id = ".intval($deal['id'])." order by id asc");
            if($deal_attrs_res)
            {
                foreach($deal_attrs_res as $k=>$v)
                {
                    $deal_attr[$v['goods_type_attr_id']]['name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."goods_type_attr where id = ".intval($v['goods_type_attr_id']));
                    $deal_attr[$v['goods_type_attr_id']]['attrs'][] = $v;
                }
                $deal['deal_attr_list'] = $deal_attr;
            }

            if($deal['uname']!='')
            $gurl = url("shop","goods",array("id"=>$deal['uname']));
            else
            $gurl = url("shop","goods",array("id"=>$deal['id']));

            $deal['share_url'] = $gurl;
            if($GLOBALS['user_info'])
            {
                if(app_conf("URL_MODEL")==0)
                {
                    $deal['share_url'] .= "&r=".base64_encode(intval($GLOBALS['user_info']['id']));
                }
                else
                {
                    $deal['share_url'] .= "?r=".base64_encode(intval($GLOBALS['user_info']['id']));
                }
            }

            //查询抽奖号
            $deal['lottery_count'] = intval($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."lottery where deal_id = ".intval($deal['id'])." and buyer_id <> 0 ")) + intval($deal['buy_count']);

            //开始获取处理库存
            $deal['stock'] = $deal['max_bought'] - $deal['buy_count'];
        }

        return $deal;
}


/**
 * 获取产品列表
 */
function get_goods_list($limit, $cate_id=0, $where='',$orderby = '',$cached = true,$city_id=0)
{
        if($city_id==0)
        {
                $city = get_current_deal_city();
                $city_id = $city['id'];
        }
        $key = md5($limit.$cate_id.$where.$orderby.$city_id);
        if($cached)
        {
            $res = $GLOBALS['cache']->get($key);
        }
        else
        {
            $res = false;
        }
        if($res===false)
        {
            $time = get_gmtime();
            $time_condition = '  and (is_shop=1  or shop_cate_id <> 0 ) ';

            $count_sql = "select count(*) from ".DB_PREFIX."deal where is_effect = 1 and is_delete = 0 ".$time_condition;
            $sql = "select * from ".DB_PREFIX."deal where is_effect = 1 and is_delete = 0 ".$time_condition;

            if($cate_id>0)
            {
                $ids = load_auto_cache("shop_sub_cate_ids",array("cate_id"=>$cate_id));
                $sql .= " and shop_cate_id in (".implode(",",$ids).")";
                $count_sql .= " and shop_cate_id in (".implode(",",$ids).")";
            }




            if($city_id>0)
            {
                $ids = load_auto_cache("deal_city_belone_ids",array("city_id"=>$city_id));
                if($ids)
                {
                $sql .= " and city_id in (".implode(",",$ids).")";
                $count_sql .= " and city_id in (".implode(",",$ids).")";
                }
            }


            if($where != '')
            {
                $sql.=" and ".$where;
                $count_sql.=" and ".$where;
            }

            if($orderby=='')
            $sql.=" order by sort desc limit ".$limit;
            else
            $sql.=" order by ".$orderby." limit ".$limit;

            $deals = $GLOBALS['db']->getAll($sql);
            $deals_count = $GLOBALS['db']->getOne($count_sql);

             if($deals)
            {
                foreach($deals as $k=>$deal)
                {
                    if($deal['buy_type']==1)
                    $module = "exchange";
                    else
                    $module = "goods";

                    if($deal['uname']!='')
                    $durl = url("shop",$module,array("id"=>$deal['uname']));
                    else
                    $durl = url("shop",$module,array("id"=>$deal['id']));

                    $deal['url'] = $durl;
                    $deals[$k] = $deal;
                }
            }
            $res = array('list'=>$deals,'count'=>$deals_count);
            $GLOBALS['cache']->set($key,$res);
        }
        return $res;
}



/**
 * 获取产品列表
 */
function search_goods_list($limit, $cate_id=0, $where='',$orderby = '',$cached = true, $join_str = '')
{
        $key = md5($limit.$cate_id.$where.$orderby.$join_str);
        if($cached)
        {
            $res = $GLOBALS['cache']->get($key);
        }
        else
        {
            $res = false;
        }
        if($res===false)
        {

            $count_sql = "select count(*) from ".DB_PREFIX."deal as d" ;
            $sql = "select d.* from ".DB_PREFIX."deal as d ";

            if($join_str!='')
            {
                $count_sql.=$join_str;
                $sql.=$join_str;
            }

            $time = get_gmtime();
            $time_condition = '  and (d.is_shop=1  or d.shop_cate_id <> 0 ) ';

            $count_sql .= " where d.is_effect = 1 and d.is_delete = 0 ".$time_condition;
            $sql .= " where d.is_effect = 1 and d.is_delete = 0 ".$time_condition;

            if($cate_id>0)
            {
                $ids = load_auto_cache("shop_sub_cate_ids",array("cate_id"=>$cate_id));
                $sql .= " and d.shop_cate_id in (".implode(",",$ids).")";
                $count_sql .= " and d.shop_cate_id in (".implode(",",$ids).")";
            }

            $city = get_current_deal_city();
            $city_id = $city['id'];

            if($city_id>0)
            {
                $ids =  load_auto_cache("deal_city_belone_ids",array("city_id"=>$city_id));
                if($ids)
                {
                $sql .= " and city_id in (".implode(",",$ids).")";
                $count_sql .= " and city_id in (".implode(",",$ids).")";
                }
            }

            if($where != '')
            {
                $sql.=" and ".$where;
                $count_sql.=" and ".$where;
            }

            if($orderby=='')
            $sql.=" order by d.sort desc limit ".$limit;
            else
            $sql.=" order by ".$orderby." limit ".$limit;


            $deals = $GLOBALS['db']->getAll($sql);
            $deals_count = $GLOBALS['db']->getOne($count_sql);

             if($deals)
            {
                foreach($deals as $k=>$deal)
                {

                    //格式化数据
                    $deal['origin_price_format'] = format_price($deal['origin_price']);
                    $deal['current_price_format'] = format_price($deal['current_price']);


                    if($deal['origin_price']>0&&floatval($deal['discount'])==0) //手动折扣
                    $deal['save_price'] = $deal['origin_price'] - $deal['current_price'];
                    else
                    $deal['save_price'] = $deal['origin_price']*((10-$deal['discount'])/10);
                    if($deal['origin_price']>0&&floatval($deal['discount'])==0)
                    {
                        $deal['discount'] = round(($deal['current_price']/$deal['origin_price'])*10,2);
                    }

                    $deal['discount'] = round($deal['discount'],2);



                    $deal['save_price_format'] = format_price($deal['save_price']);
                    if($deal['uname']!='')
                    $durl = url("shop","goods",array("id"=>$deal['uname']));
                    else
                    $durl = url("shop","goods",array("id"=>$deal['id']));
                    $deal['url'] = $durl;

                    $deals[$k] = $deal;
                }
            }
            $res = array('list'=>$deals,'count'=>$deals_count);
            $GLOBALS['cache']->set($key,$res);
        }
        return $res;
}


//[/CODE_BLOCK_END]


//[CODE_BLOCK_START][system/system_init.php]

function url_rewrite(){
    $path_info = explode("?", $_SERVER['REQUEST_URI']);
    $url = $path_info[0];
    if(preg_match("/^(.*?)\/index\/(([_0-9a-zA-Z]+)[^\.&]*)$/", $url, $matches)){
        $_REQUEST['rewrite_param'] = $matches[2];
    } else if(preg_match("/^(.*?)\/(index)([^\.&]*)$/", $url, $matches)){
        $_REQUEST['rewrite_param'] = $matches[3];
    } else if(preg_match("/^(.*?)\/(([_0-9a-zA-Z]+)[^\.&]*)$/", $url, $matches)){
        $_REQUEST['rewrite_param'] = $matches[2];
    }
    //重写模式
    $current_url = APP_ROOT;
    $current_file = explode("/",_PHP_FILE_);
    $current_file = $current_file[count($current_file)-1];
    if($current_file=='index.php'||$current_file=='shop.php')
    $app_index = "";
    else
    $app_index = str_replace(".php","",$current_file);
    if($app_index!="")
    $current_url = $current_url."/".$app_index;

    $rewrite_param = isset($_REQUEST['rewrite_param']) ? $_REQUEST['rewrite_param'] : '';
    $rewrite_param = explode("/",$rewrite_param);

    $rewrite_param_array = array();
    foreach($rewrite_param as $k=>$param_item)
    {
        if($param_item!='')
        $rewrite_param_array[] = $param_item;
    }
    foreach ($rewrite_param_array as $k=>$v)
    {
        if(substr($v,0,1)=='-')
        {
            //扩展参数
            $v = substr($v,1);
            $ext_param = explode("-",$v);
            foreach($ext_param as $kk=>$vv)
            {
                if($kk%2==0)
                {
                    if(preg_match("/(\w+)\[(\w+)\]/",$vv,$matches))
                    {
                        $_GET[$matches[1]][$matches[2]] = $ext_param[$kk+1];
                    }
                    else
                    $_GET[$ext_param[$kk]] = $ext_param[$kk+1];

                    if($ext_param[$kk]!="p")
                    {
                        $current_url.=$ext_param[$kk];
                        $current_url.="-".$ext_param[$kk+1]."-";
                    }
                }
            }
        }
        elseif($k==0)
        {
            //解析ctl与act
            $ctl_act = explode("-",$v);
            $ctl_var_arr = array('cid','id','aid','qid','pid','cid','a');
            //if($ctl_act[0]!='cid'&&$ctl_act[0]!='id'&&$ctl_act[0]!='aid'&&$ctl_act[0]!='qid'&&$ctl_act[0]!='pid'&&$ctl_act[0]!='cid'&&$ctl_act[0]!='a')
            if (!in_array($ctl_act[0], $ctl_var_arr))
            {
                $_GET['ctl'] = $ctl_act[0];
                $_GET['act'] = isset($ctl_act[1]) ? $ctl_act[1] : '';

                $current_url.="/".$ctl_act[0];
                if(isset($ctl_act[1]))
                $current_url.="-".$ctl_act[1]."/";
                else
                $current_url.="/";
            }
            else
            {
                //扩展参数
                $ext_param = explode("-",$v);
                foreach($ext_param as $kk=>$vv)
                {
                    if($kk%2==0)
                    {
                        if(preg_match("/(\w+)\[(\w+)\]/",$vv,$matches))
                        {
                            $_GET[$matches[1]][$matches[2]] = $ext_param[$kk+1];
                        }
                        else
                        $_GET[$ext_param[$kk]] = $ext_param[$kk+1];

                        if($ext_param[$kk]!="p")
                        {
                            if($kk==0)$current_url.="/";
                            $current_url.=$ext_param[$kk];
                            $current_url.="-".$ext_param[$kk+1]."-";
                        }
                    }
                }
            }

        }elseif($k==1)
        {
            //扩展参数
            $ext_param = explode("-",$v);
            foreach($ext_param as $kk=>$vv)
            {
                if($kk%2==0)
                {
                    if(preg_match("/(\w+)\[(\w+)\]/",$vv,$matches))
                    {
                        $_GET[$matches[1]][$matches[2]] = $ext_param[$kk+1];
                    } else {
                        if (isset($ext_param[$kk]) && isset($ext_param[$kk+1])) {
                            $_GET[$ext_param[$kk]] = $ext_param[$kk+1];
                        }
                    }

                    if($ext_param[$kk]!="p") {
                        $current_url.=$ext_param[$kk];
                        if (isset($ext_param[$kk+1])) {
                            $current_url.="-".$ext_param[$kk+1]."-";
                        }
                    }
                }
            }
        }
    }
    $current_url = substr($current_url,-1)=="-"?substr($current_url,0,-1):$current_url;
    $GLOBALS['current_url'] = $current_url;

    $domain = get_host();
    if(strpos($domain,".".app_conf("DOMAIN_ROOT")))
    {
        $city = str_replace(".".app_conf("DOMAIN_ROOT"),"",$domain);
        if($city!='')
        $_GET['city'] = $city;
    }
    unset($_REQUEST['rewrite_param']);
    unset($_GET['rewrite_param']);
}
/**
 * 根据配置是否需要启用https
 * @param string $control
 * @param string $action
 * @param int $type 1静态路径前缀，2附件前缀
 * @return 空或array('protocol' => 'https','path_prefix' => '')
 */
function getIsHttps($control,$action,$type=1){
    if (empty($control) || empty($action) || empty($type)){
        return '';
    }
    // 是否已启用
    $switch_https = empty($GLOBALS['sys_config']['IS_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['IS_HTTPS']);
    if (empty($switch_https)){
        return '';
    }
    $has_https = $GLOBALS['sys_config']['SSL_PAGES'];
    if (empty($has_https)){
        return '';
    }
    $has_https_module = array();
    $temp_module = explode(',', $has_https);
    if (!empty($temp_module)){
        foreach ($temp_module as $tkey => $tv){
            $temp_module_action = explode('/', $tv);
            if (!empty($temp_module_action)){
                if(!empty($has_https_module[$temp_module_action[0]])){

                    $has_https_module[$temp_module_action[0]] .= ','.$temp_module_action[1];

                }else{

                    $has_https_module[$temp_module_action[0]]  = $temp_module_action[1];

                }

            }
        }
    }
    $ret = array('protocol' => '','path_prefix' => '');
    $path_prefx = '';
    // 先判断control
    if(isset($has_https_module[$control])){
        // 判断action
        if ($has_https_module[$control] == '*'){
            // 这个control下面所有访问
        }else{
            if (empty($has_https_module[$control])){
                return '';
            }
            $all_action = explode(',', $has_https_module[$control]);
            if (empty($all_action)){
                return '';
            }
            if (!in_array($action,$all_action)){
                return '';
            }
        }
        switch ($type){
            case 1:
                $path_prefx = 'static';
                break;
            case 2:
                $path_prefx = 'attachment';
                break;
            default:
                break;
        }
        $ret['protocol'] = 'https';
        $ret['path_prefix'] = $path_prefx;
        return  $ret;
    }

    return '';
}

/**
 * 读取后台配置的模块，这些模块访问不做任何跳转
 * 同时支持http和https
 * @param string $control
 * @param string $action
 * @return bool  true|false
 */
function getNotIsHttps($control,$action){
    if (empty($control) || empty($action)){
        return false;
    }
    // 强制走SSL_PAGES, 以下配置为空即可
    isset($GLOBALS['sys_config']['HTTP_SSL_PAGES']) && $not_has_https = $GLOBALS['sys_config']['HTTP_SSL_PAGES'];
    if (empty($not_has_https)){
        return false;
    }
    // 分析模块
    $not_has_https_module = array();
    $temp_module = explode(',', $not_has_https);
    if (!empty($temp_module)){
        foreach ($temp_module as $tkey => $tv){
            $temp_module_action = explode('/', $tv);
            if (!empty($temp_module_action)){
                if(!empty($not_has_https_module[$temp_module_action[0]])){

                    $not_has_https_module[$temp_module_action[0]] .= ','.$temp_module_action[1];

                }else{

                    $not_has_https_module[$temp_module_action[0]]  = $temp_module_action[1];

                }

            }
        }
    }
    // 先判断control
    if(isset($not_has_https_module[$control])){
        // 判断action
        if ($not_has_https_module[$control] == '*'){
            // 这个control下面所有访问
            return true;
        }else{
            if (empty($not_has_https_module[$control])){
                return false;
            }
            $all_action = explode(',', $not_has_https_module[$control]);
            if (empty($all_action)){
                return false;
            }
            if (in_array($action,$all_action)){
                return true;
            }
        }
    }

    return false;

}
/**
 * 根据uri 分析是否需要https
 * @param int $type @see getIsHttps
 * @return bool | array() @see getIsHttps
 */
function uriHttps($type=1){
    if (empty($type)){
        return false;
    }

    $request_uri = trim($_SERVER['REQUEST_URI'],'/'); // 去除前后的/
    $module_action_array = explode('/',$request_uri);
    $module = $module_action_array[0];
    $action = empty($module_action_array[1])? 'index' : $module_action_array[1];
    $is_https = getIsHttps($module, $action, $type);
    if (!empty($is_https['protocol'])){
        return $is_https;
    }

    return false;
}


