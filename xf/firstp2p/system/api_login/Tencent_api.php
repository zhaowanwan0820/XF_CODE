<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

$api_lang = array(
	'name'	=>	'腾讯微博登录插件',
	'app_key'	=>	'腾讯API应用APP_KEY',
	'app_secret'	=>	'腾讯API应用APP_SECRET',
);

$config = array(
	'app_key'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //腾讯API应用的KEY值
	'app_secret'	=>	array(
		'INPUT_TYPE'	=>	'0'
	), //腾讯API应用的密码值
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
	if(ACTION_NAME=='install')
	{
		//更新字段
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `tencent_id`  varchar(255) NOT NULL",'SILENT');
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `tencent_app_key`  varchar(255) NOT NULL",'SILENT');
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `tencent_app_secret`  varchar(255) NOT NULL",'SILENT');
		$GLOBALS['db']->query("ALTER TABLE `".DB_PREFIX."user`  ADD COLUMN `is_syn_tencent`  tinyint(1) NOT NULL",'SILENT');
	}
    $module['class_name']    = 'Tencent';

    /* 名称 */
    $module['name']    = $api_lang['name'];

	$module['config'] = $config;
	$module['is_weibo'] = 1;  //可以同步发送微博
	
	$module['lang'] = $api_lang;
    
    return $module;
}

// 腾讯的api登录接口
require_once(APP_ROOT_PATH.'system/libs/api_login.php');
class Tencent_api implements api_login {
	
	private $api;
	
	public function __construct($api)
	{
		$api['config'] = unserialize($api['config']);
		$this->api = $api;
	}
	
	public function get_api_url()
	{
		es_session::start();
		require_once APP_ROOT_PATH.'system/api_login/Tencent/opent.php';
		define( "MB_RETURN_FORMAT" , 'json' );
		define( "MB_API_HOST" , 'open.t.qq.com' );
		
		$o = new MBOpenTOAuth( $this->api['config']['app_key'],$this->api['config']['app_secret'] );
		$keys = $o->getRequestToken(get_domain().APP_ROOT."/api_callback.php?c=Tencent");//这里填上你的回调URL
		$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false,'');
		es_session::set("keys",$keys);
		
