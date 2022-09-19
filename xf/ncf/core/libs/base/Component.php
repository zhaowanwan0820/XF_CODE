<?php 
/**
 * Component class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\base;

/**
 * 这是组件实现的基类, 所有组件都应该继承此类
 *
 **/
class Component implements IComponent
{

    /**
     * 创建组件类实例并应用配置设置类实例属性
     *
     * @param array $config 组件配置，每个数组的key对应一个类属性
     *
     * @return mixed 已应用配置但尚未初始化的组件实例
     **/
    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * 应用配置设置类实例属性
     *
     * @param array $config 组件配置，每个数组的key对应一个类属性
     *
     * @return mixed|null 已应用配置但尚未初始化的组件实例,如果config为空返回null
     **/
    public function setConfig($config)
    {
        if(!empty($config) && is_array($config)) {
            foreach ($config as $key => $value) {
                if($key != 'class'){
                    $this->$key = $value;
                }
            }
            return $this;
        }
        return null;
    }

    /**
     * 初始化组件实例
     *
     * @return void
     **/
    public function init()
    {
    }
}
