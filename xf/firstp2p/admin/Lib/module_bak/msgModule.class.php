<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.page");
FP::import("app.message");
FP::import("libs.libs.msgcenter");

class msgModule extends SiteBaseModule
{
	public function index()
	{
		$GLOBALS['tmpl']->caching = true;
		$cache_id  = md5(MODULE_NAME.trim($_REQUEST['act']).$GLOBALS['deal_city']['id']);	
		if($GLOBALS['tmpl']->is_cached("msg_index.html",$cache_id))
		{
			
		}	
		$GLOBALS['tmpl']->display("msg_index.html",$cache_id);
	}
	
	//不可接收购买评论
	public function add()
	{				
		$user_info = $GLOBALS['user_info'];
		$ajax = intval($_REQUEST['ajax']);
		if(!$user_info)
		{
			showErr($GLOBALS['lang']['PLEASE_LOGIN_FIRST'],$ajax);
		}
		if($_REQUEST['content']=='')
		{
			showErr($GLOBALS['lang']['MESSAGE_CONTENT_EMPTY'],$ajax);
		}
		
		//验证码
		if(app_conf("VERIFY_IMAGE")==1)
		{
			$verify = md5(trim($_REQUEST['verify']));
			$session_verify = es_session::get('verify');
			if($verify!=$session_verify)
			{				
				showErr($GLOBALS['lang']['VERIFY_CODE_ERROR'],$ajax);
			}
		}
		
		if(!check_ipop_limit(get_client_ip(),"message",intval(app_conf("SUBMIT_DELAY")),0))
		{
			showErr($GLOBALS['lang']['MESSAGE_SUBMIT_FAST'],$ajax);
		}
		
		$rel_table = addslashes(trim($_REQUEST['rel_table']));
		$message_type = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."message_type where type_name='".$rel_table."'");
		if(!$message_type)
		{
			showErr($GLOBALS['lang']['INVALID_MESSAGE_TYPE'],$ajax);
		}			
		//添加留言
		$message['title'] = $_REQUEST['title']?htmlspecialchars(addslashes($_REQUEST['title'])):htmlspecialchars(addslashes($_REQUEST['content']));
		$message['content'] = htmlspecialchars(addslashes(valid_str($_REQUEST['content'])));
		$message['title'] = valid_str($message['title']);
			
		$message['create_time'] = get_gmtime();
		$message['rel_table'] = $rel_table;
		$message['rel_id'] = addslashes(trim($_REQUEST['rel_id']));
		$message['user_id'] = intval($GLOBALS['user_info']['id']);
		
		if(app_conf("USER_MESSAGE_AUTO_EFFECT")==0)
		{
			$message_effect = 0;
		}
		else
		{
			$message_effect = $message_type['is_effect'];
		}
		$message['is_effect'] = $message_effect;		
		$GLOBALS['db']->autoExecute(DB_PREFIX."message",$message);
		$l_user_id =  $GLOBALS['db']->getOne("SELECT user_id FROM ".DB_PREFIX."deal WHERE id=".$message['rel_id']);
		
		//添加到动态
		insert_topic("message",$message['rel_id'],$message['user_id'],$GLOBALS['user_info']['user_name'],$l_user_id);
		
		if($rel_table == "deal"){
			FP::import("app.deal");
			$deal = get_deal($message['rel_id']);
			//自己给自己留言不执行操作
			if($deal['user_id']!=$message['user_id']){
				$msg_conf = get_user_msg_conf($deal['user_id']);
				//站内信
				if($msg_conf['sms_asked']==1){
					$content = "<p>您好，用户 ".get_user_name($message['user_id'])."对您发布的借款列表 “<a href=\"".$deal['url']."\">".$deal['name']."</a>”进行了以下留言：</p>"; 
					$content .= "<p>“".$message['content']."”</p>";
					send_user_msg("",$content,0,$deal['user_id'],get_gmtime(),0,true,13,$message['rel_id']);
				}
				//邮件
				if($msg_conf['mail_asked']==1 && app_conf('MAIL_ON')==1){
					$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$deal['user_id']);
					$tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_DEAL_MSG'");
					$tmpl_content = $tmpl['content'];
					
					$notice['user_name'] = $user_info['user_name'];
					$notice['msg_user_name'] = get_user_name($message['user_id'],false);
					$notice['deal_name'] = $deal['name'];
                    $notice['deal_url'] = get_domain() . url("index", "deal", array("id" => $deal['id']));
                    $notice['message'] = $message['content'];
                    $notice['site_name'] = app_conf("SHOP_TITLE");
                    $notice['site_url'] = get_domain() . APP_ROOT;
                    $notice['help_url'] = get_domain() . url("index", "helpcenter");

                    $GLOBALS['tmpl']->assign("notice", $notice);

                    $msgcenter = new Msgcenter();
                    $msgcenter->setMsg($user_info['email'], $user_info['id'], $notice, 'TPL_MAIL_DEAL_MSG', get_user_name($message['user_id'], false) . "给您的标留言！");
                    $msgcenter->save();
                }
			}
		}
		
		return showSuccess($GLOBALS['lang']['MESSAGE_POST_SUCCESS'],$ajax);
	}
}
?>
