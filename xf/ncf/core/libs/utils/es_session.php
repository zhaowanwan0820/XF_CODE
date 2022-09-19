<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

//引入数据库的系统配置及定义配置函数
require_once __DIR__."/es_cookie.php";

if(!function_exists("app_conf"))
{
    require(APP_ROOT_PATH."libs/common/simple.php");use_config();
    function app_conf($name)
    {
        return stripslashes($GLOBALS['sys_config'][$name]);
    }
}

class es_session
{
    static function id()
    {
        try {
            return session_id();
        } catch (\Exception $e) {
            \libs\utils\Logger::error('SessionGetIdException. message:'.$e->getMessage());
        }
    }

    static function start()
    {

        try {
            session_set_cookie_params(0,app_conf("COOKIE_PATH"),app_conf("DOMAIN_ROOT"), false, true);
            session_start();
        } catch (\Exception $e) {
            \libs\utils\Logger::error('SessionStartException. message:'.$e->getMessage());
            return false;
        }
    }

    // 判断session是否存在
    static function is_set($name) {
        return isset($_SESSION[app_conf("AUTH_KEY").$name]);
    }

    // 获取某个session值
    static function get($name) {

        $key = app_conf("AUTH_KEY").$name;
        $value = isset($_SESSION[$key])?$_SESSION[$key]:"";
        return $value;
    }

    // 设置某个session值
    static function set($name,$value) {

        $_SESSION[app_conf("AUTH_KEY").$name] = $value;
    }

    // 删除某个session值
    static function delete($name) {

        unset($_SESSION[app_conf("AUTH_KEY").$name]);
    }

    // 清空session
    static function clear()
    {
        try {
            session_destroy();
        } catch (\Exception $e) {
            \libs\utils\Logger::error('SessionDestroyException. message:'.$e->getMessage());
            return false;
        }
    }

    //关闭session的读写
    static function close()
    {
        try {
            session_write_close();
        } catch (\Exception $e) {
            \libs\utils\Logger::error('SessionWriteCloseException. message:'.$e->getMessage());
            return false;
        }
    }

    static function  is_expired()
    {
        if (isset($_SESSION[app_conf("AUTH_KEY")."expire"]) && $_SESSION[app_conf("AUTH_KEY")."expire"] < get_gmtime()) {
            return true;
        } else {
            $_SESSION[app_conf("AUTH_KEY")."expire"] = get_gmtime()+(intval(app_conf("EXPIRED_TIME"))*60);
            return false;
        }
    }
}
?>
