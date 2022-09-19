<?php
/**
 * MsgCategoryModel.php
 *
 * @date 2014-03-19
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


/**
 * 消息合同模板分类
 *
 * Class MsgCategoryModel
 * @package core\dao
 */
class MsgCategoryModel extends BaseModel {

    /**
     * 根据分类标记获取模板信息
     *
     * @param $type_tag 分类标记
     * @return \libs\db\Model
     */
    public function findByTypeTag($type_tag) {
        $sql = "type_tag = '%s'";
        $sql = sprintf($sql, $this->escape($type_tag));
        return $this->findByViaSlave($sql);
    }

    public function getContractVersion($categoryId) {
        $sql = "SELECT contract_version FROM %s WHERE id = %d";
        $sql = sprintf($sql,$this->tableName(), $categoryId);
        $result = $this->findBySql($sql);
        return $result['contract_version'];
    }

}
