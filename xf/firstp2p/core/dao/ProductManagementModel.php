<?php
/**
 * ProductManagementModel.php
 * @author zhaohui3@ucfgroup.com
 **/

namespace core\dao;

use core\dao\DealModel;

class ProductManagementModel extends BaseModel {

    /**
     * 根据相应条件返回符合条件的数据
     * @param $condition,$is_array=true,$fields="*", $params = array()
     * @return float
     */
    public function getAllProductInfo($is_array=true,$fields="*", $params = array()) {
        $condition = "`is_delete` =0 AND `is_effect` = 1";
        return $this->findAllViaSlave($condition, $is_array, $fields,$params);
    }
    /**
     * 根据产品名称返回符合条件的数据
     * @param $product_name,$is_array=true,$fields="*", $params = array()
     * @return float
     */
    public function getProductInfoByProductName($product_name,$is_array=true,$fields="*", $params = array()) {
        $condition = sprintf("`is_delete` =0 AND `is_effect` = 1 AND `product_name` = '%s'",addslashes($product_name));
        return $this->findAllViaSlave($condition, $is_array, $fields,$params);
    }
    /**
     * 根据指定条件更新对应的数据
     * @param $use_money,$advisory_id,$is_warning
     * @return float
     */
    public function updateProductInfoByCondition($use_money,$product_name,$is_warning) {
        $product_name = addslashes($product_name);
        $sql = sprintf("UPDATE %s set `use_money` = %s,`is_warning` = %d
                 WHERE `product_name` = %s",$this->tableName(),floatval($use_money),intval($is_warning),"'".$product_name."'");
        return $this->execute($sql);
    }
}