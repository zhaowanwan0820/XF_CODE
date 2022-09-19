<?php
 //crontab: 
 //发送邮件    */1 * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php msg_send.php deal_msg_list
 //生成合同PDF */1 * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php msg_send.php contract_list

// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
//用于队列的发送
header("Content-Type:text/html; charset=utf-8");
require dirname(__FILE__).'/../app/init.php';
//ini_set("display_errors","On");
//兼容命令行模式 msg_send.php deal_msg_list
if(isset($argc)){
    $_REQUEST['act']=$argv[1];
}
es_session::close();
set_time_limit(0);

if($_REQUEST['act']=='contract_list'){

	require  APP_ROOT_PATH."system/tcpdf/tcpdf.php";
	require APP_ROOT_PATH."system/tcpdf/mkpdf.php";
	$mkpdf =new Mkpdf();
	//$filedir = APP_ROOT_PATH.'public/contract/';
	$filedir = $GLOBALS['dict']['CONTRACT_PDF_PATH'];
	$max_i = 0;
	while($row = $GLOBALS['db']->getRow("select id,attachment from ".DB_PREFIX."deal_msg_list where is_send = 0 and send_type = 1 and is_contract = 0 and attachment != '' order by id asc limit 1")){
            $max_i++;
			$arr = explode(",",$row['attachment']);
			foreach( $arr as $v ){
				$con = $GLOBALS['db']->getRow("select number,content,create_time from ".DB_PREFIX."contract where id = '$v' limit 1");
				$filename = $filedir;
                $filename .= ceil($v/1000);
                if(!is_dir($filename)){
                	mkdir($filename, 0777);    
                }
                $filename .= '/'.md5($con['number']).'.pdf';
                if(app_conf("CONTRACT_END_TIME") < $con['create_time']){
                    $con['content'] = nl2br($con['content']);
                }
				$mkpdf->mk($filename,$con['content']);  
			}
			$GLOBALS['db']->query("update ".DB_PREFIX."deal_msg_list set `is_contract` = 1 where id = '{$row[id]}'");

			if( $max_i >= app_conf("MAX_CONTRACT") ){
				break;
			}
	}

}

