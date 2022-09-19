<?php
/**
 * ServiceAuditModel class file.
 * @author Wang Shi Jie <wangshijie@ucfgroup.com>
 */

namespace core\dao;

class ServiceAuditModel extends BaseModel
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
}
