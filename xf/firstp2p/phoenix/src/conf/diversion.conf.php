<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 14:43:35
 * @encode UTF-8编码
 */
class P_Conf_Diversion {

    const DIVERSION_ABTEST = '_diversion_abtest';
    const DIVERSION_CUSTOM = '_diversion_custom';
    const DIVERSION_VERSION = '_diversion_version';
    const DIVERSION_VERSION_KEY = 'HTTP_DIVERSION_VERSION';

    public static $diversion_version_vars = array(
        'v', 'ver', 'version'
    );
    public static $valid_diversion = array(
        self::DIVERSION_VERSION,
        self::DIVERSION_ABTEST,
        self::DIVERSION_CUSTOM,
    );

}
