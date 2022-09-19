<?php

// +----------------------------------------------------------------------
// | firstp2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.firstp2p.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: laijinhai@ucfgroup
// +----------------------------------------------------------------------
// | Update: 2013-10-29
// +----------------------------------------------------------------------
namespace libs\common;
use libs\base\ComponentFactory;

//[CODE_BLOCK_START][app/Lib/SiteBaseModule.class.php]

class SiteBaseModule{
    public function __construct()
    {
        $GLOBALS['tmpl']->assign("MODULE_NAME",MODULE_NAME);
        $GLOBALS['tmpl']->assign("ACTION_NAME",ACTION_NAME);

        $GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/page_static_cache/");
        $GLOBALS['dynamic_cache'] = $GLOBALS['fcache']->get("APP_DYNAMIC_CACHE_".APP_INDEX."_".MODULE_NAME."_".ACTION_NAME);
        $GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/avatar_cache/");
        $GLOBALS['dynamic_avatar_cache'] = $GLOBALS['fcache']->get("AVATAR_DYNAMIC_CACHE"); //头像的动态缓存

        //输出导航菜单
        $nav_list = get_nav_list();
        $nav_list= init_nav_list($nav_list, true);
        foreach($nav_list as $k=>$v){
            $nav_list[$k]['sub_nav'] = init_nav_list($v['sub_nav'], false);
        }
        $GLOBALS['tmpl']->assign("nav_list",$nav_list);



        //输出在线客服与时间
        $qq = explode("|",app_conf("ONLINE_QQ"));
        $msn = explode("|",app_conf("ONLINE_MSN"));
        $GLOBALS['tmpl']->assign("online_qq",$qq);
        $GLOBALS['tmpl']->assign("online_msn",$msn);

        //输出页面的标题关键词与描述
        $GLOBALS['tmpl']->assign("site_info",get_site_info());

        //输出系统文章
        $system_article = get_article_list(8,0,"ac.type_id = 3","",true);
        $GLOBALS['tmpl']->assign("system_article",$system_article['list']);

        //输出帮助
        $deal_help = get_help();
        $GLOBALS['tmpl']->assign("deal_help",$deal_help);



        //输出热门关键词
        $hot_kw = app_conf("SHOP_SEARCH_KEYWORD");
        $hot_kw = preg_split("/[ ,]/i",$hot_kw);
        $GLOBALS['tmpl']->assign("hot_kw",$hot_kw);

        if(MODULE_NAME=="acate"&&ACTION_NAME=="index"||
        MODULE_NAME=="article"&&ACTION_NAME=="index"||
        MODULE_NAME=="cate"&&ACTION_NAME=="index"||
        MODULE_NAME=="comment"&&ACTION_NAME=="index"||
        MODULE_NAME=="help"&&ACTION_NAME=="index"||
        MODULE_NAME=="link"&&ACTION_NAME=="index"||
        MODULE_NAME=="mobile"&&ACTION_NAME=="index"||
        MODULE_NAME=="msg"&&ACTION_NAME=="index"||
        MODULE_NAME=="notice"&&ACTION_NAME=="index"||
        MODULE_NAME=="notice"&&ACTION_NAME=="list_notice"||
        MODULE_NAME=="rec"&&ACTION_NAME=="rhot"||
        MODULE_NAME=="rec"&&ACTION_NAME=="rnew"||
        MODULE_NAME=="rec"&&ACTION_NAME=="rbest"||
        MODULE_NAME=="rec"&&ACTION_NAME=="rsale"||
        MODULE_NAME=="score"&&ACTION_NAME=="index"||
        MODULE_NAME=="space"&&ACTION_NAME=="index"||
        MODULE_NAME=="space"&&ACTION_NAME=="fav"||
        MODULE_NAME=="space"&&ACTION_NAME=="fans"||
        MODULE_NAME=="space"&&ACTION_NAME=="focus"||
        MODULE_NAME=="msg"&&ACTION_NAME=="index"||
        MODULE_NAME=="ss"&&ACTION_NAME=="index"||
        MODULE_NAME=="ss"&&ACTION_NAME=="pick"||
        MODULE_NAME=="sys"&&ACTION_NAME=="index"||
        MODULE_NAME=="sys"&&ACTION_NAME=="list_notice"||
        MODULE_NAME=="vote"&&ACTION_NAME=="index")
        {
            set_gopreview();
        }


    }

    public function index() {
        return app_redirect(url("index"));
    }

    public function display() {
        $GLOBALS['tmpl']->display("page/uc_v1.html");
    }

    /**
     * 设置面包屑
     * @param string|array $text
     */
    public function set_nav($text) {
        if (!is_array($text)) {
            $arr = array(
                "text" => $text,
            );
            $nav = array($arr);
        } else {
            foreach ($text as $k => $v) {
                if (is_numeric($k)) {
                    $nav[] = array("text" => $v);
                } else {
                    $nav[] = array("url" => $v, "text" => $k);
                }
            }
        }
        $GLOBALS['tmpl']->assign("nav", $nav);
    }

    public function __destruct()
    {
        if(isset($GLOBALS['fcache']))
        {
            $GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/page_static_cache/");
            $GLOBALS['fcache']->set("APP_DYNAMIC_CACHE_".APP_INDEX."_".MODULE_NAME."_".ACTION_NAME,$GLOBALS['dynamic_cache']);
            if(count($GLOBALS['dynamic_avatar_cache'])<=500)
            {
                $GLOBALS['fcache']->set_dir(APP_RUNTIME_PATH."data/avatar_cache/");
                $GLOBALS['fcache']->set("AVATAR_DYNAMIC_CACHE",$GLOBALS['dynamic_avatar_cache']); //头像的动态缓存
            }
        }
        // unset($this);
    }


}


//[/CODE_BLOCK_END]








