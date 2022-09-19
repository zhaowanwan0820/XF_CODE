<?php
namespace NCFGroup\Task\Events;

/**
 * TaskInterceptor
 * 用于事件执行时的拦截
 *
 * @author jingxu <jingxu@ucfgroup.com>
 */
interface EventInterceptor
{
    /**
     * before
     * 事件执行前操作
     *
     * @access public
     * @return void
     */
    public function before();

    /**
     * after
     * 事件执行后的操作
     *
     * @access public
     * @return void
     */
    public function after();
}
