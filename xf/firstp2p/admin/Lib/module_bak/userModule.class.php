<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
FP::import("app.deal");
use system\libs\OAuth;

class userModule extends SiteBaseModule
{
    
    /**
     * 注册
     * @param int $sem 1跳转到sem页
     */
	public function register($sem=0)
	{
            // oauth新旧开关
            $new_oauth_switch = $GLOBALS['sys_config']['NEW_OAUTH_SWITCH'];
	        /* 使用oauth注册 */
	        if( $GLOBALS['sys_config']['IS_OAUTH_AUTH'] )
	        {
	            switch($new_oauth_switch){
	            	case 2:
	            	    $LANG['ERROR_TITLE']='系统维护';
                            $GLOBALS['tmpl']->assign("LANG",$LANG);
                            $GLOBALS['tmpl']->assign("msg","尊敬的用户<br />系统正在进行升级，期间将无法登录和注册，请您稍后再试。"
                                    ."给您带来不便，敬请谅解！");
                            $GLOBALS['tmpl']->display("error.html");
                            exit;
                            break;
	            	case 3:
	            	case 1:
	            	default:
                        $callback = $GLOBALS['sys_config']["OAUTH_REDIRECT_URI"];
	            	    $toUrl = $GLOBALS['sys_config']['NEW_AUTO_REGISTER_API_URL']. '?client_id=' 
                                    . $GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"].'&redirect_uri=' 
                                    . urlencode($callback).'&response_type=code';
	            	     
	            	    if($sem===1){
	            	        $url=get_http().'www.firstp2p.com/';
	            	        $toUrl .= urlencode($url).'&oauthreg=1&' . $_SERVER['QUERY_STRING'].'&sem=1';
	            	    }
	            	    else
	            	    {
	            	        $toUrl.=urlencode($url).'&oauthreg=1&' . $_SERVER['QUERY_STRING'];
	            	    }
	            	    header('Location:' . $toUrl);
	            	    exit;
                            break;
	            }
	        }
	    
		$GLOBALS['tmpl']->caching = true;
		$cache_id  = md5(MODULE_NAME.ACTION_NAME.$GLOBALS['deal_city']['id']);		
		if (!$GLOBALS['tmpl']->is_cached('user_register.html', $cache_id))	
		{
			 
			$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['USER_REGISTER']);
			
			$field_list =load_auto_cache("user_field_list");
		
			$api_uinfo = es_session::get("api_user_info");
			$GLOBALS['tmpl']->assign("reg_name",$api_uinfo['name']);
			
			$GLOBALS['tmpl']->assign("field_list",$field_list);
		}
		$GLOBALS['tmpl']->display("user_register.html",$cache_id);
	}
    
    public function registersem()
    {
        $this->register(1);
    }
	
	public function doregister()
	{
		//验证码
		if(app_conf("VERIFY_IMAGE")==1)
		{
			$verify = md5(trim($_REQUEST['verify']));
			$session_verify = es_session::get('verify');
			if($verify!=$session_verify)
			{				
				return showErr($GLOBALS['lang']['VERIFY_CODE_ERROR'],0,url("shop","user#register"));
			}
		}
		
		FP::import("libs.libs.user");
		$user_data = $_POST;
		
		foreach($user_data as $k=>$v)
		{
			$user_data[$k] = htmlspecialchars(addslashes($v));
		}
		
		if(trim($user_data['user_pwd'])!=trim($user_data['user_pwd_confirm']))
		{
			return showErr($GLOBALS['lang']['USER_PWD_CONFIRM_ERROR']);
		}
		if(trim($user_data['user_pwd'])=='')
		{
			return showErr($GLOBALS['lang']['USER_PWD_ERROR']);
		}
		
		$user_data['pid'] = $GLOBALS['ref_uid'];
		
		
		$res = save_user($user_data);
	
		if($_REQUEST['subscribe']==1)
		{
			//订阅
			if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mail_list where mail_address = '".$user_data['email']."'")==0)
			{
				$mail_item['city_id'] = intval($_REQUEST['city_id']);
				$mail_item['mail_address'] = $user_data['email'];
				$mail_item['is_effect'] = app_conf("USER_VERIFY");
				$GLOBALS['db']->autoExecute(DB_PREFIX."mail_list",$mail_item,'INSERT','','SILENT');
			}
			if($user_data['mobile']!=''&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_list where mobile = '".$user_data['mobile']."'")==0)
			{
				$mobile['city_id'] = intval($_REQUEST['city_id']);
				$mobile['mobile'] = $user_data['mobile'];
				$mobile['is_effect'] = app_conf("USER_VERIFY");
				$GLOBALS['db']->autoExecute(DB_PREFIX."mobile_list",$mobile,'INSERT','','SILENT');
			}
		}
		if($res['status'] == 1)
		{
			$user_id = intval($res['data']);
			//更新来路
			$GLOBALS['db']->query("update ".DB_PREFIX."user set referer = '".$GLOBALS['referer']."' where id = ".$user_id);
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
			if($user_info['is_effect']==1)
			{
				//在此自动登录
				do_login_user($user_data['email'],$user_data['user_pwd']);
				//原来为直接挑战 现改为 完善资料
				return showSuccess($GLOBALS['lang']['REGISTER_SUCCESS'],0,APP_ROOT."/");
				//return app_redirect(url("shop","user#stepone"));
			}
			else
			{
				if(app_conf("MAIL_ON")==1)
				{
					//发邮件
					send_user_verify_mail($user_id);
					$user_email = $GLOBALS['db']->getOne("select email from ".DB_PREFIX."user where id =".$user_id);
					//开始关于跳转地址的解析
					$domain = explode("@",$user_email);
					$domain = $domain[1];
					$gocheck_url = '';
					switch($domain)
					{
						case '163.com':
							$gocheck_url = 'http://mail.163.com';
							break;
						case '126.com':
							$gocheck_url = 'http://www.126.com';
							break;
						case 'sina.com':
							$gocheck_url = 'http://mail.sina.com';
							break;
						case 'sina.com.cn':
							$gocheck_url = 'http://mail.sina.com.cn';
							break;
						case 'sina.cn':
							$gocheck_url = 'http://mail.sina.cn';
							break;
						case 'qq.com':
							$gocheck_url = 'http://mail.qq.com';
							break;
						case 'foxmail.com':
							$gocheck_url = 'http://mail.foxmail.com';
							break;
						case 'gmail.com':
							$gocheck_url = 'http://www.gmail.com';
							break;
						case 'yahoo.com':
							$gocheck_url = 'http://mail.yahoo.com';
							break;
						case 'yahoo.com.cn':
							$gocheck_url = 'http://mail.cn.yahoo.com';
							break;
						case 'hotmail.com':
							$gocheck_url = 'http://www.hotmail.com';
							break;
						default:
							$gocheck_url = "";
							break;					
					}

					 
					$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['REGISTER_MAIL_SEND_SUCCESS']);
					$GLOBALS['tmpl']->assign("user_email",$user_email);
					$GLOBALS['tmpl']->assign("gocheck_url",$gocheck_url);
					//end 
					$GLOBALS['tmpl']->display("user_register_email.html");
				}
				else
				return showSuccess($GLOBALS['lang']['WAIT_VERIFY_USER'],0,APP_ROOT."/");
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
			return showErr($error_msg);
		}
	}
	
	
	public function login()
	{
           
            $login_info = es_session::get("user_info");
            if($login_info)
            {
                    return app_redirect(url("index"));		
            }
            
            /* 使用oauth登录 */
            if ($GLOBALS['sys_config']['IS_OAUTH_AUTH']) {                
                if($GLOBALS['sys_config']['NEW_OAUTH_SWITCH']=='2')
                {
                    $LANG['ERROR_TITLE']='系统维护';
                    $GLOBALS['tmpl']->assign("LANG",$LANG);
                    $GLOBALS['tmpl']->assign("msg","尊敬的用户<br />系统正在进行升级，期间将无法登录和注册，请稍后再试。"
                            ."给您带来不便，敬请谅解！");
                    $GLOBALS['tmpl']->display("error.html");
                    exit;
                }
                else
                {
                    //使用新OAuth
                    $url_before=es_session::get('before_login');
                    
                    if(empty($url_before))
                    {                        
                        $url_before = $_SERVER['HTTP_REFERER'];
                    }
                    else
                    {
                        $url_before = get_http().$_SERVER["HTTP_HOST"].es_session::get('before_login');                      
                    }
                    
                    $referer = empty($url_before) ? get_http() . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'] : $url_before ;
                    $url = str_replace("&e=1", "", $referer);
                    if(stripos($url, 'oauth.9888.com') !== false){
                            $url = '';
                    }
                    if(strlen($url)>255){
                            $url = '';
                    }                    

                    $toUrl   = $GLOBALS['sys_config']['NEW_OAUTH_AUTH_URL'] . 'oauthserver_firstp2p/firstp2p/login/get.do?response_type=code&client_id=' . $GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"] . '&redirect_uri=' . urlencode($GLOBALS['sys_config']["OAUTH_REDIRECT_URI"].'?state='.urlencode(urlencode($url)));
                    header('Location:' . $toUrl);
                    exit;
                }
            }

            $GLOBALS['tmpl']->caching = true;
            $cache_id  = md5(MODULE_NAME.ACTION_NAME.$GLOBALS['deal_city']['id']);		
            if (!$GLOBALS['tmpl']->is_cached('user_login.html', $cache_id))	
            {
                    $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['USER_LOGIN']);
                    $GLOBALS['tmpl']->assign("CREATE_TIP",$GLOBALS['lang']['REGISTER']);

            }
            $GLOBALS['tmpl']->display("user_login.html",$cache_id);
	}
	
	
	/* oauth登录回调使用  */
	public function loginCallback()
	{
	        /*
	            使用 http://lc.firstp2p.com/user-register?love=you 跳到oauth注册后在 loginCallback() 方式里 print_r($_GET) 输出如下： 
	            Array ( [state] => http://lc.firstp2p.com/user-register?love=you [code] => e33638bb5decd4c6d73a798015e6204e [ctl] => user [act] => loginCallback [city] => lcfirstp2pcom ) 
	        */
	        if(isset($_GET['error']))
		{

		        return showErr('登录失败，有错误信息输出' , 0, url("shop", 'user#login') );
		}
		if(empty($_GET['code']))
		{
		        return showErr('登录失败，用户名或密码错误！' , 0, url("shop","user#login") );
		}
		
                if($GLOBALS['sys_config']['NEW_OAUTH_SWITCH']=='2' )
                {
                    $LANG['ERROR_TITLE']='系统维护';
                    $GLOBALS['tmpl']->assign("LANG",$LANG);
                    $GLOBALS['tmpl']->assign("msg","尊敬的用户<br />系统正在进行升级，期间将无法登录和注册，请稍后再试。"
                            ."给您带来不便，敬请谅解！");
                    $GLOBALS['tmpl']->display("error.html");
                    exit;
                }
                else
                {
                    $userInfo  = $this->getUserInfo($_GET['code']);             
                }
                #print_r($_SESSION);
//                $userInfo  = $this->getUserInfo();
                $_SESSION['oauth_id'] = $userInfo['id'];
                if( !empty($userInfo) )
                {
                    FP::import("libs.libs.user");

                    #if($_GET['state']) $this->redirect($_GET['state']);
                    #$this->redirect(U('Home/Index/Index'));
                    #echo('<h1>登录成功！</h1><pre>');
                    #print_r($userInfo);

                    if( stripos($_GET['state'], 'user-register') !== false )
                        $jump_url = get_gopreview();
                    else
                        $jump_url = $_GET['state'];

                    if(stripos($jump_url, 'firstp2p_re_password') !== false){
                            $jump_url = '';
                    }
                    if(stripos($jump_url, 're_password') !== false){
                            $jump_url = '';
                    }
                    if(stripos($jump_url, 'do_forget_password') !== false){
                            $jump_url = '';
                    }       

                    $this->updateUserInfo($userInfo);
                    #$res = do_login_user($userInfo['user_login_name'], '');

                    // 记录日志文件
                    FP::import("libs.utils.logger");
                    $log = array(
                        'type' => 'oauth',
                        'user_name' => $userInfo['user_name'],
                        'user_login_name' => $userInfo['user_login_name'],
                        'path' =>  __FILE__,
                        'function' => 'loginCallback',
                        'msg' => 'oauth登录成功.',
                        'time' => time(),
                    );
                    logger::wLog($log);

                    // 设置用户名和密码给 dologin() 使用
                    $_POST['email'] = $userInfo['user_login_name'];
                    $_POST['user_pwd'] = '';
                    $_POST['oauth'] = 1;
                    $this->dologin($jump_url,$userInfo['passport_id']);
                }
	}
    
    private function _parseUrlParam($query){
        $queryArr = explode('&', $query);
        $params = array();
        if($queryArr[0] !== ''){
            foreach( $queryArr as $param ){
                list($name, $value) = explode('=', $param);
                $params[urldecode($name)] = urldecode($value);
            }       
        }
        return $params;
    }
    
    /**
     * 修改贷款保证人邀请信息
     * @author zhang ruoshi
     * @param string $icode 邀请码，base62encode对guarantor id编码后的结果
     * @param int 0失败，deal_id成功
     */
    private function _guarantor(){
        //贷款保证人
        if(isset($_GET['state'])){
        	
        		#echo substr($_GET['state'], $pos);
	            $pu = parse_url($_GET['state']);
	            #print_r($pu);
	            $params = $this->_parseUrlParam($pu['query']);
	            //var_dump($params);
	            #exit;
	            
	            $icode = $params['icode'];
        }
        
        
        if(empty($icode)) return 0;
        $guarantor_id = base62decode($icode);
        
        //贷款保证人信息
        $guarantor = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE id=".$guarantor_id." and status=0");
        
        if(empty($guarantor)) return 0;
        
        //手机地址必须一致
        if($guarantor['mobile']!=$GLOBALS['user_info']['mobile'])
            return 0;
        
        //同步贷款保证人信息到user表
        $user['real_name'] = $guarantor['name'];
        $user['mobile'] = $guarantor['mobile'];
        //$user['idno'] = $guarantor['id_number'];
        $condition = 'id = '.$GLOBALS['user_info']['id'];
        $GLOBALS['db']->autoExecute(DB_PREFIX."user",$user,'UPDATE',$condition);
        
        //绑定保证人id
        $data['to_user_id'] = $GLOBALS['user_info']['id'];
        $data['status'] = 1;
        $condition = 'id = '.$guarantor['id'];
        $r =$GLOBALS['db']->autoExecute(DB_PREFIX."deal_guarantor",$data,'UPDATE',$condition);
    
        //发送确认站内信
        $this->send_guarantor_msg($guarantor,$GLOBALS['user_info']['id']);
        
        if($r){
            //$user_info_data = $GLOBALS['user_info'];
            //$user_info_data['guarantor_deal_id'] = $guarantor['deal_id'];//未同意的贷款保证邀请
            
            es_session::set("guarantor",$guarantor);//，在app_init.php中处理跳转 
            
            /*
            $redirect = url("index","deal",array("id"=>$guarantor['deal_id']));
            echo $redirect;
            exit;
            return app_redirect($redirect);
            */
            return $guarantor['deal_id'];
        }
        return 0;
    }

    /**
     * 给保证人发站内信息
     * @author Liwei
     * @param string $guarantor 担保人信息 $guarantor_id 担保人ID
     */
    private function send_guarantor_msg($guarantor, $guarantor_id){
        if(empty($guarantor)) return false;
        if(empty($guarantor_id)) return false;
        $deal_info = get_deal($guarantor['deal_id']);
        if(empty($deal_info)) return false;
        $borrow_user_info = get_user_info($deal_info['user_id'],true);
        $content = "<p>用户 {$borrow_user_info['real_name']} 发布的贷款申请“<a href=\"".$deal_info['url']."\">".$deal_info['name']."</a>”将您列为贷款担保人！";
        send_user_msg("担保确认",$content,0,$guarantor_id,get_gmtime(),0,true,1);
    }
        
	/**
	 * oauth 通过token获取用户信息
	 *
	 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
	 * @since      File available since Release 1.0 -- 2012-11-13 下午01:27:38
	 * @param	   string code   认证码
	 * @return	   array 返回用户信息
	 * @author	   Zheng Yi Wireless
	 */
	private function getUserInfo($code='')
	{
                //新OAuth
                FP::import('libs.libs.oauth');
                $o=new OAuth($GLOBALS['sys_config']['NEW_OAUTH_API_URL'].'oauthserver_firstp2p'
                        ,$GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"]);
                $ret=$o->getUserInfo($code);
                
                if($ret && isset($ret['username']) && !empty($ret['username']))
                {
                    return array(
                        'id'=>$ret['id'],
                        'user_login_name'=>$ret['username'],
                        'passport_id'=>$ret['passportid'],
                        'user_email'=>$ret['email'],
                        'user_name'=>$ret['telephone']
                    );
                }
                else
                {
                    // 记录日志文件
                    $reinfo = str_replace("\n", "", print_r($ret, true) );
                    FP::import("libs.utils.logger");
                    $log = array(
                        'type' => 'oauth-login-fail',
                        'result' => 'getuserinfo-fail',
                        'url' => 'user-callback',
                        'info' => $reinfo,
                        'path' =>  __FILE__,
                        'function' => 'getUserInfo',
                        'msg' => '获取用户信息失败.',
                        'time' => time(),
                    );
                    logger::wLog($log);

                    session_unset();
                    session_destroy();

                    return showErr('获取用户信息失败，请重试');
                    
                }       
            
	}
 	
	private  function getToken($code)
	{
		$posturl = 'grant_type=authorization_code&scope=basic&code='.$code.'&redirect_uri='. $GLOBALS['sys_config']['OAUTH_REDIRECT_URI'] .'&client_id='.$GLOBALS['sys_config']['OAUTH_CLIENT_ID'].'&client_secret='.$GLOBALS['sys_config']['OAUTH_CLIENT_SECRET'];	
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $GLOBALS['sys_config']['OAUTH_AUTH_URL'].'oauth2/token.php');
		curl_setopt($ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_HEADER, 0 ) ;
		if(get_http() == 'https://'){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $posturl);
		$data = curl_exec($ch);
		curl_close($ch); 
		return $data;
	}
	
	
	/* 同步oauth用户信息到本地 */
	private function updateUserInfo($info)
	{         
            
	        FP::import("libs.libs.user");
	        #if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where user_name = '".trim($user_data['user_name'])."' and id <> ".intval($user_data['id']))>0)
	        
	        $data['user_name'] = $info['user_login_name'];
	        $data['email'] = $info['user_email'];
	        $data['mobile'] = $info['user_name'];                
		if(isset($info['passport_id']))$data['passport_id'] = $info['passport_id'];
                
	        $data['oauth'] = 1;
	        $res = save_user($data);
                if($res['status']!='1')
                {
                    $_SERVER['HTTP_REFERER']=get_http().$_SERVER["HTTP_HOST"].url('index');
                    $jump = get_http().$_SERVER["HTTP_HOST"].url("shop","user#loginout");  
                    return showErr('未知错误 - #'.$res['data']['error'].' #'.$res['data']['field_name'],0,$jump);                    
                }
	        
	}
	
	/**
	 * 同步退出
	 * 
	 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
	 * @since      File available since Release 1.0 -- 2012-11-13 下午01:30:55
	 * @author	   Zheng Yi Wireless
	 */
	public function oauthLogout($url, $return = 0)
	{
            if(stripos($url, 'oauth.9888.com') !== false){
                $url = '';
            }   
            //新OAuth
            $to = $GLOBALS['sys_config']['NEW_OAUTH_LOGOUT_URL'] .'?r='.rand(0,1000).'&response_type=code&client_id='
                    .$GLOBALS['sys_config']['NEW_OAUTH_CLIENT_ID'].'&redirect_uri='. $url;

            if($return)
                return $to;

            header("Location:" . $to);
            exit;
	}
	
	public function api_login()
	{		
		$s_api_user_info = es_session::get("api_user_info");
		if($s_api_user_info)
		{
			 
			$GLOBALS['tmpl']->assign("page_title",$s_api_user_info['name'].$GLOBALS['lang']['HELLO'].",".$GLOBALS['lang']['USER_LOGIN_BIND']);
			$GLOBALS['tmpl']->assign("CREATE_TIP",$GLOBALS['lang']['REGISTER_BIND']);
			$GLOBALS['tmpl']->assign("api_callback",true);
			$GLOBALS['tmpl']->display("user_login.html");
		}
		else
		{
			return showErr($GLOBALS['lang']['INVALID_VISIT']);
		}
	}	
	public function dologin($backurl = '',$passport_id='')
	{
		$ajax = intval($_REQUEST['ajax']);
		$oauth = $_POST['oauth'] ? $_POST['oauth'] : 0;
		//验证码
		if( !$oauth  &&  app_conf("VERIFY_IMAGE")==1 )
		{
			$verify = md5(trim($_REQUEST['vdcode']));
			$session_verify = es_session::get('verify');
			if($verify!=$session_verify)
			{
				return showErr($GLOBALS['lang']['VERIFY_CODE_ERROR'],$ajax,url("shop","user#login"));
			}
		}
		
		FP::import("libs.libs.user");
		
		/*
		if(check_ipop_limit(get_client_ip(),"user_dologin",intval(app_conf("SUBMIT_DELAY"))))
		{
			$result = do_login_user($_POST['email'],$_POST['user_pwd'], $oauth);
                        $deal_id = $this->_guarantor();//写入贷款担保人数据
		}
		else
		{
			showErr($GLOBALS['lang']['SUBMIT_TOO_FAST'],$ajax, url("index"));
		}
		*/
		$username = empty($passport_id)?'-1':$passport_id;
		$result = do_login_user($username,$_POST['user_pwd'], $oauth);
        $deal_id = $this->_guarantor();//写入贷款担保人数据
		if($result['status'])
		{	
		        
			$s_user_info = es_session::get("user_info");
			if(intval($_POST['auto_login'])==1)
			{
				//自动登录，保存cookie
				$user_data = $s_user_info;
				es_cookie::set("user_name",$user_data['email'],3600*24*30);			
				es_cookie::set("user_pwd",md5($user_data['user_pwd']."_EASE_COOKIE"),3600*24*30);
			}
			if($ajax==0&&trim(app_conf("INTEGRATE_CODE"))=='')
			{
				#$redirect = $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:url("index");
				
				if($backurl)
				    return app_redirect($backurl);
				else
				{
				    $redirect = url("index");
                                    return app_redirect($redirect);
                                }
			}
			else
			{	
				$jump_url = $backurl  ? $backurl  : get_gopreview();
				
				
				if($ajax==1)
				{
					$return['status'] = 1;
					$return['info'] = $GLOBALS['lang']['LOGIN_SUCCESS'];
					$return['data'] = $result['msg'];
					$return['jump'] = $jump_url;
					return ajax_return($return);
				}
				else
				{
					$GLOBALS['tmpl']->assign('integrate_result',$result['msg']);					
					return showSuccess($GLOBALS['lang']['LOGIN_SUCCESS'],$ajax,$jump_url);
				}
			}
			
		}
		else
		{
			if($result['data'] == ACCOUNT_NO_EXIST_ERROR)
			{
				$err = $GLOBALS['lang']['USER_NOT_EXIST'];
			}
			if($result['data'] == ACCOUNT_PASSWORD_ERROR)
			{
				$err = $GLOBALS['lang']['PASSWORD_ERROR'];
			}
			if($result['data'] == ACCOUNT_NO_VERIFY_ERROR)
			{
				$err = $GLOBALS['lang']['USER_NOT_VERIFY'];
				if(app_conf("MAIL_ON")==1&&$ajax==0)
				{				
					$GLOBALS['tmpl']->assign("page_title",$err);
					$GLOBALS['tmpl']->assign("user_info",$result['user']);
					$GLOBALS['tmpl']->display("verify_user.html");
					exit;
				}
				
			}
			FP::import("libs.utils.logger");
			$log = array(
			        'type' => 'oauth-error',
			        'result' => $result,
			        'url' =>'login-callback',
			        'info' => $err,
			        'path' =>  __FILE__,
			        'function' => 'getUserInfo',
			        'msg' => '获取用户信息失败.',
			        'time' => time(),
			);
			logger::wLog($log);
				
			return showErr($err,$ajax);
		}
	}
	
	
	
	public function stepone(){
		if(intval($GLOBALS['user_info']['id'])==0)
		{
			es_session::set('before_login',$_SERVER['REQUEST_URI']);
			return app_redirect(url("shop","user#login"));
		}
		
		//扩展字段
		$field_list =load_auto_cache("user_field_list");
		
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
		
		$GLOBALS['db']->query("update ".DB_PREFIX."user set `step` = 1 where id = ".intval($GLOBALS['user_info']['id']));
		$user_info = es_session::get("user_info");
		$user_info['step'] = 1;
		es_session::set('user_info',$user_info);
		
    $GLOBALS['tmpl']->assign("agrant_id",101);
		$GLOBALS['tmpl']->display("user_step_one.html");
		exit;
	}
	public function steptwo(){
		if(intval($GLOBALS['user_info']['id'])==0)
		{
			es_session::set('before_login',$_SERVER['REQUEST_URI']);
			return app_redirect(url("shop","user#login"));
		}
    $GLOBALS['tmpl']->assign("agrant_id",200);
		$GLOBALS['tmpl']->display("user_step_two.html");
		exit;
	}
	public function stepthree(){
		if(intval($GLOBALS['user_info']['id'])==0)
		{
			es_session::set('before_login',$_SERVER['REQUEST_URI']);
			return app_redirect(url("shop","user#login"));
		}
		
		//获取会员列表
		$user_list = get_rand_user(24,0,intval($GLOBALS['user_info']['id']));
		foreach($user_list as $k => $v){
			$user_list[$k]['province'] = $GLOBALS['db']->getOne("SELECT `name` FROM ".DB_PREFIX."region_conf WHERE id ='".intval($v['province_id'])."' ");
			$user_list[$k]['city'] = $GLOBALS['db']->getOne("SELECT `name` FROM ".DB_PREFIX."region_conf WHERE id ='".intval($v['city_id'])."' ");
		}
		$GLOBALS['tmpl']->assign("user_list",$user_list);
		$GLOBALS['tmpl']->display("user_step_three.html");
		exit;
	}
	
	public function stepsave(){
		if(intval($GLOBALS['user_info']['id'])==0)
		{
			es_session::set('before_login',$_SERVER['REQUEST_URI']);
			return app_redirect(url("shop","user#login"));
		}
		$user_id=intval($GLOBALS['user_info']['id']);
		$focus_list = explode(",",$_REQUEST['user_ids']);
		foreach($focus_list as $k=>$focus_uid)
		{
			if(intval($focus_uid) > 0){
				$focus_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_focus where focus_user_id = ".$user_id." and focused_user_id = ".intval($focus_uid));
				if(!$focus_data)
				{
						$focused_user_name = $GLOBALS['db']->getOne("select user_name from ".DB_PREFIX."user where id = ".$focus_uid);
						$focus_data = array();
						$focus_data['focus_user_id'] = $user_id;
						$focus_data['focused_user_id'] = $focus_uid;
						$focus_data['focus_user_name'] = $GLOBALS['user_info']['user_name'];
						$focus_data['focused_user_name'] = $focused_user_name;
						$GLOBALS['db']->autoExecute(DB_PREFIX."user_focus",$focus_data,"INSERT");
						$GLOBALS['db']->query("update ".DB_PREFIX."user set focus_count = focus_count + 1 where id = ".$user_id);
						$GLOBALS['db']->query("update ".DB_PREFIX."user set focused_count = focused_count + 1 where id = ".$focus_uid);
				}
			}
		}		
		#showSuccess($GLOBALS['lang']['REGISTER_SUCCESS'],0,url("shop","uc_center"));
		return showSuccess($GLOBALS['lang']['REGISTER_SUCCESS'],0,url("index"));
	}
	
	public function loginout()
	{
		FP::import("libs.libs.user");
		$result = loginout_user();
		
		if($result['status'] || true)
		{
			$s_user_info = es_session::get("user_info");
			es_cookie::delete("user_name");
			es_cookie::delete("user_pwd");
			es_session::set('before_login','');
			
			$GLOBALS['tmpl']->assign('integrate_result',$result['msg']);
			$before_loginout = $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:get_http().$_SERVER["HTTP_HOST"].url("index");
			
			if( $GLOBALS['sys_config']['IS_OAUTH_AUTH'] )
			    $this->oauthLogout($before_loginout);
			
			if(trim(app_conf("INTEGRATE_CODE"))=='')
			{
				return app_redirect($before_loginout);
			}
			else
			{
			    return showSuccess($GLOBALS['lang']['LOGINOUT_SUCCESS'],0,$before_loginout);
			}
		}
		else
		{
		        
			return app_redirect(url("index"));		
		}
	}
	
	public function getpassword()
	{
		$GLOBALS['tmpl']->caching = true;
		$cache_id  = md5(MODULE_NAME.ACTION_NAME.$GLOBALS['deal_city']['id']);		
		if (!$GLOBALS['tmpl']->is_cached('user_get_password.html', $cache_id))	
		{
			 
			$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['GET_PASSWORD_BACK']);
		}
		$GLOBALS['tmpl']->display("user_get_password.html",$cache_id);
	}
	
	public function send_password()
	{
		$email = addslashes(trim($_REQUEST['email']));
		if(!check_email($email))
		{
			return showErr($GLOBALS['lang']['MAIL_FORMAT_ERROR']);
		}
		elseif($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where email ='".$email."'") == 0)
		{
			return showErr($GLOBALS['lang']['NO_THIS_MAIL']);
		}
		else 
		{
			$user_info = $GLOBALS['db']->getRow('select * from '.DB_PREFIX."user where email='".$email."'");
			send_user_password_mail($user_info['id']);
			return showSuccess($GLOBALS['lang']['SEND_HAS_SUCCESS']);
		}
		$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['GET_PASSWORD_BACK']);
		$GLOBALS['tmpl']->display("user_get_password.html");
	}
	
	public function modify_password()
	{
		 
		$id = intval($_REQUEST['id']);
		$user_info  = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
		if(!$user_info)
		{
			return showErr($GLOBALS['lang']['NO_THIS_USER']);
		}
		$verify = $_REQUEST['code'];
		if($user_info['password_verify'] == $verify&&$user_info['password_verify']!='')
		{
			//成功	
			$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['SET_NEW_PASSWORD']);				
			$GLOBALS['tmpl']->assign("user_info",$user_info);
			$GLOBALS['tmpl']->display("user_modify_password.html");
		}
		else
		{
			return showErr($GLOBALS['lang']['VERIFY_FAILED'],0,APP_ROOT."/");
		}	
	}
	
	public function do_modify_password()
	{
		$id = intval($_REQUEST['id']);
		$user_info  = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
		if(!$user_info)
		{
			return showErr($GLOBALS['lang']['NO_THIS_USER']);
		}
		$verify = $_REQUEST['code'];
		if($user_info['password_verify'] == $verify&&$user_info['password_verify']!='')
		{
			if(trim($_REQUEST['user_pwd'])!=trim($_REQUEST['user_pwd_confirm']))
			{
				return showErr($GLOBALS['lang']['PASSWORD_VERIFY_FAILED']);
			}
			else
			{			
				$password = addslashes(trim($_REQUEST['user_pwd']));
				$user_info['user_pwd'] = $password;
				$password = md5($password.$user_info['code']);
				$result = 1;  //初始为1
				//载入会员整合
				$integrate_code = trim(app_conf("INTEGRATE_CODE"));
				if($integrate_code!='')
				{
					$integrate_file = APP_ROOT_PATH."system/integrate/".$integrate_code."_integrate.php";
					if(file_exists($integrate_file))
					{
						require_once $integrate_file;
						$integrate_class = $integrate_code."_integrate";
						$integrate_obj = new $integrate_class;
					}	
				}
				
				if($integrate_obj)
				{
					$result = $integrate_obj->edit_user($user_info,$user_info['user_pwd']);				
				}
				if($result>0)
				{
					$GLOBALS['db']->query("update ".DB_PREFIX."user set user_pwd = '".$password."',password_verify='' where id = ".$user_info['id'] );
					return showSuccess($GLOBALS['lang']['NEW_PWD_SET_SUCCESS'],0,APP_ROOT."/");
				}
				else
				{
					return showErr($GLOBALS['lang']['NEW_PWD_SET_FAILED']);
				}
			}
			//成功	
			$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['SET_NEW_PASSWORD']);				
			$GLOBALS['tmpl']->assign("user_info",$user_info);
			$GLOBALS['tmpl']->display("user_modify_password.html");
		}
		else
		{
			return showErr($GLOBALS['lang']['VERIFY_FAILED'],0,APP_ROOT."/");
		}	
	}
	
	public function send()
	{
		$id = intval($_REQUEST['id']);
		$user_info  = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
		if(!$user_info)
		{
			return showErr($GLOBALS['lang']['NO_THIS_USER']);
		}
		if($user_info['is_effect']==1)
		{
			return showErr($GLOBALS['lang']['HAS_VERIFIED']);
		}
		send_user_verify_mail($user_info['id']);
		return showSuccess($GLOBALS['lang']['SEND_HAS_SUCCESS'],0,APP_ROOT."/");	
	}
	
	public function verify()
	{
		$id = intval($_REQUEST['id']);
		$user_info  = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
		if(!$user_info)
		{
			return showErr($GLOBALS['lang']['NO_THIS_USER']);
		}
		$verify = addslashes(trim($_REQUEST['code']));
		if($user_info['verify']!=''&&$user_info['verify'] == $verify)
		{
			//成功
			es_session::set("user_info",$user_info);
			$GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."',login_time= ".get_gmtime().",verify = '',is_effect = 1 where id =".$user_info['id']);
			$GLOBALS['db']->query("update ".DB_PREFIX."mail_list set is_effect = 1 where mail_address ='".$user_info['email']."'");	
			$GLOBALS['db']->query("update ".DB_PREFIX."mobile_list set is_effect = 1 where mobile ='".$user_info['mobile']."'");								
			return showSuccess($GLOBALS['lang']['VERIFY_SUCCESS'],0,get_gopreview());
		}
		elseif($user_info['verify']=='')
		{
			return showErr($GLOBALS['lang']['HAS_VERIFIED'],0,get_gopreview());
		}
		else
		{
			return showErr($GLOBALS['lang']['VERIFY_FAILED'],0,get_gopreview());
		}
	}
	
	public function api_create()
	{
		$s_api_user_info = es_session::get("api_user_info");
		if($s_api_user_info)
		{
			if($s_api_user_info['field'])
			{
				$module = str_replace("_id","",$s_api_user_info['field']);
				$module = strtoupper(substr($module,0,1)).substr($module,1);
				FP::import("libs.api_login.".$module."_api");
				$class = $module."_api";
				$obj = new $class();
				$obj->create_user();
				return app_redirect(APP_ROOT."/");
				exit;
			}			
			return showErr($GLOBALS['lang']['INVALID_VISIT']);
		}
		else
		{
			return showErr($GLOBALS['lang']['INVALID_VISIT']);
		}
	}
	
	/**
	 * Oauth设置用户密码
	 *
	 * @Title: re_set_password 
	 * @Description: todo(这里用一句话描述这个方法的作用) 
	 * @param    
	 * @return return_type   
	 * @author Liwei
	 * @throws 
	 *
	 */
	public function re_set_password($data){
		if(empty($data)) return false;
		//引入接口文件
		FP::import("libs.libs.auto_register");
				
		$auto_register = new autoRegister($data);
		
		$str = $auto_register->rc4Encode();
		
		$url = $GLOBALS['sys_config']['SET_PWD_API_URL'];
		//使用curl方式请求接口
		$target = $url.'?action=re_password&code='.$str;
		$cu = curl_init();
		curl_setopt($cu, CURLOPT_URL, $target);
		curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($cu);
		curl_close($cu);
		//返回数据
		return $ret;
	}
	
	public function forgetPassword()
        {
            //使用新OAuth
            $referer = get_http().$_SERVER["HTTP_HOST"].url("shop",'user#do_forget_password');
            $toUrl   = $GLOBALS['sys_config']['NEW_OAUTH_AUTH_URL'] . 'oauthserver_firstp2p/firstp2p/password/find/get.do?response_type=code&client_id=' . $GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"] . '&redirect_uri=' . urlencode($referer);
            header('Location:' . $toUrl);
            exit;
            
        }
	public function do_forget_password(){
            return showSuccess('密码修改成功，请重新登录。' ,0, url("shop",'user#login'));
            
        }
    public function edit_email()
    {
        $client_id=$GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"];
        $redirect_uri=get_http().$_SERVER["HTTP_HOST"].'/uc_center';
        $toUrl = $GLOBALS['sys_config']['NEW_OAUTH_AUTH_URL'] . 'oauthserver_firstp2p/firstp2p/email/show.do?response_type=code&client_id=' . $client_id
        .'&redirect_uri=' . urlencode($redirect_uri);
        header('Location:' . $toUrl);
        exit;
        
    }
    public function updateEmail()
    {
        $sign = trim($_REQUEST['sign']);
        if(!empty($sign))
        { 
            $sign = urldecode($sign);  
            // $ss='6002095&&test002&&ttt@qq.com';
            FP::import('libs.id5.des');
            $key= base64_decode('nUw0CwIfj6Q=');
            $DES = new DES($key);
            $params = $DES->newDecrypt($sign);
            $error_code = '0';
//                $params = $DES->newEncrypt($ss);
//                var_dump(urlencode($params));exit();
            if($params)
            {
                list($passport_id,$username,$email) = explode('&&',$params);
                
                if(!empty($passport_id) && !empty($username) && !empty($email))
                {
                    $userid=$GLOBALS['db']->getOne("select id from ".DB_PREFIX."user "
                            ."where user_name = '{$username}' and passport_id ='{$passport_id}'");
                    if(!empty($userid))
                    {
                        $where="id={$userid}";                        
                        $data['email'] = $email;
                        $data['update_time'] = get_gmtime();
                        
                        if($GLOBALS['db']->autoExecute(DB_PREFIX."user",$data,'UPDATE',$where))
                        {
                            $error_code ='0';
                        }
                        else
                        {
                            $error_code = '-5';  
                            $error_str = 'update-fail';                          
                        }                         
                    }
                    else
                    {
                        $error_code = '-4';
                    }
                }
                else
                {
                    $error_code = '-3';
                }
            }
            else
            {
                $error_code = '-2';
            }
        }
        else
        {
            $error_code = '-1';
        }
        FP::import("libs.utils.logger");
        $log = array(
            'type' => 'emailapi',
            'sign' => $sign,
            'params'=> urlencode($params),
            'error_code'=>$error_code,
            'user_id'=>$user_id,
            'username'=>$username,
            'passport_id'=>$passport_id,
            'path' =>  __FILE__,
            'function' => 'updateEmail',
            'msg' => '修改邮箱email',
            'time' => time(),
        );
        logger::wLog($log);
    
        echo $error_code;
        exit;
        
    }
    
    public function updatePhone()
    {
        $sign = trim($_REQUEST['sign']);
        if(!empty($sign))
        { 
            $sign = urldecode($sign);              
//                var_dump(urldecode($sign));
//             $ss='6002095&&test002&&15810462266';
            FP::import('libs.id5.des');
            $key= base64_decode('nUw0CwIfj6Q=');
            $DES = new DES($key);
            $params = $DES->newDecrypt($sign);
            $error_code = '0';
//                $params = $DES->newEncrypt($ss);
//                var_dump(urlencode($params));exit();
            if($params)
            {
                list($passport_id,$username,$phone) = explode('&&',$params);
                
                if(!empty($passport_id) && !empty($username) && !empty($phone))
                {
                    $userid=$GLOBALS['db']->getOne("select id from ".DB_PREFIX."user "
                            ."where user_name = '{$username}' and passport_id ='{$passport_id}'");
                    if(!empty($userid))
                    {
                        $where="id={$userid}";                        
                        $data['mobile'] = $phone;
                        $data['update_time'] = get_gmtime();
                        
                        if($GLOBALS['db']->autoExecute(DB_PREFIX."user",$data,'UPDATE',$where))
                        {
                            $error_code ='0';
                        }
                        else
                        {
                            $error_code = '-5';  
                            $error_str = 'update-fail';                          
                        }                       
                    }
                    else
                    {
                        $error_code = '-4';
                    }
                }
                else
                {
                    $error_code = '-3';
                }
            }
            else
            {
                $error_code = '-2';
            }
        }
        else
        {
            $error_code = '-1';
        }
        FP::import("libs.utils.logger");
        $log = array(
            'type' => 'phoneapi',
            'sign' => $sign,
            'params'=> urlencode($params),
            'error_code'=>$error_code,
            'user_id'=>$user_id,
            'username'=>$username,
            'passport_id'=>$passport_id,
            'path' =>  __FILE__,
            'function' => 'updatePhone',
            'msg' => '修改手机',
            'time' => time(),
        );
        logger::wLog($log);
    
        echo $error_code;
        exit;
        
    }
}
?>
