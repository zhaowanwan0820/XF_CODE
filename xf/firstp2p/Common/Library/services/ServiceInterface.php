<?php
/**
 *  服务定义需要实现的标准行为
 *  @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace NCFGroup\Common\Library\services;

interface ServiceInterface
{
    /**
     * 判断是否存在服务
     * @param string $key 服务名称
     * @return boolean
     */
    public function has($key);

    /**
     *  读取服务信息配置
     * @param string $key 服务名称
     * @return mixed 返回对应的服务配置信息
     */
    public function get($key);

    /**
     *  读取服务公共配置信息
     * @param string $key
     * @return mixed 返回对应的公共配置数据
     */
    public function getConfig($key);
}
