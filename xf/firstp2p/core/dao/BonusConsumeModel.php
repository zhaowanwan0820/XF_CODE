<?php
/**
 * BonusConsumeModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

class BonusConsumeModel extends BaseModel
{
    // 消费中
    const STATUS_CONSUME = 0;

    // 消费成功
    const STATUS_SUCCESS = 1;

    // 消费失败
    const STATUS_FAILED = 2;

    /**
     * 生成红包消费记录
     * @param array $data 插入字段
     * @return int $result 记录id
     */
    public function add($data) {

        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }
        return $this->id;
    }

    /**
     * 根据条件获取记录
     *
     * @param $conditions array('field' => $value)
     * @param $fields
     *
     * @return $result
     */
    public function getOneByConditions($conditions = array(), $fields = "*") {

        $condition = ' 1=1 ';
        foreach ($conditions as $field => $value) {
            $condition .= " AND $field  = '" . $this->escape($value) . "'";
        }

        return $this->findBy($condition, $fields);
    }

    /**
     * update  更新指定记录
     *
     * @param mixed $data array('id' => '', '')
     * @access public
     * @return void
     */
    public function update($data) {

        $this->setRow($data);
        $this->_isNew = false;
        $result = $this->save();
        if (!$result) {
            return false;
        }
        return $this->db->affected_rows();
    }
}