if($_REQUEST['act']=='deal_msg_list')
{
	//业务队列的群发
	$GLOBALS['db']->query("update ".DB_PREFIX."conf set `value` = 1 where name = 'DEAL_MSG_LOCK' and `value` = 0");
	$rs = $GLOBALS['db']->affected_rows();
    $rs = 1;//放弃队列锁机制 by zrs 20130710
	if($rs)
	{		
		//业务队列中处理返利发放
		$rid = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."referrals where ".get_gmtime()."-create_time > ".(intval(app_conf('REFERRALS_DELAY'))*60)." and pay_time = 0");
		if($rid)
		pay_referrals(intval($rid));
		$msg_i = 0;
		
		echo "开始发送消息...\r\n\r\n";
		while($msg_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_msg_list where send_type = 1 and is_send = 0 and (attachment is null or attachment = '' or (attachment != '' and is_contract = 1)) order by id asc limit 1"))
		{
			
			$msg_i++;
			if($msg_item)
			{
				//优先改变发送状态,不论有没有发送成功
				$GLOBALS['db']->query("update ".DB_PREFIX."deal_msg_list set is_send = 1,send_time='".get_gmtime()."' where id =".intval($msg_item['id']));
				echo "id:",$msg_item['id'],"修改发送状态is_send=1,判断发送类型... \r\n";
				// if($msg_item['send_type']==0)
				// {
				// 	//短信
				// 	require_once APP_ROOT_PATH."system/utils/es_sms.php";
				// 	$sms = new sms_sender();
					
				// 	$sms_content = str_replace(array(" "," "), '', $msg_item['content']);
				// 	$result = $sms->sendSms($msg_item['dest'],$sms_content);
					
				// 	echo "短信类型,发送结果：",$result,"\r\n\r\n";
				// 	//发送结束，更新当前消息状态
				// 	$GLOBALS['db']->query("update ".DB_PREFIX."deal_msg_list set is_success = ".intval($result['status']).",result='".$result['msg']."' where id =".intval($msg_item['id']));
				// }	
				if($msg_item['send_type']==1)
				{
					//邮件
					require_once APP_ROOT_PATH."system/utils/es_mail.php";
					$mail = new mail_sender();
		
					$mail->AddAddress($msg_item['dest']);
					$mail->IsHTML($msg_item['is_html']); 				  // 设置邮件格式为 HTML
					$mail->Subject = $msg_item['title'];   // 标题
					$mail->Body = $msg_item['content'];  // 内容
					
					echo "邮件类型... \r\n";
			
					if($msg_item['attachment']){//附件
         			
						echo "处理附件... \r\n";
					//	require  APP_ROOT_PATH."system/tcpdf/tcpdf.php";
				
			
					//	require APP_ROOT_PATH."system/tcpdf/mkpdf.php";
                        $att_arr = explode(',',$msg_item['attachment']);
						
                        //$filedir = APP_ROOT_PATH.'public/contract/';
                        $filedir = $GLOBALS['dict']['CONTRACT_PDF_PATH'];
                        //$mkpdf =new Mkpdf();
						if($att_arr){
                        	foreach ($att_arr as $v){
                                $filename = $filedir;
                                $row = $GLOBALS['db']->getRow("select number from ".DB_PREFIX."contract where id = '$v' limit 1");
                                $filename .= ceil($v/1000);
                               	// if(!is_dir($filename))
                                 //       mkdir($filename);    
                                $filename .= '/'.md5($row['number']).'.pdf';
                                if(file_exists($filename)){
                                	echo "附件pdf存在，添加... \r\n";
                                	$mail->AddAttachment($filename);
                                }else{
                                	echo "附件pdf不存在啊亲... \r\n";
                                }
                        	} 
						}
					}else{
						echo "没有附件... \r\n";
					}
					$is_success = $mail->Send();
					$result = $mail->ErrorInfo;
					
					echo "发送结果：$is_success,错误信息：",$result," \r\n\r\n";
					//发送结束，更新当前消息状态
					$GLOBALS['db']->query("update ".DB_PREFIX."deal_msg_list set is_success = ".intval($is_success).",result='".$result."' where id =".intval($msg_item['id']));
				}
				// 如果是发送标单信息到9888顾问库
				if($msg_item['send_type']==2)
				{
					$content = unserialize($msg_item['content']);
					$json_data = array('data'=>json_encode($content));
					
					
					$cu = curl_init();
					curl_setopt($cu, CURLOPT_URL, $msg_item['dest']);
					curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($cu, CURLOPT_POST, 1);
					curl_setopt($cu, CURLOPT_POSTFIELDS, $json_data );
					$ret = curl_exec($cu);
					curl_close($cu);
					
					if($ret == 1){
						$is_send = 1;
					}else{
						$is_send = 0;
					}
					
					$GLOBALS['db']->query("update ".DB_PREFIX."deal_msg_list set is_success = ".intval($ret).",result='".$is_send."' where id =".intval($msg_item['id']));//,is_send = ".$is_send."   edit by wenyanlei 20130924
				}
			}
			if( $msg_i >= app_conf("MAX_SEND_MAIL") ){
				break;
			}
				
		}
		header("Content-Type:text/html; charset=utf-8");
		echo intval($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal_msg_list where is_send = 0"));
		$GLOBALS['db']->query("update ".DB_PREFIX."conf set `value` = 0 where name = 'DEAL_MSG_LOCK'");	
	}
	else
	{
		header("Content-Type:text/html; charset=utf-8");
		echo 0;
	}	
}

if($_REQUEST['act']=='promote_msg_list')
{	
	//推广队列的群发
	$GLOBALS['db']->query("update ".DB_PREFIX."conf set `value` = 1 where name = 'PROMOTE_MSG_LOCK' and `value` = 0");
	$rs = $GLOBALS['db']->affected_rows();
	if($rs)
	{
		$promote_msg = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."promote_msg where send_status <> 2 and send_time <= ".get_gmtime()." order by id asc limit 1");
		
		if($promote_msg)
		{
			$last_id = intval($GLOBALS['db']->getOne("select value from ".DB_PREFIX."conf where name = 'PROMOTE_MSG_PAGE'"));
			
			//开始更新为发送中
			$GLOBALS['db']->query("update ".DB_PREFIX."promote_msg set send_status = 1 where id = ".intval($promote_msg['id'])." and send_status <> 2");
			switch(intval($promote_msg['send_type']))
			{
				case 0: //会员组					
					$group_id = intval($promote_msg['send_type_id']);
					if($promote_msg['type']==0)
					{
						//短信
						$sql = "select u.id,u.mobile from ".DB_PREFIX."user as u where u.mobile <> '' ";
						if($group_id>0)
						{
							$sql.=" and u.group_id = ".$group_id;
						}
						$sql.=" and u.id > ".$last_id." order by u.id asc";   
						$res = $GLOBALS['db']->getRow($sql);
						$dest = $res['mobile'];
						$last_id = $res['id'];
					}
					
					if($promote_msg['type']==1)
					{
						//邮件
						$sql = "select u.id,u.email from ".DB_PREFIX."user as u where u.email <> '' ";
						if($group_id>0)
						{
							$sql.=" and u.group_id = ".$group_id;
						}
						$sql.=" and u.id > ".$last_id." order by u.id asc";   
						$res = $GLOBALS['db']->getRow($sql);
						$dest = $res['email'];
						$last_id = $res['id'];
					}					
					break;
				case 1: //订阅
					$city_id = intval($promote_msg['send_type_id']);
					
					$ids_util = new ChildIds("deal_city");
					$ids = $ids_util->getChildIds($city_id);
					$ids[] = $city_id;
					$ids_str = implode(",",$ids);
					
					if($promote_msg['type']==0)
					{
						//短信
						$sql = "select id,mobile from ".DB_PREFIX."mobile_list where mobile <> '' and is_effect = 1 ";
						if($city_id>0)
						{
							$sql.=" and city_id in(".$ids_str.") ";
						}
						$sql.=" and id > ".$last_id." order by id asc";   

						$res = $GLOBALS['db']->getRow($sql);
						$dest = $res['mobile'];
						$last_id = $res['id'];
					}
					
					if($promote_msg['type']==1)
					{
						//邮件
						$sql = "select id,mail_address from ".DB_PREFIX."mail_list where mail_address <> '' and is_effect = 1 ";
						if($city_id>0)
						{
							$sql.=" and city_id in(".$ids_str.") ";
						}
						$sql.=" and id > ".$last_id." order by id asc";   
						$res = $GLOBALS['db']->getRow($sql);
						$dest = $res['mail_address'];
						$last_id = $res['id'];
					}					
					
					break;
				case 2: //自定义
					$send_define_data = trim($promote_msg['send_define_data']); //自定义的内容
					$dest_array = preg_split("/[ ,]/i",$send_define_data);
					
					foreach($dest_array as $k=>$v)
					{
							$rs = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."promote_msg_list where msg_id = ".intval($promote_msg['id'])." and dest = '".$v."'");
							if($rs==0)
							{
								$dest = $v;
								break;
							}
					}				
					$last_id = 0;
					break;
			}
			
			if($dest)
			{
				//开始创建一个新的发送队列
				$msg_data['dest'] = $dest;
				$msg_data['send_type'] = $promote_msg['type'];
				$msg_data['content'] = addslashes($promote_msg['content']);
				$msg_data['title'] = $promote_msg['title'];
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = 0;
				$msg_data['is_html'] = $promote_msg['is_html'];
				$msg_data['msg_id'] = $promote_msg['id'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."promote_msg_list",$msg_data); //插入	
				if($id = $GLOBALS['db']->insert_id())
				{
						$msg_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."promote_msg_list where id = ".$id);
						
						if($msg_item)
						{
							//优先改变发送状态,不论有没有发送成功
							$GLOBALS['db']->query("update ".DB_PREFIX."promote_msg_list set is_send = 1,send_time='".get_gmtime()."' where id =".intval($msg_item['id']));
			
							if($msg_item['send_type']==0)
							{
								//短信
								require_once APP_ROOT_PATH."system/utils/es_sms.php";
								$sms = new sms_sender();
								$result = $sms->sendSms($msg_item['dest'],$msg_item['content']);
								//发送结束，更新当前消息状态
								$GLOBALS['db']->query("update ".DB_PREFIX."promote_msg_list set is_success = ".intval($result['status']).",result='".$result['msg']."' where id =".intval($msg_item['id']));
							}	
					
							if($msg_item['send_type']==1)
							{
								//邮件
								require_once APP_ROOT_PATH."system/utils/es_mail.php";
								$mail = new mail_sender();
						
								$mail->AddAddress($msg_item['dest']);
								$mail->IsHTML($msg_item['is_html']); 				  // 设置邮件格式为 HTML
								$mail->Subject = $msg_item['title'];   // 标题
								//发送推广邮件时加上退订的内容
								if($msg_item['is_html'])
								$msg_item['content'] .= "<br />".sprintf(app_conf("UNSUBSCRIBE_MAIL_TIP"),app_conf("SHOP_TITLE"),"<a href='".get_domain().APP_ROOT."/tuan.php?ctl=subscribe&act=unsubscribe&code=".base64_encode($msg_item['dest'])."'>".$GLOBALS['lang']['CANCEN_EMAIL']."</a>");
								else
								$msg_item['content'] .= " \n ".sprintf(app_conf("UNSUBSCRIBE_MAIL_TIP"),app_conf("SHOP_TITLE"),$GLOBALS['lang']['CANCEN_EMAIL'].get_domain().APP_ROOT."/tuan.php?ctl=subscribe&act=unsubscribe&code=".base64_encode($msg_item['dest']));
								
								$mail->Body = $msg_item['content'];  // 内容	
						
								$is_success = $mail->Send();
								$result = $mail->ErrorInfo;
					
								//发送结束，更新当前消息状态
								$GLOBALS['db']->query("update ".DB_PREFIX."promote_msg_list set is_success = ".intval($is_success).",result='".$result."' where id =".intval($msg_item['id']));
							}						
						}	
				}
				$GLOBALS['db']->query("update ".DB_PREFIX."conf set value = ".intval($last_id)." where name='PROMOTE_MSG_PAGE'");
			}
			else //当没有目标可以发送时。完成发送
	        {
				$GLOBALS['db']->query("update ".DB_PREFIX."promote_msg set send_status = 2 where id = ".intval($promote_msg['id']));
				$GLOBALS['db']->query("update ".DB_PREFIX."conf set value = 0 where name='PROMOTE_MSG_PAGE'");
	        }
        }
		header("Content-Type:text/html; charset=utf-8");
		echo intval($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."promote_msg where send_status <> 2 and send_time <=".get_gmtime()));
        $GLOBALS['db']->query("update ".DB_PREFIX."conf set `value` = 0 where name = 'PROMOTE_MSG_LOCK'");	
	}
    else
    {
	    header("Content-Type:text/html; charset=utf-8");
		echo 0;	
    }
}

