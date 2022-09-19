<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 14:15:13
 * @encode UTF-8编码
 */
class P_Conf_Autoload {

    const CLASS_NAME_GLUE = '_';
    const CONTROLLER_PREFIX = 'C';
    const CONFIG_PREFIX = 'Conf';
    const DAO_PREFIX = 'Dao';
    const DATA_SERVICE_PREFIX = 'MD';
    const FRAMEWORK_PREFIX = 'P';
    const LIBS_PREFIX = 'Libs';
    const LOGIC_SERVICE_PREFIX = 'MS';
    const MODELS_LIBS_PREFIX = 'ML';
    const PATH_GLUE = '/';
    const RULE_PATH = 0;
    const RULE_RECURSION = 1;

    public static $default_autoload = array();
    public static $default_inffix = 'components';
    public static $extension = array('.php', '.conf.php');
    public static $path_map = array(
        'ms' => 'models/services',
        'ml' => 'models/libs',
        'md' => 'models/data',
        'dao' => 'models/dao',
        'c' => 'controllers',
        'v' => 'views',
    );

}

P_Conf_Autoload::$default_autoload = array();
