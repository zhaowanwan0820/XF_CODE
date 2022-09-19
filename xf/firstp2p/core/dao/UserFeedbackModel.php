<?php
/**
 * UserFeedback class file.
 *
 * @author 温彦磊 <wenyanlei@ucfgroup.com>
 **/

namespace core\dao;

/**
 * UserFeedback class
 *
 * @author 温彦磊 <wenyanlei@ucfgroup.com>
 **/
class UserFeedbackModel extends BaseModel
{
    public function getList($page, $page_size){
        
        $condition = "`is_delete`='0'";
        $order = " ORDER BY `id` DESC";
        //$limit = " LIMIT " . ($page - 1) * $page_size . ", $page_size";
        $limit = " LIMIT " . ($page - 1) * $page_size . ", :page_size";
        $param = array(":page_size" => $page_size); 
        $count = $this->count($condition, $param);
        $data = $this->findAll($condition . $order . $limit, true, '*', $param);
        
        return array('count' => $count, 'list' => $data);
    }

    /**
     * @call centenr调用,获取指定偏移量的数据
     * @param  int $id
     * @param  int $limit
     * @return array
     * @author:liuzhenpeng
     */
    public function getOffsetFeedsData($id, $limit){
        $sql = "SELECT a.id,a.content,b.mobile,a.create_time FROM " . DB_PREFIX . "user_feedback AS a LEFT JOIN " . DB_PREFIX . "user AS b ON a.user_id=b.id WHERE a.user_id !=0 AND a.id>=" . $id . " limit " . $limit;
        return $this->db->get_slave()->getAll($sql);
    }


    public function delete($ids, $is_shift){
        
        if($is_shift == 1){
            $sql = "DELETE FROM ".$this->tableName()." WHERE id IN ({$this->escape(preg_replace("/'|\"/", "",   $ids))})";
        }else{
            $sql = "UPDATE ".$this->tableName()." SET `is_delete` = 1 WHERE id IN ({$this->escape(preg_replace("/'|\"/", "",   $ids))})";
        }
        
        return $this->db->query($sql);
    }
}
