<?php
/**
 * BankCharge class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * BankCharge class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class BankChargeModel extends BaseModel {
    public function getChargeByValue($value) {
        $condition = "`value`=':value'";
        return $this->findBy($condition, '*', array(':value' => $value));
    }

    /**
     * getChargeByName 
     * 根据名字获取银行
     * 
     * @param mixed $name 
     * @access public
     * @return void
     */
    public function getChargeByName($name) {
        $condition = "`name`=':name'";
        return $this->findBy($condition, '*', array(':name' => $name));
    }
}
