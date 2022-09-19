<?php
/**
 * PlatformManagementModel.php
 * @author zhaohui3@ucfgroup.com
 **/

namespace core\dao;

use core\dao\DealModel;

class PlatformManagementModel extends BaseModel {

    /**
     * 根据相应条件返回符合条件的数据
     * @param $condition,$is_array=true,$fields="*", $params = array()
     * @return float
     */
    public function getAllPlatformInfo($is_array=true,$fields="*", $params = array()) {
        return $this->findAllViaSlave("`is_delete` =0 AND `is_effect` = 1", $is_array, $fields,$params);
    }
    /**
     * 根据advisory_id返回符合条件的数据
     * @param $advisory_id,$is_array=true,$fields="*", $params = array()
     * @return float
     */
    public function getPlatformInfoByAdvisoryId($advisory_id,$is_array=true,$fields="*", $params = array()) {
        $condition = sprintf("`is_delete` =0 AND `is_effect` = 1 AND `advisory_id` = '%d'",intval($advisory_id));
        return $this->findAllViaSlave($condition, $is_array, $fields,$params);
    }
    /**
     * 根据指定条件更新对应的数据
     * @param $use_money,$advisory_id,$is_warning
     * @return float
     */
    public function updatePlatformInfoByCondition($use_money,$advisory_id,$is_warning) {
        $sql = sprintf("UPDATE %s set `use_money` = %s,`is_warning` = %d
                 WHERE `advisory_id` = %d",$this->tableName(),floatval($use_money),intval($is_warning),intval($advisory_id));
        return $this->execute($sql);
    }
}
