<?php

function use_config() {
    $config_array = array(
        'env' => 1,
        'common' => 1,
        'dictionary' => "dict", //变量的别名
        'contract_lang' => 'contract',
        'system' => 1,
        'db' => 1,
        'components' => "components_config",
        'h5Union' => "h5Union",
        'db_hash' => "db_hash",
    );

    $sys_config = array();
    $confPath = ROOT_PATH.'config'.DIRECTORY_SEPARATOR;
    foreach ($config_array as $k => $v) {
        $conf_file = $confPath . $k . ".conf.php";
        if ($k == "system") {
            $conf_file = $confPath . $k . "_" . $sys_config['APP_SITE'] . ".conf.php";
        }

        if (file_exists($conf_file)) {
            $config = require($conf_file);
        }

        if ($v != 1) {
            $GLOBALS[$v] = $config;
        }
        $sys_config = array_merge($sys_config, $config);
    }
    if (defined("ADMIN_ROOT")) {
        $config = require($confPath . "system_firstp2p.conf.php");
        $sys_config = array_merge($sys_config, $config);
    }
    //默认站点id的匹配逻辑
    $sys_config['TEMPLATE_ID'] = $sys_config['TEMPLATE_LIST'][$sys_config['APP_SITE']];

    //分站样式目录名称 --20131223
    $sys_config['TPL_SITE_DIR'] = 'default';

    //统一一套模板 --20131223
    $sys_config['TEMPLATE'] = 'default';

    $GLOBALS['sys_config'] = $sys_config;
}

function use_config_db()
{
    $site_id = app_conf('TEMPLATE_ID');
    //获取配置的最后更新时间
    $lastUpdateTime = 0;

    try {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            $lastUpdateTime = $redis->get('conf_last_update_time');
        }
    } catch (\Exception $e) {
        \libs\utils\Logger::error('use_config_db get conf_last_update_time failed. message:'.$e->getMessage());
    }

    $key = 'file_cache_for_conf';
    $value = \libs\utils\FileCache::getInstance()->get($key);

    if (empty($value) || $value['last_update_time'] != $lastUpdateTime) {
        $sql = 'SELECT name, value, site_id FROM firstp2p_conf WHERE site_id>=0 and is_effect=1';
        $conf_list = $GLOBALS['db']->get_slave()->getAll($sql);
        $value = array(
            'data' => $conf_list,
            'last_update_time' => $lastUpdateTime,
        );
        \libs\utils\FileCache::getInstance()->set($key, $value, 600);
    } else {
        $conf_list = $value['data'];
    }

    foreach ($conf_list as $k => $v) {
        $result[$v['site_id']][$v['name']] = $v['value'];
    }
    //加载数据库配置
    if (!empty($result)) {
        $GLOBALS['sys_config_db'] = $result;
        if (!empty($site_id) && !empty($result[$site_id])) {
            $GLOBALS['sys_config'] = array_merge($GLOBALS['sys_config'], $result[$site_id]); // 分站配置
            if (!empty($result[0])) {
                $GLOBALS['sys_config'] = array_merge($GLOBALS['sys_config'], $result[0]); // 公用配置
            }
        }
    }
}

class FP {

    static $_class = array();
    static $_require = array();

    private static function require_cache($file) {
        $file = realpath($file);
        if (!isset(self::$_require[$file])) {
            if (is_file($file)) {
                require($file);
                self::$_require[$file] = true;
            } else {
                self::$_require[$file] = false;
            }
        }
        return self::$_require[$file];
    }

    public static function import($class, $baseurl = '', $ext = ".php") {
        $class_file = str_replace(array('.', '#'), array('/', '.'), $class) . $ext; //die;
        if (isset(self::$_class[$baseurl . $class])) {
            return true;
        }

        if ($baseurl != "") {
            if (substr($baseurl, -1) != "/")
                $baseurl .= "/";
            $class_file = $baseurl . $class_file;
        } else {
            $class_file = ROOT_PATH.'core/'.$class_file;
        }

        if (is_file($class_file)) {

            self::$_class[$baseurl . $class] = $class_file;
            return self::require_cache($class_file);
        } else {
            return false;
        }
    }

    public static function setdir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777);
        }
    }

}
