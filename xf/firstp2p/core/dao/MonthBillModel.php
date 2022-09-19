<?php
/**
 * Deal class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace core\dao;


/**
 * Deal class
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class MonthBillModel extends BaseModel {
	/**
	 * 插入月对账单 数据
	 * @param $data array 数据数组
	 * @return float
	 */
	public function insertData($data){

	    if(empty($data)){
	        return false;
	    }
	    $this->setRow($data);


        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
	}


	/**
	 * 获取月对账单和附件 列表
	 * @param int $condition['offset']     起始位置
	 * @param int $condition['pagesize']   一页大小
	 * @param int $condition['year']       年份
	 * @param int $condition['month']      月份
	 * @param int $condition['attachment_id']     附件id
	 * @author zhanglei5@ucfgroup.com
	 */
	public function getList($condition) {
	    $param = array();
		if(is_array($condition)) {

			$sql = 'SELECT `id`,`user_id`,`attachment_id`,`idno`,`html_content`,`email` FROM '.$this->tableName();
			if(isset($condition['year']) && is_numeric($condition['year'])) {
				$sql .= " WHERE `year` = :year";
				$param[':year'] =  intval($condition['year']);
			}
			if(isset($condition['month']) && is_numeric($condition['month'])) {
				$sql .= " AND `month` = :month";
				$param[':month'] =  intval($condition['month']);
			}
			if(isset($condition['attachment_id']) && is_numeric($condition['attachment_id'])) {
				$sql .= " AND `attachment_id` = :attachment_id";
				$param[':attachment_id'] =  intval($condition['attachment_id']);
			}
			if(isset($condition['is_send']) && is_numeric($condition['is_send'])) {
				$sql .= " AND `is_send` = :is_send";
				$param[':is_send'] =  intval($condition['is_send']);
			}

			if(isset($condition['offset']) && isset($condition['page_size'])) {
				$sql .= " limit :offset,:page_size";
				$param[':offset'] =  $condition['offset'];
				$param[':page_size'] =  intval($condition['page_size']);
			}

			$result = $this->findAllBySql($sql,true,$param);
			return $result;
		}else{
			return array();
		}
	}

	/** 
	  * 设置发送状态
	  * @param array $uids  用户id
	  * @author zhanglei5@ucfgroup.com
	  */
	public function setSendByUids($uids) {
		if(is_array($uids)) {
			$uids = implode(',',$uids);
		}
		$sql = "UPDATE ".$this->tableName().' set is_send = 1 where user_id in ('.$uids.')';
		return $this->execute($sql);
	}   



}
