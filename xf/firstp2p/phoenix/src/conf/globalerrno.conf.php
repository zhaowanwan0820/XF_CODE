<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 12:28:32
 * @encode UTF-8编码
 */
class P_Conf_Globalerrno {

    const OK = 0;
    //1000以上（含）都是框架错误
    const FORM_CHECK_ERROR = 1000;
    const DISPATCHER_ERROR = 1001;
    const INVALID_VAR_FILTER = 1002;
    const INVALID_RPC_CALL = 1003;
    const USER_NEED_LOGIN = 1004;
    //2000以上（含）都是数据库相关错误
    const INTERNAL_DATABASE_ERROR = 2000;
    //3000以上（含）都是cache相关错误
    const INTERNAL_CACHE_ERROR = 3000;
    const INVALID_CACHE_EXECUTION = 3001;
    //4000以上（含）都是DAO层相关错误
    const INTERNAL_DAO_ERROR = 4000;
    const INVALID_DATABASE_ENGINE = 4001;
    const INVALID_CACHE_ENGINE = 4002;
    const INVALID_DAO_CONFIG = 4003;
    //5000以上（含）都是DATA层相关错误
    const INTERNAL_DATA_ERROR = 5000;
    //6000以上（含）都是logic层相关错误
    const INTERNAL_LOGIC_ERROR = 6000;
    //7000以上（含）都是模板相关错误
    const INTERNAL_TEMPLATE_ERROR = 7000;
    const INVALID_TEMPLATE_ENGINE = 7001;
    const CANNOT_WRITE_FILE = 7002;
    const INVALID_TEMPLATE_FILE = 7003;

    public static $message = array(
        self::OK => 'success',
        //1000以上（含）都是框架错误
        self::FORM_CHECK_ERROR => 'form check error',
        self::DISPATCHER_ERROR => 'dispatcher error',
        self::INVALID_VAR_FILTER => 'invalid var filter',
        self::INVALID_RPC_CALL => 'invalid rpc call',
        //2000以上（含）都是数据库相关错误
        self::INTERNAL_DATABASE_ERROR => 'internal database error',
        //3000以上（含）都是cache相关错误
        self::INTERNAL_CACHE_ERROR => 'internal cache error',
        self::INVALID_CACHE_EXECUTION => 'invalid cache execution',
        //4000以上（含）都是DAO层相关错误
        self::INTERNAL_DAO_ERROR => 'internal dao error',
        self::INVALID_DATABASE_ENGINE => 'invalid database engine',
        self::INVALID_CACHE_ENGINE => 'invalid cache engine',
        self::INVALID_DAO_CONFIG => 'invalid dao config',
        //5000以上（含）都是DATA层相关错误
        self::INTERNAL_DATA_ERROR => 'internal data service error',
        //6000以上（含）都是logic层相关错误
        self::INTERNAL_LOGIC_ERROR => 'internal logic service error',
        //7000以上（含）都是模板相关错误
        self::INTERNAL_TEMPLATE_ERROR => 'internal template error',
        self::INVALID_TEMPLATE_ENGINE => 'invalid template engine',
        self::CANNOT_WRITE_FILE => "can't write file",
        self::INVALID_TEMPLATE_FILE => 'invalid template file',
    );

}
