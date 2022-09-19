<?php
/**
 * Url class file
 * 生成url类
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace libs\web;

class Url {

    // todo 处理数组
    public static function gene($module, $action, $param=array(),$flag =false, $is_admin=false) {
        $url = $action ? "/" . $module . "/" . $action : "/" . $module;
        if ($param) {
            if (is_string($param) || is_int($param)) {
                $url .= "/" . $param;
            } elseif (is_array($param)) {
                $url .= '?'.http_build_query($param);
            }
        }
        if (!$url) {
            $url = "/";
        }

        $url = $flag ? $url : self::getDomain().$url;
        if ($is_admin) {
            $url = str_replace("admin.", "", $url);
        }
        return $url;
    }

    /**
     * 取得当前请求的协议类型
     *
     * @return string
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public static function getHttp()
    {
        if (isset($_SERVER['HTTP_XHTTPS']) && 1 == $_SERVER['HTTP_XHTTPS']) {
            return 'https://';
        } else {
            return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
        }
    }

    public static function getDomain()
    {
        /* 协议 */
        $protocol = self::getHttp();

        /* 域名或IP地址 */
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))
        {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        elseif (isset($_SERVER['HTTP_HOST']))
        {
            $host = $_SERVER['HTTP_HOST'];
        }
        else
        {
            /* 端口 */
            if (isset($_SERVER['SERVER_PORT']))
            {
                $port = ':' . $_SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol))
                {
                    $port = '';
                }
            }
            else
            {
                $port = '';
            }

            if (isset($_SERVER['SERVER_NAME']))
            {
                $host = $_SERVER['SERVER_NAME'] . $port;
            }
            elseif (isset($_SERVER['SERVER_ADDR']))
            {
                $host = $_SERVER['SERVER_ADDR'] . $port;
            }
        }

        return $protocol . $host;
    }

    /**
     * 根据后台配置和server变量
     * @param string $control 模块
     * @param string $action 模块对应的方法
     * @param int $type 整型 @see libs/common/app.php getIsHttps
     */
    public static function getConfDomain($control,$action,$type=1){
        if (empty($control) || empty($action)) return false;

        $is_https = getIsHttps($control,$action,$type);
        $domain = self::getDomain();
        // 转发机自定server变量
        $server_http = empty($_SERVER['HTTP_XHTTPS']) ? 0 : 1;
        $server_http = intval($server_http);
        // 后台配置是https，server变量不是
        if (!empty($is_https['protocol']) && $server_http===0){
           $domain = str_replace('http://', 'https://', $domain);
        }
        // 后台配置不是https，server变量是
        if (empty($is_https['protocol']) && $server_http===1){
            $domain = str_replace('https://', 'http://', $domain);
        }
        // 转发机传过来的都是http
        if (!empty($is_https['protocol']) && $server_http===1){
            $domain = str_replace('http://', 'https://', $domain);
        }
        // 都不是https
        if (empty($is_https['protocol']) && $server_http===0){
            $domain = str_replace('https://', 'http://', $domain);
        }
        return $domain;
    }
    /**
     * 根据配置获取当前http协议
     * @return string http:// | https://
     */
    public static function getConfHttpProtocol(){
        $ret = 'http://';
        $switch_https = empty($GLOBALS['sys_config']['IS_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['IS_HTTPS']);
        // 0是关闭，1是部分开启
        switch($switch_https){
            case 2:
            case 3:
                $ret = 'https://';
            break;
            default:
            break;
        }

        return $ret;
    }
    /**
     * 去除数组中http链接的协议头，变为“//wwww.firstp2p.com/xxx”形式
     **/
    public static function formatUrlListForDualProtocol(&$list, $key){
        if (empty($list) || empty($key)) {
            return false;
        }
        foreach ($list as &$item) {
            //$item[$key] = self::formatUrlForDualProtocol($item[$key]);
        }
        return $list;
    }

    /**
     * 去除http链接的协议头，变为“//wwww.firstp2p.com/xxx”形式
     **/
    public static function formatUrlForDualProtocol($url){
        return empty($url) ? '' :  str_ireplace('http:'.\SiteApp::init()->asset->getStaticHost(), \SiteApp::init()->asset->getStaticHost(), $url);
    }


}
