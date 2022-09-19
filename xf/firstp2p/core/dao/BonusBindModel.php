<?php
/**
 * WeixinBindModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 **/

namespace core\dao;

/**
 * 红包活动绑定信息操作类
 *
 * @author luzhengshuai@ucfgroup.com
 **/
class BonusBindModel extends BaseModel {

    const STATUS_BIND = 1; // 绑定
    const STATUS_UNBIND = 0; // 删除
    private $_fields = array('openid', 'user_id', 'mobile', 'user_info', 'status', 'create_time', 'update_time', 'delete_time');

    private $_required = array('mobile');

    /**
     * 插入一条数据
     * @param $data array 数据数组
     * @return float
     */
    public function insertData($data){

        if(empty($data)){
            return false;
        }

        foreach ($this->_required as $field) {
            if (!$data[$field]) {
                return false;
            }
        }

        foreach ($data as $field => $value) {
            if ($value !== NULL && $value !== '') {
                $this->$field = $this->escape($value);
            }
        }

        $this->create_time = get_gmtime();
        $this->status = self::STATUS_BIND;

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    public function updateByOpenid($openid, $data) {
        if (!$openid || !$data) {
            return false;
        }
        $data['update_time'] = get_gmtime();

        $result = $this->db->autoExecute($this->tableName(), $data, "UPDATE", 'openid = "'.$this->escape($openid) .'"');
        $affectedRows = $this->db->affected_rows();
        if (!$result || $affectedRows <= 0) {
            return false;
        }
        return true;
    }

    public function updateByMobile($mobile, $data) {
        if (!$mobile || !$data) {
            return false;
        }
        $data['update_time'] = get_gmtime();
        $result = $this->db->autoExecute($this->tableName(), $data, "UPDATE", 'mobile = "'.$this->escape($mobile) . '"');
        $affectedRows = $this->db->affected_rows();
        if (!$result || $affectedRows <= 0) {
            return false;
        }
        return true;
    }

    public function getByConditions($conditions, $fields = '*') {
        $condition = 'status = ' . self::STATUS_BIND;
        foreach ($conditions as $field => $value) {
            $condition .= " AND $field = '$value'";
        }

        $condition .= " AND openid IS NOT NULL";
        return $this->findByViaSlave($condition, $fields);
    }
} // END class WeixinBindModel extends BaseModel
