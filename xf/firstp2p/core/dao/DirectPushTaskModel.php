<?php
/**
 * DirectPushTaskModel class file.
 * @author Wang Shi Jie <wangshijie@ucfgroup.com>
 */

namespace core\dao;

class DirectPushTaskModel extends BaseModel
{
    /**
     * 插入
     * @param array $data 插入字段
     * @return int
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
     * 更新
     * @param array $data 插入字段
     * @return int
     */
    public function modify($data) {
        if ($data['id'] <= 0) {
            return false;
        }
        $this->setRow($data);
        $this->_isNew = false;
        return $this->save();
    }

    /**
     * updateStatus
     *
     * @param mixed $id
     * @param mixed $status
     * @param array $addCondition
     * @access public
     * @return void
     */
    public function updateStatus($id, $status, $addCondition = array()) {
        $where = '';
        foreach ($addCondition as $k => $v) {
            $where .= " AND $k = '$v'";
        }
        $sql = sprintf('UPDATE firstp2p_direct_push_task set status = %s WHERE id = %s %s', intval($status), intval($id), $where);
        return $this->updateRows($sql);
    }
}
