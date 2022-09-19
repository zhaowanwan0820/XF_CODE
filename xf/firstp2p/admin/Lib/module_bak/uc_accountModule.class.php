<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.uc");

class uc_accountModule extends SiteBaseModule
{
	public function index()
	{
		$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_ACCOUNT']);
		
		//扩展字段
		$field_list = load_auto_cache("user_field_list");
		
		foreach($field_list as $k=>$v)
		{
			$field_list[$k]['value'] = $GLOBALS['db']->getOne("select value from ".DB_PREFIX."user_extend where user_id=".$GLOBALS['user_info']['id']." and field_id=".$v['id']);
		}
		
		$GLOBALS['tmpl']->assign("field_list",$field_list);
		
		
		//地区列表
		
			$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2");  //二级地址
			foreach($region_lv2 as $k=>$v)
			{
				if($v['id'] == intval($GLOBALS['user_info']['province_id']))
				{
					$region_lv2[$k]['selected'] = 1;
					break;
				}
			}
			$GLOBALS['tmpl']->assign("region_lv2",$region_lv2);
			
			$region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".intval($GLOBALS['user_info']['province_id']));  //三级地址
			foreach($region_lv3 as $k=>$v)
			{
				if($v['id'] == intval($GLOBALS['user_info']['city_id']))
				{
					$region_lv3[$k]['selected'] = 1;
					break;
				}
			}
			$GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
			
			$n_region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".intval($GLOBALS['user_info']['n_province_id']));  //三级地址
			foreach($n_region_lv3 as $k=>$v)
			{
				if($v['id'] == intval($GLOBALS['user_info']['n_city_id']))
				{
					$n_region_lv3[$k]['selected'] = 1;
					break;
				}
			}
			$GLOBALS['tmpl']->assign("n_region_lv3",$n_region_lv3);
			
			
		
		$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_account_index.html");
		$GLOBALS['tmpl']->display("page/uc.html");
	}

    public function invitation() {
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_account_invitation_new.html");
        $user_id = intval($GLOBALS['user_info']['id']);
        $GLOBALS['tmpl']->assign('user_id', $user_id);
        $GLOBALS['tmpl']->assign('domain', get_domain());
        //$GLOBALS['tmpl']->display("page/uc.html");
        //$this->set_nav(array("我的P2P"=>url("index", "uc_center"), "邀请好友"));
        $this->display();
    }

    public function coupon() {
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_account_coupon.html");
        $user_id = intval($GLOBALS['user_info']['id']);
        $GLOBALS['tmpl']->assign('user_id', $user_id);
        $GLOBALS['tmpl']->assign('domain', get_domain());

        // 生成短码
        $coupon_dao = new core\dao\CouponLogModel();
        $coupon_service = new core\service\CouponService();
        $coupon = $coupon_service->genAlias($user_id);

        // 获取返利记录
        $nowPage = !empty($_GET['p']) ? $_GET['p'] : 1;
        $pageSize = app_conf("PAGE_SIZE");
        $firstRow = ($nowPage - 1) * $pageSize;
        $result = $coupon_dao->getLogPaid($user_id, $firstRow, $pageSize);
        $page = new Page($result['count'], $pageSize);
        $GLOBALS['tmpl']->assign('pages', $page->show());

        $GLOBALS['tmpl']->assign('coupon', $coupon);
        $GLOBALS['tmpl']->assign('coupon_log', $result['data']);
        $this->display();
    }
	
	public function work(){
		$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_WORK_AUTH']);
		//地区列表
		$work =  $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."user_work where user_id =".$GLOBALS['user_info']['id']);
		$GLOBALS['tmpl']->assign("work",$work);
		
		$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2");  //二级地址
		foreach($region_lv2 as $k=>$v)
		{
			if($v['id'] == intval($work['province_id']))
			{
				$region_lv2[$k]['selected'] = 1;
				break;
			}
		}
		$GLOBALS['tmpl']->assign("region_lv2",$region_lv2);
		
