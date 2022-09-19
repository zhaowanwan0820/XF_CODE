<?php

class CacheAction extends CommonAction {

    protected static $app_runtime_root;

    protected static $static_root;

    protected static $site_list;

    protected static $tpl_site_list;

    public function __construct() {
        parent::__construct();
        set_time_limit(0);
        es_session::close();
        self::$app_runtime_root = APP_ROOT_PATH . "runtime/";
        self::$static_root = APP_WEBROOT_PATH . "static/";
        self::$site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);
        self::$tpl_site_list = $GLOBALS['sys_config']['TPL_SITE_LIST'];
    }


    /**
     * 清空程序的后台缓存
     *
     * @param bool $is_exit 是否返回页面
     */
    public function clear_admin($is_exit = true) {
        clear_dir_file(RUNTIME_PATH . "Cache/");
        clear_dir_file(RUNTIME_PATH . "Data/_fields/");
        clear_dir_file(RUNTIME_PATH . "Temp/");
        clear_dir_file(RUNTIME_PATH . "Logs/");
        @unlink(RUNTIME_PATH . "~app.php");
        @unlink(RUNTIME_PATH . "~runtime.php");
        @unlink(APP_STATIC_PATH . "lang.js");
        @unlink(APP_RUNTIME_PATH . "app/config_cache.php");

        if ($is_exit) {
            $this->clear_cache_return();
        }

    }

    /**
     * 清空网站的javascript以及CSS样式缓存文件
     *
     * @param bool $is_exit 是否返回页面
     */
    public function clear_parse_file($is_exit = true) {
        foreach (self::$tpl_site_list as $tpl_dir) {
            $static_path = self::$static_root . $tpl_dir . "/";
            clear_dir_file($static_path . "/cache/");
            @unlink($static_path . "/lang.js");
        }

        foreach (self::$site_list as $site_name) {
            $app_runtime_path = self::$app_runtime_root . $site_name . "/";
            clear_dir_file($app_runtime_path . "app/tpl_caches/");
            clear_dir_file($app_runtime_path . "app/tpl_compiled/");
        }

        if ($is_exit) {
            $this->clear_cache_return();
        }
    }

    /**
     * is_all=1:清空程序的所有数据缓存; is_all=0:清空程序的临时数据缓存
     *
     * @param bool $is_exit 是否返回页面
     */
    public function clear_data() {
        foreach (self::$site_list as $site_name) {
            $app_runtime_path = self::$app_runtime_root . $site_name . "/";
            clear_dir_file($app_runtime_path . "app/data_caches/");
            clear_dir_file($app_runtime_path . "app/db_caches/");

            if (intval($_REQUEST['is_all']) == 1) {
                clear_dir_file($app_runtime_path . "data/");
            }
        }


        // todo
        $GLOBALS['cache']->clear();
        $this->clear_auto_cache();

        if (intval($_REQUEST['is_all']) == 1) {
            // 后台
            $this->clear_admin(false);
            // 样式脚本
            $this->clear_parse_file(false);

            FP::import("libs.common.site");
        }

        $this->clear_cache_return();
    }


    /**
     * 清空前台图片的各种规格缓存
     *
     * @param bool $is_exit 是否返回页面
     */
    public function clear_image($is_exit = true) {

        $path = APP_ROOT_PATH . "public/attachment/";
        $this->clear_image_file($path);
        $path = APP_ROOT_PATH . "public/images/";
        $this->clear_image_file($path);

        $this->clear_parse_file(false);

        if ($is_exit) {
            $this->clear_cache_return();
        }
    }

    public function syn_data() {
    }

    private function clear_image_file($path) {
        if ($dir = opendir($path)) {
            while ($file = readdir($dir)) {
                $check = is_dir($path . $file);
                if (!$check) {
                    if (preg_match("/_(\d+)x(\d+)/i", $file, $matches))
                        @unlink($path . $file);
                } else {
                    if ($file != '.' && $file != '..') {
                        $this->clear_image_file($path . $file . "/");
                    }
                }
            }
            closedir($dir);
            return true;
        }
    }

    /**
     * 删除相关未自动清空的数据缓存
     */
    private function clear_auto_cache() {
        //删除相关未自动清空的数据缓存
        clear_auto_cache("page_image");
        clear_auto_cache("recommend_hot_sale_list");
        clear_auto_cache("recommend_uc_topic");
        clear_auto_cache("youhui_page_recommend_youhui_list");

        clear_auto_cache("store_filter_nav_cache");
        clear_auto_cache("ytuan_filter_nav_cache");
        clear_auto_cache("tuan_filter_nav_cache");
        clear_auto_cache("fyouhui_filter_nav_cache");
        clear_auto_cache("byouhui_filter_nav_cache");
    }

    private function clear_cache_return() {
        header("Content-Type:text/html; charset=utf-8");
        exit("<div style='line-height:50px; text-align:center; color:#f30;'>" . L('CLEAR_SUCCESS') . "</div><div style='text-align:center;'><input type='button' onclick='$.weeboxs.close();' class='button' value='关闭' /></div>");
    }
}

?>