<?php
/**
 * 消息中心
 */
FP::import("app.uc");
class uc_msgModule extends SiteBaseModule {
    
    /**
     * 消息中心分类列表页
     * @see SiteBaseModule::index()
     */
	public function index() {	
    	$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_NOTICE']);
    	$GLOBALS['tmpl']->assign("post_title",$GLOBALS['lang']['UC_NOTICE']);	
		$limit = '0,10';
		$user_id = intval($GLOBALS['user_info']['id']);

        $sql = "select group_key,count(is_notice) as total from (select group_key,is_notice,system_msg_id,create_time FROM " . \core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->tableName() . " where is_delete = 0 and to_user_id = ".$user_id." and `type` = 0  ORDER BY create_time DESC ) AS TMPA
				group by is_notice
				order by system_msg_id desc,MAX(create_time) desc,is_notice desc limit ".$limit;
        $list = core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->db->getAll($sql);

		//取消息
		foreach($list as $k=>$v) {
		    $sql = "select * from " . core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->tableName() . " where group_key = '".$v['group_key']."' and ((to_user_id = ".$user_id." and `type` = 0) or (from_user_id = ".$user_id." and `type` = 1))  order by create_time desc limit 1";
			$list[$k] = core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->db->getRow($sql);
			$list[$k]['total'] = $v['total'];
		}
		$mylist = array();
		//给消息分类
		foreach ($list as $key=>$val) {
		    if($val['is_notice'] == 18) {             //投标完成
		        $mylist['b'][] = $list[$key];
		    }else if ($val['is_notice'] == 16){       //项目满标
		        $mylist['c'][] = $list[$key];
		    }else if ($val['is_notice'] == 19){       //投标放款
		        $mylist['d'][] = $list[$key];
		    }else if ($val['is_notice'] == 9){        //投标取消
		        $mylist['e'][] = $list[$key];
		    }else if ($val['is_notice'] == 10 || $val['is_notice'] == 11){    //项目回款
		        $mylist['f'][] = $list[$key];
		    }else{                                    //系统消息
		        $mylist['a'][] = $list[$key];
		    }
		}
		
		ksort($mylist); 
		$notice_title_config = array(
			'a'=>'系统消息',
		    'b'=>'投标完成',
	        'c'=>'项目满标',
	        'd'=>'投标放款',
	        'e'=>'项目回款',
	        'f'=>'投标取消',
		);
//		$sql = 'SELECT COUNT(*) as num FROM `firstp2p_msg_box` WHERE to_user_id = '.$user_id.' AND is_notice <9';
//		$systemNum = $GLOBALS['db']->getRow($sql) ;
        $sql = "SELECT COUNT(*) as num FROM " .  core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->tableName() . " WHERE to_user_id = '.$user_id.' AND is_notice <9";
		$systemNum = core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->db->getRow($sql);
		$mylist['a'][0]['total'] = $systemNum['num']; 
		$GLOBALS['tmpl']->assign("msg_list",$mylist);
		//print_r($mylist);
		//$this->set_nav(array( "消息"));
		$GLOBALS['tmpl']->assign("notice_title", $notice_title_config);
		$GLOBALS['tmpl']->display('inc/uc/uc_msg_index.html');
	}
	
