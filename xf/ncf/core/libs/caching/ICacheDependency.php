<?php
/**
 * ICacheDependency interface file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 **/

namespace libs\caching;

/**
 * ICacheDependency is the interface that must be implemented by cache dependency classes.
 *
 * This interface must be implemented by classes meant to be used as
 * cache dependencies.
 *
 * Objects implementing this interface must be able to be serialized and unserialized.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
interface ICacheDependency
{
    /**
     * Evaluates the dependency by generating and saving the data related with dependency.
     * This method is invoked by cache before writing data into it.
     */
    public function evaluateDependency();
    /**
     * 检查依赖是否已经修改
     *
     * @return boolean whether the dependency has changed.
     */
    public function getHasChanged();
}
