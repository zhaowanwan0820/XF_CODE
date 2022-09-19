<?php
/**
 * BaseNoSQLModel.php
 *
 * @date 2015-05-19
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

use libs\mongodm\Model;

class BaseNoSQLModel extends Model implements \ArrayAccess {

    /**
     * 配置的key值
     */
    protected static $config = '';

    /**
     * collection名
     */
    protected static $collection = "";

    /**
     * 根据id获取记录
     *
     * @param $id
     * @return Model
     */
    public static function get($id) {
        return self::id($id);
    }


    /**
     * 数组访问接口，设置键值
     **/
    public function offsetSet($offset, $value) {
        $this->$offset=$value;
    }

    /**
     * 数组访问操作，检查key是否存在
     **/
    public function offsetExists($offset) {
        return isset($this->$offset);
    }

    /**
     * 数组访问操作，unset
     **/
    public function offsetUnset($offset) {
        unset($this->$offset);
    }

    /**
     * 数组访问操作，取值
     **/
    public function offsetGet($offset) {
        return isset($this->$offset) ? $this->$offset : null;


    }


}