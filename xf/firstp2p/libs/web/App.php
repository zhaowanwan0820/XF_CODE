<?php
/**
 * App class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\web;

use libs\base\ComponentFactory;

/**
 * 应用程序类
 **/
class App
{
    /**
     * 应用程序配置
     *
     * @var
     **/
    public static $config;

    /**
     * 单例实例
     *
     * @var string
     **/
    protected static $instance;

    /**
     * 组件实例数组
     *
     * @var string
     **/
    protected static $comps = array();

    /**
     * 启动站点
     *
     * @return void
     **/
    public function run(){ 
        $this->route->parseRequest();
    }
    
    /**
     * 初始化配置
     *
     * @param array $config 应用程序配置
     *
     * @return void
     **/
    public static function init($config = array())
    {
        if(self::$instance === null && !empty($config)){
            self::$instance = new App();
            self::$config = $config;
        }
        return self::$instance;
    }

    /**
     * 以属性读取的方式加载组件
     *
     * @param string $name 组件名称
     *
     * @return void
     **/
    public function __get($name)
    {
        return $this->getComponent($name);
    }

    /**
     * 生成组件实例
     *
     * @param string $name 组件名称
     *
     * @return mixed
     **/
    public function getComponent($name){
        if(empty(self::$comps[$name])) {
            $comp_config = self::$config['components'][$name];
            $classname = $comp_config['class'];
            if($classname) {
                $comp_instance = ComponentFactory::create($classname, $comp_config);
            }
            self::$comps[$name] = $comp_instance;
        } else {
            $comp_instance = self::$comps[$name];
        }
        return $comp_instance;
    }
}
