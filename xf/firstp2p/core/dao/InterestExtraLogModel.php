<?php
/**
 * InterestExtraLogModel.class.php
 * 投资贴息记录日志模块
 * @date 2015-10-29
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */

namespace core\dao;


class InterestExtraLogModel extends BaseModel {

    public function insert($data)
    {
        $this->db->autoExecute($this->tableName(), $data, "INSERT");
        return $this->db->insert_id();
    }
}