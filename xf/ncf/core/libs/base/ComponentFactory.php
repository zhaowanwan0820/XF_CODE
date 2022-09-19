<?php 
/**
 * ComponentFactory class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\base;

/**
 * 组件工厂，用于创建组件实例
 **/
class ComponentFactory {
    /**
     * 用指定的组件类以及相应配置创建对应的实例并初始化
     *
     * @param string $class_name 组件完整类名
     * @param array $config 初始化组件类实例的配置数组，数组的每个key对应类的一个属性
     * @return mixed 初始化过的组件实例
     **/
    public static function create($class_name, $config) {
        $comp_instance = new $class_name($config);
        if ($comp_instance != null) {
            $comp_instance->init();
        }
        return $comp_instance;
    }
}
