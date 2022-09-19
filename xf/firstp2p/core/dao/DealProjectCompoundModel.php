<?php
/**
 * DealProjectModel.php
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;


class DealProjectCompoundModel extends BaseModel {

    /**
     * 获取利滚利项目的扩展信息
     * @param $id
     * @return float
     */
    public function getInfoByProId($id){

        $sql = "SELECT * FROM %s WHERE project_id = ':project_id'";
        $sql = sprintf($sql, DealProjectCompoundModel::instance()->tableName());

        $param = array(':project_id' => $id);
        $result = $this->findBySqlViaSlave($sql,$param);
        return $result;
    }


}
