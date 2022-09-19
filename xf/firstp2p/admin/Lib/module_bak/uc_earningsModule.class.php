<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.uc");
class uc_earningsModule extends SiteBaseModule
{
    function index() {
    	$site_id = app_conf("TEMPLATE_ID");
        
    	$user_statics = sys_user_status($GLOBALS['user_info']['id'],false,false,$site_id);
		$GLOBALS['tmpl']->assign("user_statics",$user_statics);
    	
    	$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_EARNINGS']);
    	
    	$load = $GLOBALS['db']->getRow("SELECT sum(money) as total_money FROM ".DB_PREFIX."deal_load WHERE site_id=".$site_id." AND user_id=".intval($GLOBALS['user_info']['id']));
    	$GLOBALS['tmpl']->assign("load",$load);
    	
    	$GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_earnings.html");
		$GLOBALS['tmpl']->display("page/uc.html");
    }
}
?>