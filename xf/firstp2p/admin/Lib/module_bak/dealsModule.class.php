<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.deal");
class dealsModule extends SiteBaseModule
{
	
	public function index(){
		FP::import("app.page");
	
		$field_hash = array(
				'0'=>'id',
				'1'=>'rate',
				'2'=>'repay_time',
				'3'=>'borrow_amount',
				'4'=>'deal_status',
		);
	
		$type_hash = array(
				'0'=>'asc',
				'1'=>'desc',
		);
		$deal_type_data = deal_types();
		$types_hash = $deal_type_data['others'];
	
		//tab分类type_id
		$cate = getRequestInt("cate",0);
		
		if(!in_array($cate,$types_hash)){
			$cate = 0;
		}
		//排序样式
		$sort['field'] = $field_hash[$_GET['field']];
		$sort['type'] = $type_hash[$_GET['type']];
		$GLOBALS['tmpl']->assign("sort",$sort);
		//输出投标列表
		$page = getRequestInt('p',1);
		$limit = (($page-1)*app_conf("DEAL_PAGE_SIZE")).",".app_conf("DEAL_PAGE_SIZE");
	
		$condition = " publish_wait = 0 ";
		$condition .= "AND deal_status in(0,1,2,4,5) ";
		
		$orderby = "";
		$field = $sort['field'];
		$field_sort = $sort['type'];
		if($field && $field_sort){
			$orderby = "$field $field_sort ,sort DESC,id DESC";
			if($field == 'deal_status'){
				$orderby = "FIELD(deal_status, 1,0,2,4,5,3)  {$field_sort} ,sort DESC,id DESC";
			}
		}
		else{
			$orderby = "FIELD(deal_status, 1,0,2,4,5,3) , update_time DESC , sort DESC , id DESC"; // 修改排序规则，1.可投 - 满标 - 还款中 2.发布时间顺序由新到旧
		}
		
		$deal_type = $deal_type_data['data'];
		foreach($deal_type as $k=>$v){
			/* if($v['where']){
				$list = get_deal_list($limit,0,$v['where']." AND publish_wait =0 AND deal_status in(0,1,2,4,5) ",$orderby);
			}else{
				$list = get_deal_list($limit,0,"publish_wait =0 AND deal_status in(0,1,2,4,5) ",$orderby);
			} */
			if($cate == $k){
				$list = get_deal_list($limit,0,$v['where']." publish_wait =0 AND deal_status in(0,1,2,4,5) ",$orderby);
				$list = format_deals_list($list);
				$deal_list = $list;
				$page = new Page($list['count'],app_conf("PAGE_SIZE"));//array('types'=>$k));   //初始化分页对象
				$p  =  $page->show(array('cate'=>$k));
			}else{
				$list = get_deal_list($limit,0,$v['where']." publish_wait =0 AND deal_status in(0,1,2,4,5) ",$orderby,true,0,true);
			}
				
			if($list){
				$deals_list[$k]['name'] = $v['name'];
				$deals_list[$k]['list'] = $list['list'];
				
				
				
				$deal_type[$k]['count'] = $list['count'];
			}else{
				//unset($deal_type[$k]);
			}
		}
// 		$GLOBALS['tmpl']->assign("deals_list",$deals_list);
		$GLOBALS['tmpl']->assign("deal_list",$deal_list);
		$GLOBALS['tmpl']->assign("deal_type",$deal_type);
// 		$GLOBALS['tmpl']->assign("cate",$types_hash[$types]);
		$GLOBALS['tmpl']->assign("cate",$cate);
		$GLOBALS['tmpl']->assign("p",$p);
	
		$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
		$this->set_nav("投资列表");
		$GLOBALS['tmpl']->display("page/new/deals.html");
	}
	
