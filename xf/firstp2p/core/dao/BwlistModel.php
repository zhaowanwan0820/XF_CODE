<?php
/**
 * BwlistModel.php
 *
 * 黑白名单信息
 * @date 2018-05-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

use core\dao\BwlistTypeModel;

class BwlistModel extends BaseModel {

    /**
     * 查询value是否存在黑白名单里
     *
     * @param $type_key 类型key
     * @param $value value
     * @return boolean
     */
    public function inList($type_key, $value, $value2='', $value3='') {
        $sql = "`type_key` = '%s' AND is_effect=1";
        $sql = sprintf($sql, $this->escape($type_key));
        $type = BwlistTypeModel::instance()->findByViaSlave($sql);
        if (empty($type)) {
            return false;
        }
        $sql = sprintf("`type_id` = '%d' and `value` = '%s' ", $this->escape($type['id']), $this->escape($value));
        $sql .= empty($value2) ? "" : sprintf( " and `value2` = '%s' ", $this->escape($value2));
        $sql .= empty($value3) ? "" : sprintf( " and `value3` = '%s' ", $this->escape($value3));
        return $this->countViaSlave($sql);
    }

    /**
     * 获取列表
     * @param $type_key
     */
    public function getValueList($type_key){
        $sql = "`type_key` = '%s' AND is_effect=1";
        $sql = sprintf($sql, $this->escape($type_key));
        $type = BwlistTypeModel::instance()->findByViaSlave($sql);
        if (empty($type)) {
            return false;
        }

        $sql = sprintf("`type_id` = '%d' ", $this->escape($type['id']));
        return $this->findAllViaSlave($sql,true);

    }

    /**
     * 添加记录
     */
    public function addRecord($data) {
        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }

        return $this->id;
    }
}