	/**
	 * 消息详情列表页
	 */
	public function deal() {
		$group_key = addslashes(trim($_REQUEST['id']));
		$user_id = intval($GLOBALS['user_info']['id']);
//		$sql = "select count(*) as count,max(system_msg_id) as system_msg_id,max(id) as id,max(is_notice) as is_notice from ".DB_PREFIX."msg_box
//				where is_delete = 0 and ((to_user_id = ".$user_id." and `type` = 0) or (from_user_id = ".$user_id." and `type` = 1))
//				and group_key = '".$group_key."'";
//		$row = $GLOBALS['db']->getRow($sql);
        $sql = "select count(*) as count,max(system_msg_id) as system_msg_id,max(id) as id,max(is_notice) as is_notice from " . core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->tableName() .
            " where is_delete = 0 and ((to_user_id = ".$user_id." and `type` = 0) or (from_user_id = ".$user_id." and `type` = 1))
				and group_key = '".$group_key."'";
        $row = core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->db->getRow($sql);
		if(!empty($row['count'])) {
		    $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['SYSTEM_PM']);
		    //分页
		    $page = intval($_REQUEST['p']);
		    if($page==0)
		        $page = 1;
		    //消息分类之后的 逻辑
		    if(in_array($row['is_notice'], array(0,1,2,3,4,5,6,7,8))) {
		        $sql_str = '  AND  is_notice <9';
		    }else{
		        $sql_str=  '  AND is_notice='.$row['is_notice'];
		    }
		    $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");

            $sql = "select * from ".core\dao\MsgBoxModel::instance()->tableName(array('to_user_id' => $GLOBALS['user_info']['id'])) . " where system_msg_id = ".$row['system_msg_id']."  ".$sql_str." AND to_user_id=".$GLOBALS['user_info']['id']." and is_delete = 0 ORDER BY create_time DESC LIMIT ".$limit;
            $list = core\dao\MsgBoxModel::instance(array('to_user_id' => $GLOBALS['user_info']['id']))->db->getRow($sql);
		    $GLOBALS['tmpl']->assign("list",$list);

            $sql = "select count(*) from " . core\dao\MsgBoxModel::instance(array('to_user_id' => $GLOBALS['user_info']['id']))->tableName() . " where system_msg_id = ".$row['system_msg_id']." ".$sql_str." AND to_user_id=".$GLOBALS['user_info']['id']." and is_delete = 0";
            $total = core\dao\MsgBoxModel::instance(array('to_user_id' => $GLOBALS['user_info']['id']))->db->getOne($sql);
            $page = new Page($total,app_conf("PAGE_SIZE"));   //初始化分页对象
		    $p  =  $page->show();
		    $GLOBALS['tmpl']->assign('pages',$p);
		    //更新 已读状态
//		    $upd_sql = "UPDATE ".DB_PREFIX."msg_box SET is_read = 1
//        					WHERE is_delete = 0 AND ((to_user_id = ".$user_id." AND `type` = 0) or (from_user_id = ".$user_id." AND `type` = 1))
//        					AND is_read = 0
//        					AND system_msg_id = ".$row['system_msg_id'].$sql_str;
//		    $GLOBALS['db']->query($upd_sql);
            $upd_sql = "UPDATE ".core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->tableName() ." SET is_read = 1
        					WHERE is_delete = 0 AND ((to_user_id = ".$user_id." AND `type` = 0) or (from_user_id = ".$user_id." AND `type` = 1))
        					AND is_read = 0
        					AND system_msg_id = ".$row['system_msg_id'].$sql_str;
            core\dao\MsgBoxModel::instance(array('to_user_id' => $user_id))->db->query($upd_sql);
		    //$this->set_nav(array("消息"=>url("index", "uc_msg"), "消息详情"));
		    $GLOBALS['tmpl']->assign("notice_title", $GLOBALS['dict']['MSG_NOTICE_TITLE']);
		    $GLOBALS['tmpl']->display("inc/uc/uc_msg_deal_system.html");
		}else{ //指到首页
		    return app_redirect(url("index",'index'));
		}
	}
	
	
	
	public function setting(){
		$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_MSG_SETTING']);	
		
		$msg_setting = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_conf where user_id = ".$GLOBALS['user_info']['id']);
		
		$GLOBALS['tmpl']->assign("msg_setting",$msg_setting);
		$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_msg_setting.html");
		$GLOBALS['tmpl']->display("page/uc.html");	
	}
	
	public function savesetting(){
		if($GLOBALS['user_info']['id'] > 0){
			$data['user_id'] = intval($GLOBALS['user_info']['id']);
			$data['mail_asked'] = intval($_REQUEST['mail_asked']);
			$data['sms_asked'] = intval($_REQUEST['sms_asked']);
			$data['mail_bid'] = intval($_REQUEST['mail_bid']);
			$data['sms_bid'] = intval($_REQUEST['sms_bid']);
			$data['mail_myfail'] = intval($_REQUEST['mail_myfail']);
			$data['sms_myfail'] = intval($_REQUEST['sms_myfail']);
			$data['mail_half'] = intval($_REQUEST['mail_half']);
			$data['sms_half'] = intval($_REQUEST['sms_half']);
			$data['mail_bidsuccess'] = intval($_REQUEST['mail_bidsuccess']);
			$data['sms_bidsuccess'] = intval($_REQUEST['sms_bidsuccess']);
			$data['mail_fail'] = intval($_REQUEST['mail_fail']);
			$data['sms_fail'] = intval($_REQUEST['sms_fail']);
			$data['mail_bidrepaid'] = intval($_REQUEST['mail_bidrepaid']);
			$data['sms_bidrepaid'] = intval($_REQUEST['sms_bidrepaid']);
			$data['mail_answer'] = intval($_REQUEST['mail_answer']);
			$data['sms_answer'] = intval($_REQUEST['sms_answer']);
			if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."msg_conf where user_id = ".$data['user_id'])==0){
				//添加
				$GLOBALS['db']->autoExecute(DB_PREFIX."msg_conf",$data,"INSERT");
			}
			else{
				//编辑
				$GLOBALS['db']->autoExecute(DB_PREFIX."msg_conf",$data,"UPDATE","user_id=".$data['user_id']);
			}
			$key = md5("USER_MSG_CONF_".$data['user_id']);
			//更新配置缓存
			set_dynamic_cache($key,$data);
			return showSuccess($GLOBALS['lang']['MESSAGE_POST_SUCCESS']);
		}else{
			return app_redirect(url("index","user#login"));
		}
	}
}
?>
