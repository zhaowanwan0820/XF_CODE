<?php
/**
 * Route class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\route;

use libs\base\Component;
use libs\base\IComponent;

/**
 * 基于正则的路由处理
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class Route extends Component implements IRoute,IComponent
{
    /**
     * 定义路由规则, 数组项的key表示匹配url的规则, value表示对应的action class规则
     * 对于命名子组，_c表示controller名称，_a表示action名称, 其他命名将会转换为get参数
     * action类的首字母必须大写
     * 示例：
     * array(
     *      '/^\/(?<_c>\w+)[\/](?<_a>\w+)/' => 'web\controllers\<_c>\<_a>',
     *      '/^\/(?<_c>\w+)/' => 'web\controllers\<_c>\Index',
     * ),
     *
     * @var array
     **/
    public $rules = array();

    /**
     * 加密用路由规则
     * @var array
     **/
    public $rules_ec = array();

    /**
     * 路由是否成功
     *
     * @var boolean
     **/
    public $succeed = false;

    /**
     * 解析请求
     *
     * @return void
     **/
    public function parseRequest()
    {
        $class_name = $this->mapRules();
        $this->runAction($class_name);
    }

    /**
     * 获取当前请求url, 不包括协议头和get参数
     *
     * @return string 当前url
     **/
    public function getCurrentUrl()
    {
        $uri_path = explode('?', $_SERVER['REQUEST_URI']);
        // 去掉部分浏览器 REQUEST_URI 有 host和端口的问题
        $url = preg_replace('#http:\/\/.*?firstp2p.com\:*\d*#i','',$uri_path[0]);
        if ($url == "/index.php") {
            $url = $this->_parseOld($url, $uri_path[1]);
        }
        return $url;
    }
    /**
     * 映射规则,将url映射至相应的类
     *
     * @return string 类名
     **/
    public function mapRules() {
        $url = $this->getCurrentUrl();
        if (APP == 'web') {//优先解析加密
            foreach ($this->rules_ec as $rule => $map) {
                $res = preg_match($rule, $url, $matches);
                if($res){
                    foreach ($matches as $m=>$v) {
                        if($m == '_c'){
                            $map = str_replace('<'.$m.'>', $v, $map);
                        }else if($m == '_a'){
                            $action = implode(array_map('ucfirst', explode('_', $v)));
                            $map = str_replace('<'.$m.'>', $action, $map);
                        }else{
                            $_GET[$m] = $v;
                            $_REQUEST[$m] = $v;
                        }
                    }
                    return $map;
                }
            }
        }
        foreach ($this->rules as $rule => $map) {
            $res = preg_match($rule, $url, $matches);

            if($res){
                foreach ($matches as $m=>$v) {
                    if($m == '_c'){
                        $map = str_replace('<'.$m.'>', $v, $map);
                    }else if($m == '_a'){
                        $action = implode(array_map('ucfirst', explode('_', $v)));
                        $map = str_replace('<'.$m.'>', $action, $map);
                    }else{
                        $_GET[$m] = $v;
	                    $_REQUEST[$m] = $v;
                    }
                }
                return $map;
            }
        }
    }

    /**
     * 执行请求
     *
     * @param string $class_name 承载请求的类名
     * @return void
     **/
    public function runAction($class_name){
        if (class_exists($class_name) && method_exists($class_name, 'execute')) {
            $obj = new $class_name();

            try {
                $obj->execute();
            } catch (\Exception $e) {
                $obj->show_exception($e);
            }

            $this->succeed = true;
        }
    }

    /**
     * 处理旧版路由规则，使其兼容新框架
     */
    private function _parseOld($url, $param) {
        parse_str($param, $arr);
        if (isset($arr['ctl']) && $arr['ctl']) {
            return app_redirect(url("index"));
            /*if (isset($arr['act']) && $arr['act']) {
                return "/" . $arr['ctl'] . "/" . $arr['act'];
            }
            return "/" . $arr['ctl'];*/
        } else {
            return $url;
        }
    }
} // END class Route
