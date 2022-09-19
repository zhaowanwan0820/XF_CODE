<?php
/**
 * web config file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

/**
 * 兼容Phoenix框架的配置信息
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Conf_Web
{
    public static $db = array();  
} // END public class 

Conf_Web::$db = array(
    'web' => array(
        P_Conf_Db::DB_HOST     => app_conf('DB_HOST'),
        P_Conf_Db::DB_PORT     => app_conf('DB_PORT'),
        P_Conf_Db::DB_USER     => app_conf('DB_USER'),
        P_Conf_Db::DB_PWD      => app_conf('DB_PWD'),
        P_Conf_Db::DB_NAME     => app_conf('DB_NAME'),
        P_Conf_Db::DB_PREFIX   => rtrim(DB_PREFIX, "_"),
        P_Conf_Db::DB_ENGINE   => P_Conf_Db::ENGINE_MYSQL,
        P_Conf_Db::DB_CHARSET  => P_Conf_Db::DEFAULT_CHARSET,
        P_Conf_Db::DB_PCONNECT => false,
    ),  
);
