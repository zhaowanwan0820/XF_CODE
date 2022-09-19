<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class mobileModule extends SiteBaseModule
{
	public function index()
	{
		$GLOBALS['tmpl']->caching = true;
		$cache_id  = md5(MODULE_NAME.ACTION_NAME);		
		if (!$GLOBALS['tmpl']->is_cached('mobile_index.html', $cache_id))	
		{		
			
			
			$site_nav[] = array('name'=>$GLOBALS['lang']['HOME_PAGE'],'url'=>APP_ROOT."/");
			$site_nav[] = array('name'=>"手机客户端下载",'url'=>url("shop","mobile"));
			$GLOBALS['tmpl']->assign("site_nav",$site_nav);
			//输出当前的site_nav
			
			
			$GLOBALS['tmpl']->assign("page_title","手机客户端下载");
			$GLOBALS['tmpl']->assign("page_keyword","手机客户端下载");
			$GLOBALS['tmpl']->assign("page_description","手机客户端下载");
		}
		$GLOBALS['tmpl']->display("mobile_index.html",$cache_id);
	}
}
?>