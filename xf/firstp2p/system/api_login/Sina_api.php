<?php
// +----------------------------------------------------------------------
// | Fanwe 方维订餐小秘书商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

$api_lang = array(
	'name'	=>	'新浪api登录接口',
	'app_key'	=>	'新浪API应用APP_KEY',
	'app_secret'	=>	'新浪API应用APP_SECRET',
);

$config = array(
	'app_key'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //新浪API应用的KEY值
	'app_secret'	=>	array(
		'INPUT_TYPE'	=>	'0'
	), //新浪API应用的密码值
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
	if(ACTION_NAME=='install')
	{
		//更新字段
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `sina_id`  varchar(255) NOT NULL",'SILENT');
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `sina_app_key`  varchar(255) NOT NULL",'SILENT');
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `is_syn_sina`  tinyint(1) NOT NULL",'SILENT');
	}
    $module['class_name']    = 'Sina';

    /* 名称 */
    $module['name']    = $api_lang['name'];

	$module['config'] = $config;
	$module['is_weibo'] = 1;  //可以同步发送微博
	
	$module['lang'] = $api_lang;
    
    return $module;
}

// 新浪的api登录接口
require_once(APP_ROOT_PATH.'system/libs/api_login.php');
class Sina_api implements api_login {
	
	private $api;
	
	public function __construct($api)
	{
		$api['config'] = unserialize($api['config']);
		$this->api = $api;
	}
	
	public function get_api_url()
	{
		require_once APP_ROOT_PATH.'system/api_login/sina/saetv2.ex.class.php';
		$callback=get_domain().APP_ROOT."/api_callback.php?c=Sina";
		$o = new SaeTOAuthV2($this->api['config']['app_key'],$this->api['config']['app_secret']);
		$aurl = $o->getAuthorizeURL( $callback );
				
		$str = "<a href='".$aurl."' title='".$this->api['name']."'><img src='".$this->api['icon']."' alt='".$this->api['name']."' /></a>&nbsp;";
		return $str;
	}
	
	public function get_big_api_url()
	{
		require_once APP_ROOT_PATH.'system/api_login/sina/saetv2.ex.class.php';
		$callback=get_domain().APP_ROOT."/api_callback.php?c=Sina";
		$o = new SaeTOAuthV2($this->api['config']['app_key'],$this->api['config']['app_secret']);
		$aurl = $o->getAuthorizeURL( $callback );	
		
		$str = "<a href='".$aurl."' title='".$this->api['name']."'><img src='".$this->api['bicon']."' alt='".$this->api['name']."' /></a>&nbsp;";
		return $str;
	}	
	
	public function get_bind_api_url()
	{
		require_once APP_ROOT_PATH.'system/api_login/sina/saetv2.ex.class.php';
		$callback=get_domain().APP_ROOT."/api_callback.php?c=Sina&is_bind=1";
		$o = new SaeTOAuthV2($this->api['config']['app_key'],$this->api['config']['app_secret']);
		$aurl = $o->getAuthorizeURL( $callback );		
		
		$str = "<a href='".$aurl."' title='".$this->api['name']."'><img src='".$this->api['bicon']."' alt='".$this->api['name']."' /></a>&nbsp;";
		return $str;
	}	
	
	public function callback()
	{
		require_once APP_ROOT_PATH.'system/api_login/sina/saetv2.ex.class.php';
		es_session::start();
		$callback=get_domain().APP_ROOT."/api_callback.php?c=Sina";
		$o = new SaeTOAuthV2($this->api['config']['app_key'],$this->api['config']['app_secret']);
		if (isset($_REQUEST['code'])) {
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] = $callback;
			try {
				$last_key = $o->getAccessToken( 'code', $keys ) ;
			} catch (OAuthException $e) {
			}
		}		
		if ($last_key) {
			$_SESSION['token'] = $last_key;
			setcookie( 'weibojs_'.$o->client_id, http_build_query($last_key) );
		 }else
		 {
		 	echo "Error:授权失败";
			return false;
		 }
		//$last_key = $o->getAccessToken($_REQUEST['oauth_verifier']) ;
		$is_bind = intval($_REQUEST['is_bind']);

		$c = new SaeTClientV2( $this->api['config']['app_key'], $this->api['config']['app_secret'] , $_SESSION['token']['access_token'] );
	    $ms  = $c->home_timeline(); // done
	    $uid_get = $c->get_uid();
	    $u_id = $uid_get['uid'];

