<?php
/**
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace core\dao;

/**
 * ConfModel class
 *
 * @author <pengchanglu@ucfgroup.com>
 **/
class ConfModel extends BaseModel {

    /**
     * 通过 key 获取配置
     * @param $key
     */
    public function get($key){
        return $this->findBy("name =':name'","id,value",array(':name'=>$key));
    }

    /**
     * @param $key key 对应 value 自增
     */
    public function incr($key){
        $sql = "UPDATE `firstp2p_conf` SET `value` = `value` + 1 WHERE `name` = '%s' ";
        $sql = sprintf($sql,$key);
        return $this->execute($sql);
    }

    /**
     * 通过 key 获取配置
     * @param $key
     */
    public function getValue($key) {
        $ret = $this->get($key);
        return isset($ret['value']) ? $ret['value'] : '';
    }

    /**
     * 通过key修改配置
     */
    public function set($key, $value){
        $sql = "UPDATE `firstp2p_conf` SET `value` = '%s' WHERE `name` = '%s' ";
        $sql = sprintf($sql,$value, $key);
        return $this->execute($sql);
    }

}
