<?php
/**
 * DealOrder class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * DealOrderAuxiliary class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealOrderModel extends BaseModel {
    /**
     * 根据用户id获取用户的
     * @param $user_id
     * @param $page_size
     * @param int $page
     */
    public function getDealOrderByUserId($user_id, $page_size, $page=1) {
        $user_id = intval($user_id);
        $page_size = intval($page_size);
        $page = intval($page);
        $start = ($page-1) * $page_size;

        $condition = "`user_id` = '%d' AND `type` = '1' AND `is_delete` = '0' and `user_delete` = '0' ORDER BY `id` DESC";
        $condition = sprintf($condition, $this->escape($user_id));
        $count = $this->count($condition);

        $condition .= sprintf(" LIMIT %d, %d", $this->escape($start), $this->escape($page_size));
        $list = $this->findAll($condition);

        return array("count"=>$count, "list"=>$list);
    }
    
    /**
     * 根据指定条件获取单条数据
     * @param unknown $condition
     * @return unknown
     */
    public function getDealOrderInfo($condition) {
        $result = $this->findBy($condition . " LIMIT 1 " );
        return $result;
    }

    /**
     *正常需要提供一个根据业务来定的名称
     *
     */
    public function getInfoExtByIdAndUserId($id,$userId){
        $sql = "id = :id and is_delete = 0 and pay_status <> 2 and order_status <> 1 and user_id = :user_id LIMIT 1 ";
        return $this->findBy($sql,'*',array(':id'=>$id,':user_id'=>$userId)); 
    }
    /**
     *正常需要提供一个根据业务来定的名称
     *
     */
    public function getInfoByIdAndUserId($id,$userId){
        $sql = "id = :id and is_delete = 0  and user_id = :user_id LIMIT 1 ";
        return $this->findBy($sql,'*',array(':id'=>$id,':user_id'=>$userId)); 
    }
}
