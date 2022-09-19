<?php
/**
 * WeixinInfoModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

/**
 * 微信信息
 *
 * @author luzhengshuai@ucfgroup.com
 */
class WeixinInfoModel extends BaseModel
{
    const STATUS_BIND = 1; // 绑定
    const STATUS_UNBIND = 0; // 解绑

    /**
     * saveWeixinInfo
     *
     * @param array $data
     * @access public
     * @return void
     */
    public function saveWeixinInfo($data) {

        if(empty($data)){
            return false;
        }

        foreach ($data as $field => $value) {
            if ($value !== NULL && $value !== '') {
                $this->$field = $this->escape($value);
            }
        }

        $this->create_time = time();
        $this->status = self::STATUS_BIND;

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * getWeixinInfoByOpenid
     *
     * @param string $openid
     * @access public
     * @return void
     */
    public function getWeixinInfoByOpenid($openid, $slave = false) {
        return $this->getByConditions(array('openid' => $openid), '*', $slave);
    }

    /**
     * getWeixinInfoByUserId
     *
     * @param integer $userId
     * @access public
     * @return void
     */
    public function getWeixinInfoByUserId($userId, $slave = false) {
         return $this->getByConditions(array('user_id' => $userId), '*', $slave);
    }

    /**
     * updateByOpenid
     *
     * @param string $openid
     * @param array $data
     * @access public
     * @return void
     */
    public function updateByOpenid($openid, $data) {
        if (!$openid || !$data) {
            return false;
        }
        $data['update_time'] = time();

        $result = $this->db->autoExecute($this->tableName(), $data, "UPDATE", 'openid = "'.$this->escape($openid). '"');
        $affectedRows = $this->db->affected_rows();
        if (!$result || $affectedRows <= 0) {
            return false;
        }
        return true;
    }

    /**
     * getByConditions
     *
     * @param array $conditions
     * @param string $fields
     * @access public
     * @return void
     */
    public function getByConditions($conditions, $fields = '*', $slave = false) {
        $condition = '1 = 1';
        foreach ($conditions as $field => $value) {
            $condition .= " and $field = '$value'";
        }
        return $this->findBy($condition, $fields, array(), $slave);
    }
}
