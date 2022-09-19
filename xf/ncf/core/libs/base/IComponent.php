<?php
/**
 * IComponent interface file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\base;

/**
 * 约定组件类应该实现的接口, 所有组件都应实现此接口
 **/
interface IComponent
{
    /**
     * 初始化组件实例
     *
     * @return void
     **/
    public function init();
} // END interface 