if($_REQUEST['act']=='apns_list')
{
	
//业务队列的群发
	$GLOBALS['db']->query("update ".DB_PREFIX."conf set `value` = 1 where name = 'APNS_MSG_LOCK' and `value` = 0");
	$rs = $GLOBALS['db']->affected_rows();
	if($rs)
	{	
		$message = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."apns_messages where status <> 2 and send_time <= ".get_gmtime()." order by id asc limit 1");
		if($message)
		{
			$last_id = intval($GLOBALS['db']->getOne("select value from ".DB_PREFIX."conf where name = 'APNS_MSG_PAGE'"));
			//开始更新为发送中
			$GLOBALS['db']->query("update ".DB_PREFIX."apns_messages set status = 1 where id = ".intval($message['id'])." and status <> 2");
			
			if($message['user_names']=='')
			{
				//发送给所有人
				$uids = array();  //为空
			}
			else
			{
				$user_names = preg_split("/[ ,]/i",$message['user_names']);
				foreach($user_names as $k=>$v)
				{
					$uid = intval($GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where user_name = '".$v."'"));
					if($uid>0)
					$uids[] = $uid;
				}
			}
			
			//查询设备记录
			$sql  = "select pid from ".DB_PREFIX."apns_devices where 1=1 ";
			if(count($uids)>0)
			{
				$sql.=" and clientid in (".implode(",",$uids).") ";
			}
			$sql.=" and pid>".$last_id." order by pid asc";
			$pid = intval($GLOBALS['db']->getOne($sql,true));
			
			if($pid>0)
			{
				//有ID
				//发送过程.....
				
			
				require_once APP_ROOT_PATH."system/libs/apns.php";
				$pids[] = $pid;
				$apns = new apns();
				$apns->newMessage($pids);
				$apns->addMessageAlert($message['content']);
				//$apns->addMessageBadge(2);
				$apns->addMessageSound('bingbong.aiff');
				$apns->queueMessage($message['id']);
				$apns->processQueue();


				
				//end发送
				$GLOBALS['db']->query("update ".DB_PREFIX."conf set value = ".intval($pid)." where name='APNS_MSG_PAGE'");
			}
			else
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."apns_messages set status = 2 where id = ".intval($message['id']));
				$GLOBALS['db']->query("update ".DB_PREFIX."conf set value = 0 where name='APNS_MSG_PAGE'");
			}
		}
		header("Content-Type:text/html; charset=utf-8");
		echo intval($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."apns_messages where status <> 2 and send_time <=".get_gmtime()));
		$GLOBALS['db']->query("update ".DB_PREFIX."conf set `value` = 0 where name = 'APNS_MSG_LOCK'");	
	}
	else
	{
		header("Content-Type:text/html; charset=utf-8");
		echo 0;
	}	
}
?>
