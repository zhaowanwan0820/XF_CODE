<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.uc");

class uc_creditModule extends SiteBaseModule
{
	public function index(){
		$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_CREDIT']);
		
		$list = load_auto_cache("level");
		
		$GLOBALS['tmpl']->assign("list",$list['list']);
		
		foreach($list["list"] as $k=>$v){
			if($v['id'] ==  $GLOBALS['user_info']['level_id'])
				$user_point_level = $v['name'];
		}
		
		//可用额度
		$can_use_quota=get_can_use_quota($GLOBALS['user_info']['id']);
		$GLOBALS['tmpl']->assign('can_use_quota',$can_use_quota);
		
		//必要信用认证
    	$level_point['need_other_point'] = 0;
    	if($GLOBALS['user_info']['idcardpassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("IDCARDPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['workpassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("WORKPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['incomepassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("INCOMEPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['creditpassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("CREDITPASSED_POINT"));
    	}
    	//可选信用认证
    	if($GLOBALS['user_info']['housepassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("HOUSEPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['skillpassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("SKILLPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['carpassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("CARPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['marrypassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("MARRYPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['residencepassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("RESIDENCEPASSED_POINT"));
    	}
    	if($GLOBALS['user_info']['videopassed']==1){
    		$level_point['need_other_point'] += (int)trim(app_conf("VIDEOPASSED_POINT"));
    	}
    	
    	//还清
    	$level_point['repay_success'] = $GLOBALS['db']->getRow("SELECT sum(point) as total_point,count(*) AS total_count FROM ".DB_PREFIX."user_log WHERE is_delete = 0 AND log_info='还清借款' AND user_id=".$GLOBALS['user_info']['id']);
    	//逾期
    	$level_point['impose_repay'] = $GLOBALS['db']->getRow("SELECT sum(point) as total_point,count(*) AS total_count FROM ".DB_PREFIX."user_log WHERE is_delete = 0 AND log_info='逾期还款' AND user_id=".$GLOBALS['user_info']['id']);
    	//严重逾期
    	$level_point['yz_impose_repay'] = $GLOBALS['db']->getRow("SELECT sum(point) as total_point,count(*) AS total_count FROM ".DB_PREFIX."user_log WHERE is_delete = 0 AND log_info='严重逾期还款' AND user_id=".$GLOBALS['user_info']['id']);
    	
    	$GLOBALS['tmpl']->assign('level_point',$level_point);
		
		$GLOBALS['tmpl']->assign("user_point_level",$user_point_level);
		
		$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_credit.html");
		$GLOBALS['tmpl']->display("page/uc.html");
	}
}
?>