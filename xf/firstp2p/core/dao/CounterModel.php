<?php
/**
 * 计数器
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace core\dao;

/**
 * CounterModel class
 *
 * @author <pengchanglu@ucfgroup.com>
 **/
class CounterModel extends BaseModel {

    /**
     * 通过 key 获取配置
     * @param $key
     */
    public function get($key){
        $rs = $this->findBy("name =':name'","id,value",array(':name'=>$key));
        //没有这个key  就插入一个
        if($rs == null){
            $arr = array();
            $arr['name'] = $key;
            $this->setRow($arr);
            $this->save();
            return 0;
        }
        return $rs['value'];
    }

    /**
     * @param $key key 对应 value 自增
     */
    public function incr($key){
        $sql = "UPDATE `firstp2p_counter` SET `value` = `value` + 1 WHERE `name` = '%s' ";
        $sql = sprintf($sql,$key);
        return $this->execute($sql);
    }
}