	/*
	public function indexs(){
		FP::import("app.page");

        $field_hash = array(
            '0'=>'id',
            '1'=>'rate',
            '2'=>'repay_time',
            '3'=>'borrow_amount',
            '4'=>'deal_status',
        );

        $type_hash = array(
            '0'=>'asc',
            '1'=>'desc',
        );

		//排序样式
		$sort['field'] = $field_hash[$_GET['field']];
		$sort['type'] = $type_hash[$_GET['type']];
		$GLOBALS['tmpl']->assign("sort",$sort);
		
		$level_list = load_auto_cache("level");
		$GLOBALS['tmpl']->assign("level_list",$level_list['list']);
	
		if(trim($_REQUEST['cid'])=="last"){
			$cate_id = "-1";
			$page_title = $GLOBALS['lang']['LAST_SUCCESS_DEALS']." - ";
		}
		else{
			$cate_id = intval($_REQUEST['cid']);
		}
	
		if($cate_id == 0){
			$page_title = $GLOBALS['lang']['ALL_DEALS']." - ";
		}
	
		$keywords = trim($_REQUEST['keywords']);
		$GLOBALS['tmpl']->assign("keywords",$keywords);
	
		$level = intval($_REQUEST['level']);
		$GLOBALS['tmpl']->assign("level",$level);
	
		$interest = intval($_REQUEST['interest']);
		$GLOBALS['tmpl']->assign("interest",$interest);
	
		$months = intval($_REQUEST['months']);
		$GLOBALS['tmpl']->assign("months",$months);
	
		$lefttime_search = intval($_REQUEST['lefttime']);
		$GLOBALS['tmpl']->assign("lefttime_search",$lefttime_search);
		// 搜索 
		$repay_time_search = intval($_REQUEST['repay_time']);
		$GLOBALS['tmpl']->assign("repay_time_search",$repay_time_search);
	
		if($_REQUEST['loan_type'] == ""){
			$loan_type_search = -1;
		}else {
			$loan_type_search = intval($_REQUEST['loan_type']);
		}
		$GLOBALS['tmpl']->assign("loan_type_search",$loan_type_search);
	
		$repay_type_search = intval($_REQUEST['repay_type']);
		$GLOBALS['tmpl']->assign("repay_type_search",$repay_type_search);
	
		$deal_agency_search = intval($_REQUEST['deal_agency']);
		$GLOBALS['tmpl']->assign("deal_agency_search",$deal_agency_search);
	
		$warrant_search = intval($_REQUEST['warrant']);
		$GLOBALS['tmpl']->assign("warrant_search",$warrant_search);
	
		//输出分类
		$deal_cates_db = $GLOBALS['db']->getAllCached("select * from ".DB_PREFIX."deal_cate where is_delete = 0 and is_effect = 1 order by sort desc");
		$deal_cates = array();
		foreach($deal_cates_db as $k=>$v)
		{
			if($cate_id==$v['id']){
				$v['current'] = 1;
				$page_title = $v['name']." - ";
			}
			$v['url'] = url("index","deals",array("id"=>$v['id']));
			$deal_cates[] = $v;
		}
	
		//输出投标列表
		$page = intval($_REQUEST['p']);
		if($page==0)
			$page = 1;
		$limit = (($page-1)*app_conf("DEAL_PAGE_SIZE")).",".app_conf("DEAL_PAGE_SIZE");
	
		$n_cate_id = 0;
		$condition = " publish_wait = 0 ";
		$orderby = "";
		$field = $sort['field'];
		$field_sort = $sort['type'];
		if($cate_id > 0){
			$n_cate_id = $cate_id;
			$condition .= "AND deal_status in(0,1)";
			if($field && $field_sort)
				$orderby = "$field $field_sort ,deal_status desc , sort DESC,id DESC";
			else
				$orderby = "update_time DESC ,sort DESC,id DESC";
			$total_money = $GLOBALS['db']->getOne("SELECT sum(borrow_amount) FROM ".DB_PREFIX."deal WHERE cate_id=$cate_id AND deal_status in(2,4)");
		}
		elseif ($cate_id == 0){
			$n_cate_id = 0;
			$condition .= "AND deal_status in(0,1,2,4,5)";
			if($field && $field_sort){
				$orderby = "$field $field_sort ,sort DESC,id DESC";
				if($field == 'deal_status'){
					$orderby = "FIELD(deal_status, 1,0,2,4,5,3)  {$field_sort} ,sort DESC,id DESC";
				}
			}
			else{
				$orderby = "FIELD(deal_status, 1,0,2,4,5,3) , update_time DESC , sort DESC , id DESC"; // 修改排序规则，1.可投 - 满标 - 还款中 2.发布时间顺序由新到旧
			}
			$total_money = $GLOBALS['db']->getOne("SELECT sum(borrow_amount) FROM ".DB_PREFIX."deal WHERE deal_status in(2,4,5) AND parent_id != 0");
		}
		elseif ($cate_id == "-1"){
			$n_cate_id = 0;
			$condition .= "AND deal_status in(4,5) ";
			$orderby = "success_time DESC,sort DESC,id DESC";
		}
		//edit by wangyiming 20131202 去掉全文索引
		//
		if($repay_time_search > 0){
			$condition .=" AND repay_time = ".$repay_time_search;
		}
		if($loan_type_search >= 0){
			$condition .=" AND type_id = ".$loan_type_search;
		}
		if($repay_type_search > 0){
			$condition .=" AND loantype = ".$repay_type_search;
		}
		if($deal_agency_search > 0){
			$condition .=" AND agency_id = ".$deal_agency_search;
		}
		if($warrant_search >0){
			$condition .=" AND warrant = ".$warrant_search;
		}
	
		if($level > 0){
			$point  = $level_list['point'][$level];
			$condition .= " AND user_id in(SELECT u.id FROM ".DB_PREFIX."user u LEFT JOIN ".DB_PREFIX."user_level ul ON ul.id=u.level_id WHERE ul.point >= $point)";
		}
	
		if($interest > 0){
			$condition .= " AND rate >= ".$interest;
		}
	
		if($months > 0){
			if($months==12)
				$condition .= " AND repay_time <= ".$months;
			elseif($months==18)
			$condition .= " AND repay_time >= ".$months;
		}
	
		if($lefttime_search > 0){
			$condition .= " AND (start_time + enddate*24*3600 - ".get_gmtime().") <= ".$lefttime_search*24*3600;
		}
	
		$result = get_deal_list($limit,$n_cate_id,$condition,$orderby);
		foreach($result['list'] as $key => $dv){ // 重新格式化一些数据
			$result['list'][$key]['name'] = msubstr($dv['name'],0,20);
			$result['list'][$key]['old_name'] = $dv['name'];
			if($dv['deal_status'] <> 1 || $dv['remain_time'] <= 0){
				$result['list'][$key]['remain_time_format'] = "0".$GLOBALS['lang']['DAY']."0".$GLOBALS['lang']['HOUR']."0".$GLOBALS['lang']['MIN'];
			}
			else{
				$d = intval($dv['remain_time']/86400);
				$h = floor($dv['remain_time']%86400/3600);
				$m = floor($dv['remain_time']%3600/60);
				$result['list'][$key]['remain_time_format'] = $d.$GLOBALS['lang']['DAY'].$h.$GLOBALS['lang']['HOUR'].$m.$GLOBALS['lang']['MIN'];
			}
			$result['list'][$key]['need_money_detail'] = format_price($dv['need_money_decimal'],false);
			if (!empty($dv['bad_time'])){
				$curr_year = date("Y",get_gmtime());
				$database_year = date("Y",$dv['bad_time']);
				if ($database_year != $curr_year){
					$bad_time_format = 'Y年m月d日';
				}else{
					$bad_time_format = 'm月d日';
				}
					
				$result['list'][$key]['flow_standard_time'] = to_date($dv['bad_time'],$bad_time_format);
			}
			
			if (!empty($dv['success_time'])){
				$su_curr_year = date("Y",get_gmtime());
				$su_database_year = date("Y",$dv['success_time']);
				if ($su_database_year != $su_curr_year){
					$su_time_format = 'Y年m月d日';
				}else{
					$su_time_format = 'm月d日';
				}
				$result['list'][$key]['full_scale_time'] = to_date($dv['success_time'],$su_time_format);
			}
		}
		$GLOBALS['tmpl']->assign("deal_count",$result['count']);
		$GLOBALS['tmpl']->assign("deal_list",$result['list']);
		$GLOBALS['tmpl']->assign("total_money",$total_money);
		$repay_time_list = $GLOBALS['dict']['REPAY_TIME'];
		asort($repay_time_list,SORT_NUMERIC);
		$GLOBALS['tmpl']->assign("repay_time_list",$repay_time_list);
		$loan_type_list = get_loan_type();
		$GLOBALS['tmpl']->assign("loan_type_list",$loan_type_list);
		$GLOBALS['tmpl']->assign("repay_type",$GLOBALS['dict']['LOAN_TYPE']);
		$GLOBALS['tmpl']->assign("deal_agency",get_agency_list());
	
		$page = new Page($result['count'],app_conf("PAGE_SIZE"));   //初始化分页对象
		$p  =  $page->show();
		$GLOBALS['tmpl']->assign('pages',$p);
	
		$GLOBALS['tmpl']->assign("page_title",$page_title . $GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
	
		$GLOBALS['tmpl']->assign("cate_id",$cate_id);
		$GLOBALS['tmpl']->assign("keywords",htmlspecialchars($keywords));
		$GLOBALS['tmpl']->assign("deal_cate_list",$deal_cates);
		$this->set_nav("投资列表");
		$GLOBALS['tmpl']->display("page/new/deals.html");
// 		$GLOBALS['tmpl']->display("page/deals.html");
	}*/