		$region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".intval($work['province_id']));  //三级地址
		foreach($region_lv3 as $k=>$v)
		{
			if($v['id'] == intval($work['city_id']))
			{
				$region_lv3[$k]['selected'] = 1;
				break;
			}
		}
		$GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
		
		//查询紧急联系人
		$contact_arr = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_contact where user_id = ".$GLOBALS['user_info']['id']." and is_delete = 0 order by create_time asc limit 5");
		$GLOBALS['tmpl']->assign("contact_arr",$contact_arr);
		
		$relation_list =  $GLOBALS['dict']['DICT_RELATIONSHIPS'];

		$GLOBALS['tmpl']->assign('relation', $relation_list);
		$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_work_index.html");
		$GLOBALS['tmpl']->display("page/uc.html");
	}
	
	public function mobile(){
		return app_redirect(url("index","uc_center"));
		
		/*$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_MOBILE']);
		$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_mobile_index.html");
		$GLOBALS['tmpl']->display("page/uc.html");
		*/
	}
	
	public function save()
	{
		FP::import("libs.libs.user");
		foreach($_REQUEST as $k=>$v)
		{
			$_REQUEST[$k] = htmlspecialchars(addslashes(trim($v)));
		}
		if(intval($_REQUEST['id']) == 0 )
			$_REQUEST['id'] = intval($GLOBALS['user_info']['id']);
		$res = save_user($_REQUEST,'UPDATE');
		
		if($res['status'] == 1)
		{
			$s_user_info = es_session::get("user_info");
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = '".intval($s_user_info['id'])."'");
			es_session::set("user_info",$user_info);
			if(intval($_REQUEST['is_ajax'])==1)
				return showSuccess($GLOBALS['lang']['SUCCESS_TITLE'],1);
			else{
				return app_redirect(url("index","uc_account#work"));
			}		
		}
		else
		{
			$error = $res['data'];		
			if(!$error['field_show_name'])
			{
					$error['field_show_name'] = $GLOBALS['lang']['USER_TITLE_'.strtoupper($error['field_name'])];
			}
			if($error['error']==EMPTY_ERROR)
			{
				$error_msg = sprintf($GLOBALS['lang']['EMPTY_ERROR_TIP'],$error['field_show_name']);
			}
			if($error['error']==FORMAT_ERROR)
			{
				$error_msg = sprintf($GLOBALS['lang']['FORMAT_ERROR_TIP'],$error['field_show_name']);
			}
			if($error['error']==EXIST_ERROR)
			{
				$error_msg = sprintf($GLOBALS['lang']['EXIST_ERROR_TIP'],$error['field_show_name']);
			}
			if($error['error']==IDNO_ERROR)
			{
				$error_msg = sprintf($GLOBALS['lang']['IDNO_ERROR'],$error['field_show_name']);
			}
			if($error['error']==IDNO_LINK_ERROR)
			{
				$error_msg = sprintf($GLOBALS['lang']['IDNO_LINK_ERR'],$error['field_show_name']);
			}
			return showErr($error_msg,intval($_REQUEST['is_ajax']));
		}
	}
	
	/**
	 * 修改密码
	 *
	 * @Title: re_password 
	 * @Description: 修改密码
	 * @param    
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	public function re_password(){
                if($GLOBALS['sys_config']['NEW_OAUTH_SWITCH']=='2')
                {
                    $LANG['ERROR_TITLE']='系统维护';
                    $GLOBALS['tmpl']->assign("LANG",$LANG);
                    $GLOBALS['tmpl']->assign("msg","尊敬的用户<br />系统正在进行升级，在此期间将无法登录和注册，请稍后再试。"
                            ."给您带来不便，敬请谅解！");
                    $GLOBALS['tmpl']->display("error.html");
                    exit;
                }
		else
                {
                    //新OAuth 
                    
                    $ucenter = get_http().$GLOBALS['sys_config']['SITE_DOMAIN'][$GLOBALS['sys_config']['APP_SITE']]
                            .url("shop", 'uc_account#do_re_password');                              
                    $url=$GLOBALS['sys_config']['NEW_SET_PWD_API_URL'].'?client_id='.$GLOBALS['sys_config']['NEW_OAUTH_CLIENT_ID']
                            .'&response_type=code&redirect_uri='.urlencode($ucenter);
                    header('Location:'.$url);
                    exit();
                }
                /**
                
                else
                {
                    FP::import("module.user","","Module.class.php");

                    $user = new userModule();

                    $result = $user->re_set_password($data);
                    
                }
		$GLOBALS['tmpl']->assign("page_title",'修改密码');

		$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_account_repassword_new.html");
                
		$this->display();
                **/
	}
	
	/**
	 * 更改密码
	 *
	 * @Title: re_password 
	 * @Description: 更改密码 
	 * @param    
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	public function do_re_password(){		
                if($GLOBALS['sys_config']['NEW_OAUTH_SWITCH']=='2')
                {
                    die('系统维护中，请稍后再试。。');
                }
                else
                {                   
                    return showSuccess('密码修改成功。' ,0, url("shop", 'uc_center#index'));
                }
                /**
		$data = array();
		$data['user_password'] = empty($_POST['old_password']) ? '' : $_POST['old_password'];
		$data['new_password'] = empty($_POST['new_password']) ? '' : $_POST['new_password'];
		$data['r_new_password'] = empty($_POST['re_new_password']) ? '' : $_POST['re_new_password'];
		
		if(empty($data['user_password']) || empty($data['new_password']) || empty($data['r_new_password'])){
			showErr("旧密码、新密码、确认密码不能为空");
		}

		if($data['new_password'] != $data['r_new_password']){
			showErr("新密码与确认密码必须一致！");
		}
		
		$s_user_info = es_session::get("user_info");
		
		$data['id'] = $_SESSION['oauth_id'];
		$data['user_name'] = empty($s_user_info['mobile']) ? $s_user_info['email'] : $s_user_info['mobile'];
		$data['user_phone'] = $s_user_info['mobile'];
		$data['user_email'] = $s_user_info['email'];		
                    
                FP::import("module.user","","Module.class.php");

                $user = new userModule();
                $result = $user->re_set_password($data);                    
                
		if($result){
			showSuccess("新密码设置成功！", 0, url("index", "uc_center"));
		}else{
			showErr("新密码设置失败！", 0, url("index", "uc_center"));
		}
		**/
	}
	
	
	public function savework(){
		//error_reporting(E_ALL);
		foreach($_REQUEST as $k=>$v)
		{
			if(!in_array($k,array('con_id','con_name','con_relation','con_mobile'))){
				$_REQUEST[$k] = htmlspecialchars(addslashes(trim($v)));
			}
		}
		$data['office'] = trim($_REQUEST['office']);
		$data['jobtype'] = trim($_REQUEST['jobtype']);
		$data['province_id'] = intval($_REQUEST['province_id']);
		$data['city_id'] = intval($_REQUEST['city_id']);
		$data['officetype'] = trim($_REQUEST['officetype']);
		$data['officedomain'] = trim($_REQUEST['officedomain']);
		$data['officecale'] = trim($_REQUEST['officecale']);
		$data['position'] = trim($_REQUEST['position']);
		$data['salary'] = trim($_REQUEST['salary']);
		$data['workyears'] = trim($_REQUEST['workyears']);
		$data['workphone'] = trim($_REQUEST['workphone']);
		$data['workemail'] = trim($_REQUEST['workemail']);
		$data['officeaddress'] = trim($_REQUEST['officeaddress']);
		
		/* if(isset($_REQUEST['urgentcontact']))
			$data['urgentcontact'] = trim($_REQUEST['urgentcontact']);
		if(isset($_REQUEST['urgentrelation']))
			$data['urgentrelation'] = trim($_REQUEST['urgentrelation']);
		if(isset($_REQUEST['urgentmobile']))
			$data['urgentmobile'] = trim($_REQUEST['urgentmobile']);
		if(isset($_REQUEST['urgentcontact2']))
			$data['urgentcontact2'] = trim($_REQUEST['urgentcontact2']);
		if(isset($_REQUEST['urgentrelation2']))
			$data['urgentrelation2'] = trim($_REQUEST['urgentrelation2']);
		if(isset($_REQUEST['urgentmobile2']))
			$data['urgentmobile2'] = trim($_REQUEST['urgentmobile2']); */
		
		$contact_id = isset($_POST['con_id']) ? $_POST['con_id'] : array();
		$contact_name = isset($_POST['con_name']) ? $_POST['con_name'] : array();
		$contact_relation = isset($_POST['con_relation']) ? $_POST['con_relation'] : array();
		$contact_mobile = isset($_POST['con_mobile']) ? $_POST['con_mobile'] : array();
		
		//查询原有联系人
		$id_arr = $contact_arr = array();
		$contact_arr = $GLOBALS['db']->getAll("select id from ".DB_PREFIX."user_contact where user_id = ".$GLOBALS['user_info']['id']." and is_delete = 0");
		if(count($contact_arr) > 0){
			foreach($contact_arr as $key => $val){
				$id_arr[$val['id']] = $val['id'];
			}
		}
		
		if(count($contact_name) > 0){
			foreach ($contact_name as $key => $val){
				$contact_arr = array(
						'name' => isset($val) ? htmlspecialchars($val) : '',
						'relation' => isset($contact_relation[$key]) ? htmlspecialchars($contact_relation[$key]) : '',
						'mobile' => isset($contact_mobile[$key]) ? htmlspecialchars($contact_mobile[$key]) : ''
				);
				if(isset($contact_id[$key]) && in_array($contact_id[$key], $id_arr)){
					$GLOBALS['db']->autoExecute(DB_PREFIX."user_contact",$contact_arr,"UPDATE","id=".intval($contact_id[$key]));
					unset($id_arr[$contact_id[$key]]);
				}elseif($contact_arr['name']){
					$contact_arr['user_id'] = $GLOBALS['user_info']['id'];
					$contact_arr['create_time'] = time();
					$GLOBALS['db']->autoExecute(DB_PREFIX."user_contact",$contact_arr,"INSERT");
				}
			}
		}else{
			$id_arr = array();
		}
		
		if(count($id_arr) > 0){
			$ids = implode(',', $id_arr);
			$GLOBALS['db']->autoExecute(DB_PREFIX."user_contact",array('is_delete' => '1'),"UPDATE","id in (".$ids.")");
		}
			
		if(intval($_REQUEST['id']) > 0)
			$data['user_id'] = intval($_REQUEST['id']);
		else
			$data['user_id'] = intval($GLOBALS['user_info']['id']);
			
		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."user_work WHERE user_id=".$data['user_id'])==0){
			//添加
			$GLOBALS['db']->autoExecute(DB_PREFIX."user_work",$data,"INSERT");
		}
		else{
			//编辑
			$GLOBALS['db']->autoExecute(DB_PREFIX."user_work",$data,"UPDATE","user_id=".$data['user_id']);
		}
		
		return showSuccess($GLOBALS['lang']['SAVE_USER_SUCCESS'],isset($_REQUEST['is_ajax']) ? intval($_REQUEST['is_ajax']) : 0);
	}
	/**
	 * 通行证类型
	 */
	public function passporttype(){
		$uid = $GLOBALS['user_info']['id'];
		if(!$uid){
			return app_redirect(url("index"));
		}
		$text = array(
				'1'=>array('1）港澳居民來往內地通行證','2）香港永久性居民身份證','./static/default/images/hk.jpg'),
				'2'=>array('1）港澳居民來往內地通行證','2）澳门永久性居民身份證','./static/default/images/ma.jpg'),
				'3'=>array('1）台灣居民來往大陸通行證（臺胞證）','2）臺灣地區身份證','./static/default/images/tw.jpg'),
		);
		
		$GLOBALS['tmpl']->assign("text",json_encode($text));
		$GLOBALS['tmpl']->display("page/passport_type.html");
	}
	/**
	 * 用户港澳台认证
	 * changlu 2013年12月3日16:34:46
	 */
	public function passport(){
		$uid = $GLOBALS['user_info']['id'];
		$type = getRequestInt("type",1)>3?1:getRequestInt("type",1);
		$type = $type==0?1:$type;
		$conf = array(
				'1'=>array("h"=>H,'pass'=>'<b class="passport-type-initial">H</b>12345678&nbsp;00','id'=>'A123456(B)','name'=>'香港','type'=>$type,'img'=>'./static/default/images/hk.jpg'),
				'2'=>array("h"=>M,'pass'=>'<b class="passport-type-initial">M</b>12345678&nbsp;00','id'=>'1234567(8)','name'=>'澳門','type'=>$type,'img'=>'./static/default/images/ma.jpg'),
				'3'=>array("h"=>'','pass'=>'12345678 01','id'=>'A123456789','name'=>'臺灣','type'=>$type,'passname'=>'通行證內頁','img'=>'./static/default/images/tw.jpg'),
		);
		
		if(!$uid){
			return app_redirect(url("index"));
		}
		$info = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."user_passport WHERE uid=".$uid);

		if($GLOBALS['user_info']['idcardpassed'] == 3){
			return showErr('認證信息提交成功,网信理财將在3個工作日內完成信息審核。審核結果將以短信、站內信或電子郵件等方式通知您。',0);
		}
		
		if($info && $info['status'] == 1){
			return app_redirect(url("index"));
		}

		if($_POST){//存数据
			$data = array();
			$id = getRequestInt('id');
			
			$data['uid']    = $uid;
			$data['name']   = getRequestString('name');
			$data['region'] = getRequestString("region");
			$data['sex']    = getRequestString('sex');
			
			if(getRequestString('type')){
				$data['idno']   = getRequestString('idno').'('.getRequestString('idno_suffix').')';
			}else{//臺灣
				$data['idno']   = getRequestString('idno').getRequestString('idno_suffix');
			}
			
			$data['passportid'] = getRequestString('type').getRequestString('passportid').' '.getRequestString('passportid_suffix');
			$data['valid_date'] = getRequestString('valid_date');
			$data['birthday']   = getRequestString('birthday');
			$data['file'] = serialize($_POST['path']);
			
			if($id){//修改
				$data['utime'] = get_gmtime();
				$condition = 'id = '.$id.' and uid='.$uid;
				$re = $GLOBALS['db']->autoExecute(DB_PREFIX."user_passport",$data,'UPDATE',$condition);
			}else{
				$data['ctime'] = get_gmtime();
				$re = $GLOBALS['db']->autoExecute(DB_PREFIX."user_passport",$data,'INSERT','','SILENT');
			}
			//修改用户表状态
			$GLOBALS['db']->autoExecute(DB_PREFIX."user",array('idcardpassed'=>3),'UPDATE','id='.$uid);
			
			if($re){
				return showSuccess($GLOBALS['lang']['SAVE_USER_SUCCESS'],1);
			}
			return showErr("通行证资料提交失败！",1);
		}else{//显示界面
			$GLOBALS['tmpl']->assign("info",$info);
			$GLOBALS['tmpl']->assign("type",$conf[$type]);
			$GLOBALS['tmpl']->display("page/passport.html");
		}
	}
}
?>
