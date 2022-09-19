<?php
namespace core\dao;

/**
 * ConfModel class
 *
 **/
class ConfModel extends BaseModel {

    /**
     * 通过 key 获取配置
     * @param $key
     */
    public function get($key) {
        return $this->findBy("name =':name'", "id,value", array(':name'=>$key));
    }

    /**
     * @param $key key 对应 value 自增
     */
    public function incr($key) {
        // 获取表名
        $tableName = $this->tableName();
        $sql = "UPDATE `{$tableName}` SET `value` = `value` + 1 WHERE `name` = '%s' ";
        $sql = sprintf($sql, $key);
        return $this->execute($sql);
    }

    /**
     * 通过key修改配置
     */
    public function set($key, $value) {
        $tableName = $this->tableName();
        $sql = "UPDATE `{$tableName}` SET `value` = '%s' WHERE `name` = '%s' ";
        $sql = sprintf($sql, $value, $key);
        return $this->execute($sql);
    }
}
