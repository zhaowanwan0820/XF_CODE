<?php
/**
 * Payment class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * Payment class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class PaymentModel extends BaseModel {
    
    /**
     * 获取支付类信息
     * @param type $classname
     * @return type
     */
    public function getPaymentByClassname($classname){        
        $condition = "`class_name` = '%s'";
        $condition = sprintf($condition, $this->escape($classname));
        return $this->findBy($condition);
    }
    
}
