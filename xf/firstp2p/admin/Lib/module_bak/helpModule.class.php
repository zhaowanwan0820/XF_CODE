<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

FP::import("app.page");
class helpModule extends SiteBaseModule
{
	public function index()
	{			
		$GLOBALS['tmpl']->caching = true;
		$cache_id  = md5(MODULE_NAME.ACTION_NAME.trim($_REQUEST['id']).$GLOBALS['deal_city']['id']);	
                $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
                if(empty($site_id))$site_id = 1;	
		if (!$GLOBALS['tmpl']->is_cached('page/help_index.html', $cache_id))	
		{                    
                    $hc_list = $GLOBALS['db']->getAll("select id,title from ".DB_PREFIX."article_cate where type_id = 1 and site_id = '{$site_id}' and is_delete = 0 order by sort asc");
                    foreach ($hc_list as $k=>$v) {
                      $help_cate_list = $GLOBALS['db']->getAll("select id,title,rel_url,uname from ".DB_PREFIX."article where cate_id = ".$v['id']
                              ." and site_id = '{$site_id}' and is_delete = 0 and is_effect = 1 order by sort desc");
                      foreach($help_cate_list as $kk=>$vv)
                      {
                        if($vv['rel_url']!='')
                        {
                          if(!preg_match ("/http:\/\//i", $vv['rel_url']))
                          {
                            if(substr($vv['rel_url'],0,2)=='u:')
                            {
                              $help_cate_list[$kk]['url'] = parse_url_tag($vv['rel_url']);
                            }
                            else
                            $help_cate_list[$kk]['url'] = APP_ROOT."/".$vv['rel_url'];
                          }
                          else
                          $help_cate_list[$kk]['url'] = $vv['rel_url'];

                          $help_cate_list[$kk]['new'] = 1;
                        }
                        else
                        {
                          if($vv['uname']!='')
                          $hurl = url("shop","help",array("id"=>$vv['uname']));
                          else
                          $hurl = url("shop","help",array("id"=>$vv['id']));
                          $help_cate_list[$kk]['url'] = $hurl;
                        }
                        $help_cate_list[$kk]['title'] = $this->_replace_site_text($vv['title']);
                      }
                      $hc_list[$k]['sub'] = $help_cate_list;
                    }
                    $GLOBALS['tmpl']->assign("hc_list", $hc_list);
			$id = intval($_REQUEST['id']);
			$uname = addslashes(trim($_REQUEST['id']));
			
			if($id==0&&$uname=='')
			{
				$id = $GLOBALS['db']->getOne("select a.id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where ac.type_id = 1 order by a.sort desc");
			}
			elseif($id==0&&$uname!='')
			{
				$id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."article where uname = '".$uname."'"); 
			}		
			$article = get_article($id);		
	
			if(!$article||$article['type_id']!=1)
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
		$GLOBALS['tmpl']->display("page/help_index.html",$cache_id);
	}
    private function _replace_site_text($str) {
        if (empty($str)) {
            return $str;
        }
        $site_domain = $GLOBALS['sys_config']['site_domain']['TPL_SITE_DIR'];
        return str_ireplace(array('网信理财', 'www.firstp2p.com'), array(app_conf('SHOP_TITLE'), $site_domain), $str);
    }
}
?>