<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-11 12:06:20
 * @encode UTF-8编码
 */
M::D('FRAMEWORK_PATH', rtrim(str_replace('\\', '/', dirname(__FILE__)), '/') . '/');
M::R(M::D('FRAMEWORK_PATH') . '/conf/autoload.conf.php');

class P_Autoload {

    private static $_require_cache = array();

    public static function autoload($class) {
        if (isset(self::$_require_cache[$class])) {
            return true;
        }
        $paths = array(
            M::D('FRAMEWORK_PATH'), P_Conf_Autoload::$default_inffix, $class,
        );
        if (false !== strpos($class, P_Conf_Autoload::CLASS_NAME_GLUE)) {
            $class = strtolower($class);
            $paths = explode(P_Conf_Autoload::CLASS_NAME_GLUE, $class);
            if (isset(P_Conf_Autoload::$path_map[$paths[0]])) {
                $paths[0] = P_Conf_Autoload::$path_map[$paths[0]];
            }
            if (strnatcasecmp(P_Conf_Autoload::FRAMEWORK_PREFIX, $paths[0])) {
                $paths = array_merge(array(M::D('APP_ROOT_PATH')), $paths);
            } else {
                $paths[0] = M::D('FRAMEWORK_PATH');
            }
        }
        foreach (P_Conf_Autoload::$extension as $extension) {
            $file = implode(P_Conf_Autoload::PATH_GLUE, $paths) . $extension;
            if (file_exists($file)) {
                M::R($file);
                self::$_require_cache[$class] = $file;
                break;
            }
        }
        return true;
    }

    public static function init() {
        $app_autoload = M::C('autoload');
        $autoload_rules = P_Conf_Autoload::$default_autoload;
        if (is_array($app_autoload)) {
            $autoload_rules = array_merge($autoload_rules, $app_autoload);
        }
        foreach ($autoload_rules as $autoload) {
            if (!is_array($autoload) || empty($autoload) || !isset($autoload[P_Conf_Autoload::RULE_PATH])) {
                continue;
            }
            !isset($autoload[P_Conf_Autoload::RULE_RECURSION]) ? $autoload[P_Conf_Autoload::RULE_RECURSION] = false : null;
            self::_require_files($autoload[P_Conf_Autoload::RULE_PATH], $autoload[P_Conf_Autoload::RULE_RECURSION]);
        }
    }

    private static function _require_files($path, $recursion = false) {
        if (is_file($path)) {
            M::R($path);
            return true;
        }
        if (is_dir($path)) {
            $handle = @opendir($path);
            while (false !== ($file = readdir($handle))) {
                if ($file == "." || $file == ".." || false !== strpos($file, '.svn')) {
                    continue;
                }
                $name = $path . P_Conf_Autoload::PATH_GLUE . $file;
                if (is_file($name) || (is_dir($name) && $recursion)) {
                    self::_require_files($name, $recursion);
                }
            }
            closedir($handle);
        }
        return true;
    }

}

spl_autoload_register("P_Autoload::autoload");
P_Autoload::init();