		$msg = $c->show_user_by_id($u_id);
		if ($msg === false || $msg === null){
			echo "Error occured";
			return false;
		}
		if (isset($msg['error_code']) && isset($msg['error'])){
			echo ('Error_code: '.$msg['error_code'].';  Error: '.$msg['error'] );
			return false;
		}
		
		$msg['field'] = 'sina_id';
		$msg['app_key'] = $last_key['access_token'];	
		es_session::set("api_user_info",$msg);
	
		$user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where sina_id = ".intval($msg['id'])." and sina_id <> 0 and is_effect=1 and is_delete=0");	
		if($user_data)
		{
				$user_current_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where id = ".intval($user_data['group_id']));
				$user_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where score <=".intval($user_data['score'])." order by score desc");
				if($user_current_group['score']<$user_group['score'])
				{
					$user_data['group_id'] = intval($user_group['id']);
				}				
				$GLOBALS['db']->query("update ".DB_PREFIX."user set sina_app_key = '".$last_key['access_token']."',login_ip = '".get_client_ip()."',login_time= ".get_gmtime().",group_id=".intval($user_data['group_id'])." where id =".$user_data['id']);				
				//$GLOBALS['db']->query("update ".DB_PREFIX."deal_cart set user_id = ".intval($user_data['id'])." where session_id = '".es_session::id()."'");
				es_session::delete("api_user_info");
				if($is_bind)
				{
					if(intval($user_data['id'])!=intval($GLOBALS['user_info']['id']))
					{
						showErr("该帐号已经被别的会员绑定过，请直接用帐号登录",0,url("shop","uc_center#setweibo"));
					}
					else
					{
						es_session::set("user_info",$user_data);
						return app_redirect(url("shop","uc_center#setweibo"));
					}
				}
				else
				{
					es_session::set("user_info",$user_data);
					app_recirect_preview();
				}
		}
		elseif($is_bind==1&&$GLOBALS['user_info'])
		{
			//当有用户身份且要求绑定时
			$GLOBALS['db']->query("update ".DB_PREFIX."user set sina_id= '".intval($msg['id'])."', sina_app_key ='".$last_key['access_token']."' where id =".$GLOBALS['user_info']['id']);						
			return app_redirect(url("shop","uc_center#setweibo"));
		}
		else
		return app_redirect(url("shop","user#api_login"));
		
	}
	
	public function get_title()
	{
		return '新浪V2api登录接口，需要php_curl扩展的支持';
	}
	
	public function create_user()
	{
		$s_api_user_info = es_session::get("api_user_info");
		$user_data['user_name'] = $s_api_user_info['name'];
		$user_data['user_pwd'] = md5(rand(100000,999999));
		$user_data['create_time'] = get_gmtime();
		$user_data['update_time'] = get_gmtime();
		$user_data['login_ip'] = get_client_ip();
		$user_data['group_id'] = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user_group order by score asc limit 1");
		$user_data['is_effect'] = 1;
		$user_data['sina_id'] = $s_api_user_info['id'];
		$user_data['sina_app_key'] = $s_api_user_info['app_key'];
		$count = 0;
		do{
			if($count>0)
			$user_data['user_name'] = $user_data['user_name'].$count;
			if(intval($user_data['sina_id'])>0)
			$GLOBALS['db']->autoExecute(DB_PREFIX."user",$user_data,"INSERT",'','SILENT');
			$rs = $GLOBALS['db']->insert_id();
			$count++;
		}while(intval($rs)==0&&intval($user_data['sina_id'])>0);
		
		$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($rs));
		es_session::set("user_info",$user_info);
		es_session::delete("api_user_info");
	}
	
	
	//同步发表到新浪微博
	public function send_message($data)
	{
		static $client = NULL;
		if($client === NULL)
		{
			require_once APP_ROOT_PATH.'system/api_login/sina/saetv2.ex.class.php';
			$uid = intval($GLOBALS['user_info']['id']);
			$udata = $GLOBALS['db']->getRow("select sina_app_key from ".DB_PREFIX."user where id = ".$uid);
			$client = new SaeTClientV2( $this->api['config']['app_key'], $this->api['config']['app_secret'] , $udata['sina_app_key'] );
		}
		try
		{
			if(empty($data['img']))
				$msg = $client->update($data['content']);
			else
				$msg = $client->upload($data['content'],$data['img']);
//				echo "success";
//				print_r($msg);

		}
		catch(Exception $e)
		{
//			echo "error";
//			print_r($e);
		}
	}
	
}
?>