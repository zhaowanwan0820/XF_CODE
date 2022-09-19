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

use libs\base\ComponentFactory;

//[CODE_BLOCK_START][app/Lib/SiteApp.class.php]

class SiteApp
{
    private static $instance;
    public static $config;
    private static $comps = array();
    private $module_obj;

    //网站项目构造
    public function run(){
        $this->setChannel();

        $this->route->parseRequest();

        //if(!$this->route->succeed){
        //    $this->routeOld();
        //}

     //   slogs::set("host",$_SERVER['HTTP_HOST']);
     //   slogs::set("user",isset($_SESSION['fanweuser_info']['user_name'])?$_SESSION['fanweuser_info']['user_name']:'nouser');
     //   slogs::write();
    }

    /**
     * 旧的路由处理方法
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function routeOld()
    {
        if(isset($GLOBALS['pay_req'][CTL]))
            $_REQUEST[CTL] = $GLOBALS['pay_req'][CTL];
        if(isset($GLOBALS['pay_req'][ACT]))
            $_REQUEST[ACT] = $GLOBALS['pay_req'][ACT];

        $module = strtolower(isset($_REQUEST[CTL])?$_REQUEST[CTL]:"index");
        $action = strtolower(isset($_REQUEST[ACT])?$_REQUEST[ACT]:"index");

        //增加对/index.php?ctl=user-register&icode=dMij这类url的兼容
        if (isset($_REQUEST[CTL]) && strpos($_REQUEST[CTL],"-")!== false){
            if (preg_match('@^[a-z0-9]+\-[a-z0-9]+$@', $_REQUEST[CTL])){
                list($module, $action) = explode("-", $_REQUEST[CTL]);
            }
        }

        //路由判断，只支持允许的module
        if ($module != 'index' && !in_array($module, $GLOBALS['sys_config']['MODULE_ALLOW'])){
            //$module = "index";
            return app_redirect("404.html");
        }
        if (!FP::import('module.'.$module,'','Module.class.php')){
            $module = "index";
        }
        FP::import('module.'.$module,'','Module.class.php');

        if(!method_exists($module."Module",$action))
        $action = "index";

        if(!defined("MODULE_NAME"))
            define("MODULE_NAME",$module);
        define("ACTION_NAME",$action);

        $module_name = $module."Module";
        if (class_exists($module_name)) {
            $this->module_obj = new $module_name;
            $this->module_obj->$action();
        }
    }

    //设置全局推广标记
    private function setChannel(){

        $rootDomain = '.'.implode('.', array_slice((explode('.', get_host())), -2));

        $sessionId = session_id();
        if(!isset($_COOKIE['FSESSID']) || $sessionId != $_COOKIE['FSESSID']){
            setcookie('FSESSID', $sessionId, 0, '/', $rootDomain, false, true);
        }

        $channel = isset($_GET['cn']) ? $_GET['cn'] : "";
        $channel = preg_replace("#[^A-Za-z0-9]#",'',$channel);
        if ($channel) {
            setcookie(\core\service\coupon\CouponService::LINK_COUPON_KEY, $channel, 0, '/', $rootDomain);
            //es_cookie::set('channel', $channel);
        }
        $fromPlatform = isset($_REQUEST['from_platform']) ? $_REQUEST['from_platform'] : "";
        $fromPlatform = preg_replace("#[^A-Za-z0-9_]#",'',$fromPlatform);
        if ($fromPlatform) {
            setcookie('from_platform', $fromPlatform, time()+259200, '/', $rootDomain);
        }

        //euid 为广告联盟渠道站长的id
        $euid = isset($_GET['euid']) ? $_GET['euid'] : "";
        $euid = preg_replace("#[^A-Za-z0-9_]#",'',$euid);
        if ($euid) {
            strlen($euid) > 200 ? $euid = substr($euid, 0, 200) : '';
            header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
            setcookie('euid', $euid, time()+259200, '/', $rootDomain);
            $_COOKIE['euid'] = $euid;
        }

        $wapapp = isset($_REQUEST['wapapp']) ? trim($_REQUEST['wapapp']) : '';
        if (!empty($wapapp)) {
            header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
            setcookie("WAPAPP", $wapapp, time() + 259200, '/', $rootDomain);
            $_COOKIE['WAPAPP'] = $wapapp;
        }

        $eventIntroHidden = isset($_GET['event_intro_hidden']) ? trim($_GET['event_intro_hidden']) : '';
        if (!empty($eventIntroHidden)) {
            header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
            setcookie('event_intro_hidden', $eventIntroHidden, time() + 24 * 60 * 60, '/', $rootDomain);
        }

        $eventCnHidden = isset($_GET['event_cn_hidden']) ? trim($_GET['event_cn_hidden']) : '';
        if (!empty($eventCnHidden)) {
            header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
            setcookie('event_cn_hidden', $eventCnHidden, time() + 24 * 60 * 60, '/', $rootDomain);
        }

        //全局浏览器id
        if(!isset($_COOKIE['wxid'])){
            $ua = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'';
            $ua = substr($ua, 0, 10).substr($ua, -10);
            $wxid = sprintf("%s|%s|%s", get_client_ip(), $ua, time());
            $encryptWxid = \libs\utils\Aes::encode($wxid, 'a0b923820dcc509a');
            setcookie('wxid', $encryptWxid, time()+315360000, '/', $rootDomain);
        }

    }

    public static function init($config = array()) {
        if(empty($config)){
            $config = $GLOBALS['components_config'];
        }
        if(self::$instance === null && !empty($config)){
            self::$instance = new SiteApp();
            self::$config = $config;
        }
        return self::$instance;
    }

    public function __get($name)
    {
        return $this->getComponent($name);
    }

    public function getComponent($name){
        $comp_instance = isset(self::$comps[$name]) ? self::$comps[$name] : null;
        if($comp_instance === null)
        {
            $comp_config = self::$config['components'][$name];
            $classname = $comp_config['class'];
            if($classname){
                $comp_instance = ComponentFactory::create($classname, $comp_config);
            }
            self::$comps[$name] = $comp_instance;
        }
        return $comp_instance;
    }

    // public function __destruct()
    // {
    //     unset($this);
    // }
}



//[/CODE_BLOCK_END]






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








