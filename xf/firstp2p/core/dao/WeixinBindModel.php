<?php
/**
 * WeixinBindModel class file.
 *
 * @author xiaoan
 **/

namespace core\dao;

/**
 * 微信绑定信息操作类
 *
 **/
class WeixinBindModel extends BaseModel {

    private $_fields = array('openid', 'weixin_id','user_id', 'create_time', 'update_time');

    private $_required = array('user_id','openid');

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

        $this->create_time = time();
        $this->update_time = time();
        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * @param string $openid
     * @param string $fields
     * @return \libs\db\Model
     */
    public function getByOpenid($openid, $fields = '*') {

        $where = "openid=':openid'AND status = 1";
        $param = array(':openid' => $openid);
        return $this->findBy($where, $fields,$param, true);
    }

    /**
     *获取数据通过userid
     * @param string $openid
     * @param string $fields
     * @return \libs\db\Model
     */
    public function getByUserid($user_id, $wxId, $fields = '*') {

        $where = "user_id=':user_id 'AND weixin_id = ':wxId' AND status = 1";
        $param = array(':user_id' => $user_id, ':wxId' => $wxId);
        return $this->findBy($where, $fields, $param, true);
    }

    /**
     *获取uids的openid
     * @param string $user_id
     * @param array uids
     * @return array
     */
     public function getOpenidByUids($uids, $fields = '*') {

        $condition = 'status = 1 AND user_id IN ('.implode(',',$uids).')';
        return $this->findAllViaSlave($condition,true,$fields);
    }
   /**
     *获取删除过的数据通过userid和openid
     * @param string $user_id
     * @param string $openid
     * @param string $fields
     * @return \libs\db\Model
     */
    public function getDelData($user_id,$openid, $fields = '*'){
        $where = "user_id=':user_id 'AND openid=':openid' AND status = 0";
        $param = array(
            ':user_id' => $user_id,
            ':openid'  => $openid
                );
        return $this->findBy($where, $fields,$param, true);
    }

    /**
     *更新状态通过userid和openid
     * @param string $user_id
     * @param string $openid
     * @return bool
     */
    public function updateStatus($user_id,$openid){
        $data = array(
            'status' => 1,
            'update_time'=> time()
            );
        $condition = sprintf("`openid` = '%s' AND `user_id` = '%s' AND status = 0",$this->escape($openid),$this->escape($user_id));
        return $this->updateAll($data,$condition);
    }

    /**
     *删除数据通过userid
     * @param string $user_id
     * @return bool
     */
    public function delByUserid($user_id){
        $data = array(
            'status' => 0,
            'update_time'=> time()
            );
        $condition = sprintf("`user_id` = '%s'AND status = 1",$this->escape($user_id));
        return $this->updateAll($data,$condition);
    }

    /**
     *删除数据通过openid
     * @param string $openid
     * @return bool
     */
    public function delByOpenid($openid){
        $data = array(
            'status' => 0,
            'update_time'=> time()
            );
        $condition = sprintf("`openid` = '%s'AND status = 1",$this->escape($openid));
        return $this->updateAll($data,$condition);
    }

    public function getList($wxId, $openId = '', $userId = '', $page = 1, $size = 20)
    {
        $where = 'status = 1';
        $data = [];
        if ($wxId) {
            $where .= " AND weixin_id = ':weixin_id' ";
            $data[':weixin_id'] = $wxId;
        }

        if ($openId) {
            $where .= " AND openid = ':openid' ";
            $data[':openid'] = $openId;
        }

        if ($userId) {
            $where .= " AND user_id = :user_id ";
            $data[':user_id'] = $userId;
        }
        $count = $this->count($where, $data);
        $start = ($page - 1) * $size;
        $where .= " ORDER BY id DESC LIMIT {$start}, {$size}";
        return [
            'list' => $this->findAllViaSlave($where, true, '*', $data),
            'cnt' => $count,
        ];
    }

} // END class WeixinBindModel extends BaseModel
