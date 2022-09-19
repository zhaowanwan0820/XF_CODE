<?php
/**
 * Asset class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace libs\helpers;

use libs\base\Component;
use libs\base\IComponent;

/**
 * 处理静态资源的类
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Asset extends Component implements IComponent {
    /**
     * 配置文件路径
     *
     * @var string
     **/
    public $config_file;

    /**
     * 静态文件路径
     *
     * @var string
     **/
    public $static_path;

    /**
     * 加载资源文件配置
     *
     * @return void
     **/
    public function init() {
        $this->config = json_decode(file_get_contents($this->config_file));
    }

    /**
     * 输出js标签
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     * @return string
     **/
    public function renderJs($is_index=0) {
        $res = "";
        if(DEBUG){
            $src = $is_index ? $this->config->js->index_src : $this->config->js->src;
            foreach ($src as $js) {
                $js = str_replace("public/", "", $js);
                $res .= "<script type=\"text/javascript\" src=\"/".$js."\"></script>\n";
            }
        } else {
            $min = $is_index ? $this->config->js->index_min : $this->config->js->dest_min;
            $path = APP_ROOT_PATH . $min;
            $js = str_replace("public/static/", "", $min);
            $res .= "<script type=\"text/javascript\" src=\"".($this->getStaticPath($js))."\"></script>\n";
        }
        return $res;
    }

    /**
     * 输出css标签
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     * @return string
     **/
    public function renderCss($is_index=0) {
        $res = "";
        if(DEBUG){
            $src = $is_index ? $this->config->css->index_src : $this->config->css->src;
            foreach ($src as $css) {
                $css = str_replace("public/", "", $css);
                $res .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"/".$css."\" />\n";
            }
        } else {
            $min = $is_index ? $this->config->css->index_min : $this->config->css->dest_min;
            $path = APP_ROOT_PATH . $min;
            $css = str_replace("public/static/", "", $min);
            $res .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".($this->getStaticPath($css))."\" />\n";
        }
        return $res;
    }
    /**
     * 输出js标签
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>; modify by 曲晓雷 <quxiaolei@ucfgroup.com>
     * @return string
     **/
    public function renderJsV2($tag = '') {
        $res = "";
        if ($tag == '') {
            return $res;
        }

        if(DEBUG){
            $src = $this->config->js->$tag;
            foreach ($src as $js) {
                $js = str_replace("public/", "", $js);
                $res .= "<script type=\"text/javascript\" src=\"/".$js."\"></script>\n";
            }
        } else {
            $tag_min = $tag.'_min';
            $min = $this->config->js->$tag_min;
            $path = APP_ROOT_PATH . $min;
            $js = str_replace("public/static/", "", $min);
            $res .= "<script type=\"text/javascript\" src=\"".($this->getStaticPath($js))."\"></script>\n";
        }
        return $res;
    }
    /**
     * 输出css标签
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>; modify by 曲晓雷 <quxiaolei@ucfgroup.com>
     * @return string
     **/
    public function renderCssV2($tag = '') {
        $res = "";
        if ($tag == '') {
            return $res;
        }

        if(DEBUG){
            $src = $this->config->css->$tag;
            foreach ($src as $css) {
                $css = str_replace("public/", "", $css);
                $res .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"/".$css."\" />\n";
            }
        } else {
            $tag_min = $tag.'_min';
            $min = $this->config->css->$tag_min;
            $path = APP_ROOT_PATH . $min;
            $css = str_replace("public/static/", "", $min);
            $res .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".($this->getStaticPath($css))."\" />\n";
        }
        return $res;
    }
    /**
     * 输出css和js
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function renderAll($is_index=0) {
        return $this->renderCss($is_index).$this->renderJs($is_index);
    }
    /**
     * 输出css和js
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>; modify by 曲晓雷 <quxiaolei@ucfgroup.com>
     **/
    public function renderAllV2($tag='') {
        // return $this->renderCssV2($tag).$this->renderJsV2($tag);
        return $this->renderJsV2($tag);
    }

    /**
     * 静态文件服务器地址
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/

    public function getStaticRoot() {
        $request_uri = trim($_SERVER['REQUEST_URI'],'/'); // 去除前后的/
        $module_action_array = explode('/',$request_uri);
        $module = $module_action_array[0];
        $action = $module_action_array[1];

        return "//".app_conf("STATIC_DOMAIN_NAME").app_conf("STATIC_DOMAIN_ROOT").app_conf("STATIC_WEB_PATH");
    }

    /**
     * 上传文件服务器地址
     * @return string
     */
    public function getUploadRoot() {
        $i = 1; // 上传文件全走fp1域名
        return DEBUG ? "./attachment" : "//" . app_conf("UPLOAD_DOMAIN_NAME") . $i . app_conf("CDN_DOMAIN_ROOT");
    }

    /**
     * 获取新的静态文件服务器
     * @return string 返回不带协议的//www.example.com
     */
    public function getStaticHost(){

        return app_conf("STATIC_HOST");
    }

    /**
     * getStaticPath
     * 生成固定的静态文件地址
     * modify by zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-11-03
     * @param mixed $path
     * @param string $v     版本号
     * @param int $is_pname  是否需要协议名 使用场景在不是网页的情况下:如邮件客户端
     * @access private
     * @return void
     */
    private function getStaticPath($path, $v="", $is_pname=0) {
        //$is_https = checkHttps();
        //$is_proxy_https = checkHttpsFromProxy();
        //if ($is_https || $is_proxy_https) {     //https
            return "//" . app_conf("STATIC_DOMAIN_NAME") . app_conf("STATIC_DOMAIN_ROOT") . app_conf("STATIC_WEB_PATH").($v ? $v : app_conf("APP_SUB_VER")) . "/". $path ;
        /*} else {    //  http
            $n = crc32($path);
            $i = $n%9 + 1;
            $url = "//" . app_conf("CDN_DOMAIN_NAME") . $i . app_conf("CDN_DOMAIN_ROOT") . app_conf("STATIC_WEB_PATH"). ($v ? $v : app_conf("APP_SUB_VER"))  . "/". $path;
            if ( $is_pname) {
                $url = "http:".$url;
            }
            return $url;
        }*/
    }

    /**
     * 生成基于静态文件服务器的资源路径
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function makeUrl($path, $is_pname=0) {

        return DEBUG ? "/static/" . ltrim($path, '/') : $this->getStaticPath(ltrim($path, '/'), "", $is_pname);
    }

    /**
     * 生成基于静态文件服务器的资源路径
     * 在openapi的接口中给出web下静态文件的路径
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function makeWebUrl($path, $is_pname=0) {

        return DEBUG ?  "http://" . app_conf('FIRSTP2P_CN_DOMAIN') ."/static/" . ltrim($path, '/') : $this->getStaticPath(ltrim($path, '/'), "", $is_pname);
    }

    /**
     * 生成基于静态文件服务器的资源路径
     **/
    public function makeApiUrl($path) {
        return "//". $_SERVER['HTTP_HOST']."/static/". $path."?v=". app_conf("APP_SUB_VER");
    }

    /**
     * 生成基于app h5静态文件服务器的资源路径
     **/
    public function makeAppUrl($path, $is_pname=0) {

        return DEBUG ? "http://".app_conf("WXLC_DOMAIN")."/static/app/" . ltrim($path, '/') : $this->getStaticPath("app/".ltrim($path, '/'), "", $is_pname);
    }

    /**
     * 生成基于openapi 静态文件服务器的资源路径
     **/
    public function makeOpenApiUrl($path, $is_pname=0) {

        return DEBUG ? "http://".app_conf("WXLC_DOMAIN")."/static/openapi/" . ltrim($path, '/') : $this->getStaticPath("openapi/".ltrim($path, '/'), "", $is_pname);
    }
    /**
     * app中输出js标签，用于线上环境压缩静态资源，减少请求数
     *
     * @return void
     * @author 张天翔<zhangtianxiang@ucfgroup.com>;
     * @return string
     **/
    public function renderAppJsV2($tag = '') {
        $res = "";
        if ($tag == '') {
            return $res;
        }
        if(DEBUG){
            $src = $this->config->js->$tag;
            foreach ($src as $js) {
                $js = str_replace("public/static/app/", "", $js);
                $res .= "<script type=\"text/javascript\" src=\"".$this->makeAppUrl($js)."\"></script>\n";
            }
        } else {
            $tag_min = $tag.'_min';
            $min = $this->config->js->$tag_min;
            $js = str_replace("public/static/app/", "", $min);
            $res .= "<script type=\"text/javascript\" src=\"".$this->makeAppUrl($js)."\"></script>\n";
        }
        return $res;
    }

    /**
     * app中输出css标签，用于线上环境压缩静态资源，减少请求数
     *
     * @return void
     * @author 张天翔<zhangtianxiang@ucfgroup.com>;
     * @return string
     **/
    public function renderAppCssV2($tag = '') {
        $res = "";
        if ($tag == '') {
            return $res;
        }

        if(DEBUG){
            $src = $this->config->css->$tag;
            foreach ($src as $css) {
                $css = str_replace("public/static/app/", "", $css);
                $res .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->makeAppUrl($css)."\" />\n";
            }
        } else {
            $tag_min = $tag.'_min';
            $min = $this->config->css->$tag_min;
            $css = str_replace("public/static/app/", "", $min);
            $res .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->makeAppUrl($css)."\" />\n";
        }
        return $res;
    }

} // END public class Asset
