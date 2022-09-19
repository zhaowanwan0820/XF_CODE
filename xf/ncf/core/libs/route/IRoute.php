<?php
/**
 * IRoute interface file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\route;

/**
 * 描述路由类的接口
 *
 **/
interface IRoute
{
    /**
     * 解析请求
     *
     * @return void
     **/
    public function parseRequest();

    /**
     * 获取当前请求url, 不包括协议头和get参数
     *
     * @return string 当前url
     **/
    public function getCurrentUrl();

    /**
     * 映射规则,将url映射至相应的类
     *
     * @return string 类名
     **/
    public function mapRules();

    /**
     * 执行请求
     *
     * @param string $class_name 承载请求的类名
     * @return void
     **/
    public function runAction($class_name);
}