		$str = "<a href='".$aurl."' title='".$this->api['name']."'><img src='".$this->api['icon']."' alt='".$this->api['name']."' /></a>&nbsp;";
		return $str;
	}
	
	public function get_big_api_url()
	{
		es_session::start();
		require_once APP_ROOT_PATH.'system/api_login/Tencent/opent.php';
		define( "MB_RETURN_FORMAT" , 'json' );
		define( "MB_API_HOST" , 'open.t.qq.com' );
		
		$o = new MBOpenTOAuth( $this->api['config']['app_key'],$this->api['config']['app_secret'] );
		$keys = $o->getRequestToken(get_domain().APP_ROOT."/api_callback.php?c=Tencent");//这里填上你的回调URL
		$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false,'');
		es_session::set("keys",$keys);
		
		$str = "<a href='".$aurl."' title='".$this->api['name']."'><img src='".$this->api['bicon']."' alt='".$this->api['name']."' /></a>&nbsp;";
		return $str;
	}	
	
	public function get_bind_api_url()
	{
		es_session::start();
		require_once APP_ROOT_PATH.'system/api_login/Tencent/opent.php';
		define( "MB_RETURN_FORMAT" , 'json' );
		define( "MB_API_HOST" , 'open.t.qq.com' );
		
		$o = new MBOpenTOAuth( $this->api['config']['app_key'],$this->api['config']['app_secret'] );
		$keys = $o->getRequestToken(get_domain().APP_ROOT."/api_callback.php?c=Tencent&is_bind=1");//这里填上你的回调URL
		$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false,'');
		es_session::set("keys",$keys);
		
		$str = "<a href='".$aurl."' title='".$this->api['name']."'><img src='".$this->api['bicon']."' alt='".$this->api['name']."' /></a>&nbsp;";
		return $str;
	}		
	public function callback()
	{
		es_session::start();		
		require_once APP_ROOT_PATH.'system/api_login/Tencent/opent.php';
		define( "MB_RETURN_FORMAT" , 'json' );
		define( "MB_API_HOST" , 'open.t.qq.com' );
		
		$is_bind = intval($_REQUEST['is_bind']);
		
		$keys = es_session::get("keys");
		$o = new MBOpenTOAuth( $this->api['config']['app_key'],$this->api['config']['app_secret'] , $keys['oauth_token'] , $keys['oauth_token_secret']  );
		$last_key = $o->getAccessToken(  $_REQUEST['oauth_verifier'] ) ;//获取ACCESSTOKEN
		$tencent_id = $last_key['name'];		

		$msg['field'] = 'tencent_id';
		$msg['id'] = $tencent_id;
		$msg['name'] = $tencent_id;
		$msg['app_key'] = $last_key['oauth_token'];
		$msg['app_secret'] = $last_key['oauth_token_secret'];
		es_session::set("api_user_info",$msg);
		$user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where tencent_id = '".$tencent_id."' and tencent_id <> ''");	
		if($user_data)
		{
				$user_current_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where id = ".intval($user_data['group_id']));
				$user_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where score <=".intval($user_data['score'])." order by score desc");
				if($user_current_group['score']<$user_group['score'])
				{
					$user_data['group_id'] = intval($user_group['id']);
				}
				$GLOBALS['db']->query("update ".DB_PREFIX."user set tencent_app_key ='".$last_key['oauth_token']."',tencent_app_secret = '".$last_key['oauth_token_secret']."', login_ip = '".get_client_ip()."',login_time= ".get_gmtime().",group_id=".intval($user_data['group_id'])." where id =".$user_data['id']);				
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
			$GLOBALS['db']->query("update ".DB_PREFIX."user set tencent_id= '".$tencent_id."', tencent_app_key ='".$last_key['oauth_token']."',tencent_app_secret = '".$last_key['oauth_token_secret']."' where id =".$GLOBALS['user_info']['id']);						
			return app_redirect(url("shop","uc_center#setweibo"));
		}
		else
		return app_redirect(url("shop","user#api_login"));
		
	}
	
	public function get_title()
	{
		return '腾讯api登录接口，需要php_curl扩展的支持';
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
		$user_data['tencent_id'] = $s_api_user_info['id'];
		$user_data['tencent_app_key'] = $s_api_user_info['app_key'];
		$user_data['tencent_app_secret'] = $s_api_user_info['app_secret'];
		
		$count = 0;
		do{
			if($count>0)
			$user_data['user_name'] = $user_data['user_name'].$count;
			if($user_data['tencent_id'])
			$GLOBALS['db']->autoExecute(DB_PREFIX."user",$user_data,"INSERT",'','SILENT');
			$rs = $GLOBALS['db']->insert_id();
			$count++;
		}while(intval($rs)==0&&$user_data['tencent_id']);
		
		$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($rs));
		es_session::set("user_info",$user_info);
		es_session::delete("api_user_info");
	}

	
	public function send_message($data)
	{
		static $client = NULL;
		if($client === NULL)
		{
			define( "MB_RETURN_FORMAT" , 'json' );
			define( "MB_API_HOST" , 'open.t.qq.com' );
			require_once APP_ROOT_PATH.'system/api_login/Tencent/api_client.php';
			$uid = intval($GLOBALS['user_info']['id']);
			$udata = $GLOBALS['db']->getRow("select tencent_app_key,tencent_app_secret from ".DB_PREFIX."user where id = ".$uid);
	
			$client = new MBApiClient( $this->api['config']['app_key'],$this->api['config']['app_secret'],$udata['tencent_app_key'],$udata['tencent_app_secret']);
		}
		
		$p['c'] = $data['content'];
		
		//组装autho类所需的图片参数内容
		if(!empty($data['img']))
		{
			$filename = $data['img'];
			$pic[0] = $this->get_image_mime($filename);
			$pic[1] = reset( explode( '?' , basename( $filename ) ));
			$pic[2] = file_get_contents($filename);
			$p['p'] = $pic;
		}
		
		$p['ip'] = get_client_ip();
		$p['type']	=1;
		
		try
		{
			$msg = $client->postOne($p);
//			echo "success";
//			print_r($msg);
		
		}
		catch(Exception $e)
		{
//			echo "error";
//			print_r($e);
		}
	}
	
    private function get_image_mime( $file )
    {
    	$ext = strtolower(pathinfo( $file , PATHINFO_EXTENSION ));
    	switch( $ext )
    	{
    		case 'jpg':
    		case 'jpeg':
    			$mime = 'image/jpg';
    			break;
    		 	
    		case 'png';
    			$mime = 'image/png';
    			break;
    			
    		case 'gif';
    		default:
    			$mime = 'image/gif';
    			break;    		
    	}
    	return $mime;
    }
}
?>