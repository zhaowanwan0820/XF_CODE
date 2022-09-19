<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class CacheAction extends CommonAction {

    protected static $app_runtime_root;

    protected static $static_root;

    protected static $site_list; //1:firstp2p; 2:diyifangdasi; 3:gongchang; 4:mulandai

    protected static $tpl_site_list; //1:default; 2:diyifangdasi; 3:gongchang; 4:mulandai

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
            @unlink($app_runtime_path . "app/deal_cate_conf.js");
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
            clear_dir_file($app_runtime_path . "app/deal_region_conf/");

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
            $list = \core\dao\DealModel::instance()->findAllViaSlave('`deal_status` = 1', true, 'DISTINCT(`project_id`) AS project_id');
            foreach($list as $row){
                SiteApp::init()->dataCache->removeOne(new \libs\rpc\Rpc(), 'local', array('DealProjectService\getProInfo', array('id' => $row['project_id'], 'deal_id' => $row['id'])), 1);
            }
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
        //同步，supplier_location表, deal表, youhui表, event表 , supplier 表
        //总数
        $page = intval($_REQUEST['p']) == 0 ? 1 : intval($_REQUEST['p']);
        if ($page == 1)
            syn_dealing();
        $page_size = 5;
        $location_total = M("SupplierLocation")->count();
        $deal_total = M("Deal")->count();
        $youhui_total = M("Youhui")->count();
        $event_total = M("Event")->count();
        $supplier_total = M("Supplier")->count();
        $count = max(array($location_total, $deal_total, $youhui_total, $event_total, $supplier_total));

        $limit = ($page - 1) * $page_size . "," . $page_size;
        $location_list = M("SupplierLocation")->limit($limit)->findAll();
        foreach ($location_list as $v) {
            syn_supplier_location_match($v['id']);
        }
        $supplier_list = M("Supplier")->limit($limit)->findAll();
        foreach ($supplier_list as $v) {
            syn_supplier_match($v['id']);
        }
        $deal_list = M("Deal")->limit($limit)->findAll();
        foreach ($deal_list as $v) {
            syn_deal_match($v['id']);
        }
        $youhui_list = M("Youhui")->limit($limit)->findAll();
        foreach ($youhui_list as $v) {
            syn_youhui_match($v['id']);
        }
        $event_list = M("Event")->limit($limit)->findAll();
        foreach ($youhui_list as $v) {
            syn_event_match($v['id']);
        }

        if ($page * $page_size >= $count) {
            $this->assign("jumpUrl", U("Cache/index"));
            $ajax = intval($_REQUEST['ajax']);
            clear_auto_cache("cache_deal_cart");
            $data['status'] = 1;
            $data['info'] = "<div style='line-height:50px; text-align:center; color:#f30;'>同步成功</div><div style='text-align:center;'><input type='button' onclick='$.weeboxs.close();' class='button' value='关闭' /></div>";
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($data));
        } else {
            $total_page = ceil($count / $page_size);
            $data['status'] = 0;
            $data['info'] = "共" . $total_page . "页，当前第" . $page . "页,等待更新下一页记录";
            $data['url'] = U("Cache/syn_data", array("p" => $page + 1));
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($data));
        }
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
        clear_auto_cache("cache_nav_list");
    }

    private function clear_cache_return() {
        header("Content-Type:text/html; charset=utf-8");
        exit("<div style='line-height:50px; text-align:center; color:#f30;'>" . L('CLEAR_SUCCESS') . "</div><div style='text-align:center;'><input type='button' onclick='$.weeboxs.close();' class='button' value='关闭' /></div>");
    }
}

