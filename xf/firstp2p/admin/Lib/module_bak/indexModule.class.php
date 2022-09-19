<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

define("MODULE_NAME","index");
FP::import("app.deal");
class indexModule extends SiteBaseModule
{
	public function index()
	{
	    return app_redirect('/');
		$GLOBALS['tmpl']->caching = true;
		$GLOBALS['tmpl']->cache_lifetime = 1;//600;  //首页缓存10分钟
		$cache_id  = md5(MODULE_NAME.ACTION_NAME);
		if (!$GLOBALS['tmpl']->is_cached("page/new/index.html", $cache_id))
		{
			//最新借款列表
			#$deal_list =  get_deal_list(11,0,"publish_wait =0 AND deal_status in(0,1,2) "," deal_status ASC, update_time DESC,sort DESC,id DESC");
			$deal_type_data = deal_types();
			$deal_type = $deal_type_data['data'];
			$deals_list = array();
			foreach($deal_type as $k=>$v){
				$list = get_deal_list(10,0,$v['where']." publish_wait =0 AND deal_status in(0,1,2,4,5) AND is_update != 1 "," FIELD(deal_status,1,0,2,4,5) ASC, update_time DESC,sort DESC,id DESC");
				$list = format_deals_list($list);
				if($list){
					$deals_list[$k]['name'] = $v['name'];
					$deals_list[$k]['list'] = $list['list'];
					$deal_type[$k]['count'] = $list['count'];
				}else{
					unset($deal_type[$k]);
				}
			}
			$GLOBALS['tmpl']->assign("deals_list",$deals_list);
			$GLOBALS['tmpl']->assign("deal_type",$deal_type);

			//友情链接
			$links = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."link where is_effect = 1 and show_index = 1 order by sort asc limit 20");
			if($links){
				foreach($links as $kk=>&$vv){
					$vv["img"] = str_replace("./app/Tpl/default", $GLOBALS["tmpl"]->_var["APP_WEB_STATIC"], $vv["img"]);
				}
			}
			$GLOBALS['tmpl']->assign("links",$links);
			//首页贷款收益概述
			$deals_income_view = deals_income_view();
			$GLOBALS['tmpl']->assign("deals_income_view",$deals_income_view);
		}
		$GLOBALS['tmpl']->display("page/new/index.html",$cache_id);
	}
	public function index_old()
	{
		$GLOBALS['tmpl']->caching = true;
		$GLOBALS['tmpl']->cache_lifetime = 1;//600;  //首页缓存10分钟
		$cache_id  = md5(MODULE_NAME.ACTION_NAME);
		if (!$GLOBALS['tmpl']->is_cached("page/index.html", $cache_id))
		{
			make_deal_cate_js();
			make_delivery_region_js();
			//change_deal_status();

			//友情链接
			$links = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."link where is_effect = 1 and show_index = 1 order by sort asc limit 20");
			if($links){
				foreach($links as $kk=>&$vv){$vv["img"] = str_replace("./app/Tpl/default", $GLOBALS["tmpl"]->_var["APP_WEB_STATIC"], $vv["img"]);
					if(!empty($vv['url']) && substr($vv['url'],0,7) == 'http://'){
						$vv['url'] = str_replace("http://","",$vv['url']);
					}
				}
			}

			//最新借款列表
			#$deal_list =  get_deal_list(11,0,"publish_wait =0 AND deal_status in(0,1,2) "," deal_status ASC, update_time DESC,sort DESC,id DESC");
			$deal_list =  get_deal_list(11,0,"publish_wait =0 AND deal_status in(0,1,2,4) AND is_update != 1 "," FIELD(deal_status,1,0,2,4) ASC, update_time DESC,sort DESC,id DESC");
			$GLOBALS['tmpl']->assign("deal_list",$deal_list['list']);
			$GLOBALS['tmpl']->assign("deal_count",$deal_list['count']);
			//输出公告
			$notice_list = get_notice(0);
			$GLOBALS['tmpl']->assign("notice_list",$notice_list);

			//使用技巧
			$use_tech_list  = get_article_list(12,6);
			$GLOBALS['tmpl']->assign("use_tech_list",$use_tech_list);

			//借款用途
			$loantype = $GLOBALS['db']->getAll("SELECT id,name FROM ".DB_PREFIX."deal_loan_type where is_delete = 0 and is_effect = 1");
			$GLOBALS['tmpl']->assign("loantype",$loantype);

			//借款期限
			$GLOBALS['tmpl']->assign("repay_time", $GLOBALS['dict']['REPAY_TIME']);

			//最大,最小借款
			$GLOBALS['tmpl']->assign("max_money", app_conf("MAX_BORROW_QUOTA")/10000);
			$GLOBALS['tmpl']->assign("min_money", app_conf("MIN_BORROW_QUOTA")/10000);

			//首页新闻
			$newsall = get_article_list(12,app_conf("SITE_NEWS_CATE_ID"),'','',false);
			$newslist = array();
			if($newsall['count'] > 0){
				foreach($newsall['list'] as $key => $val){
					if($key%2 == 0){
						$newslist['left'][] = $val;
					}else{
						$newslist['right'][] = $val;
					}
				}
			}
			$GLOBALS['tmpl']->assign('newslist', $newslist);

			$now = get_gmtime();
			$vote = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."vote where is_effect = 1 and begin_time < ".$now." and (end_time = 0 or end_time > ".$now.") order by sort desc limit 1");
			$GLOBALS['tmpl']->assign("vote",$vote);
			$GLOBALS['tmpl']->assign("links",$links);

			$GLOBALS['tmpl']->assign("show_site_titile",1);

            //采用adv标签方式
			//$ad = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."adv where adv_id = '首页广告位1' and tmpl= '".app_conf('TEMPLATE')."' limit 1");
			//$ad["code"] = str_replace("./public/attachment/","./attachment/",$ad["code"]);
			//$GLOBALS["tmpl"]->assign("ad",$ad);
		}

		$GLOBALS['tmpl']->display("page/index.html",$cache_id);
	}
}
?>