	public function about(){
		$GLOBALS['tmpl']->caching = true;
		$GLOBALS['tmpl']->cache_lifetime = 6000;  //首页缓存10分钟

		$contract_list = $GLOBALS['db']->getAll("select id,title from ".DB_PREFIX."article where cate_id = 22 and is_delete = 0 and is_effect = 1 order by sort asc");
    foreach($contract_list as $kk=>$vv) {
      $contract_list[$kk]['url'] = url("shop","deals-about",array("id"=>$vv['id']));
    }
    // echo '<pre>';var_dump($contract_list);return;
    $GLOBALS['tmpl']->assign("contract_list", $contract_list);

    if ( !(isset($_REQUEST['id']) && is_numeric(intval($_REQUEST['id']))) ) {
      $id = $GLOBALS['db']->getOne("select a.id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where ac.id = 22 order by a.sort asc");
      $id = intval($id);
    } else {
    	$id = intval($_REQUEST['id']);
    }
    $article = get_article($id);
    if ($article['cate_id'] != 22) {
    	$id = $GLOBALS['db']->getOne("select a.id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where ac.id = 22 order by a.sort asc");
    	$id = intval($id);
    }

    $cache_id = md5(MODULE_NAME.ACTION_NAME.trim($_REQUEST['id']).$GLOBALS['deal_city']['id']);

    if (!$GLOBALS['tmpl']->is_cached('page/deals_about.html', $cache_id)) {
	    if(!$article)
	    {
	      return app_redirect(APP_ROOT."/");
	    }   
	    else
	    {
	      if(check_ipop_limit(get_client_ip(),"article",60,$article['id']))
	      {
	        //每一分钟访问更新一次点击数
	        $GLOBALS['db']->query("update ".DB_PREFIX."article set click_count = click_count + 1 where id =".$article['id']);
	      }
	      
	      if($article['rel_url']!='')
	      {
	        if(!preg_match ("/http:\/\//i", $article['rel_url']))
	        {
	          if(substr($article['rel_url'],0,2)=='u:')
	          {
	            return app_redirect(parse_url_tag($article['rel_url']));
	          }
	          else
	          return app_redirect(APP_ROOT."/".$article['rel_url']);
	        }
	        else
	        return app_redirect($article['rel_url']);
	      }
	    }
	    $GLOBALS['tmpl']->assign("article",$article);
	    $seo_title = $article['seo_title']!=''?$article['seo_title']:$article['title'];
	    $GLOBALS['tmpl']->assign("page_title",$seo_title);
	    $seo_keyword = $article['seo_keyword']!=''?$article['seo_keyword']:$article['title'];
	    $GLOBALS['tmpl']->assign("page_keyword",$seo_keyword.",");
	    $seo_description = $article['seo_description']!=''?$article['seo_description']:$article['title'];
	    $GLOBALS['tmpl']->assign("page_description",$seo_description.",");
	  }
	  $GLOBALS['tmpl']->display("page/deals_about.html",$cache_id);
	}
}
?>
