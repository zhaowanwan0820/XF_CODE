<?php
/**
 * OpStatusModel class file.
 *
 * @author yangqing@ucfgroup.com
 */

namespace core\dao\contract;

use libs\utils\Logger;
use core\dao\BaseModel;

class OpStatusModel extends BaseModel
{
    /**
     * insert_status_log
     * 插入任务流程
     * @param mixed $op_name
     * @access public
     * @return boolean
     */
    public function insert_status_log($op_name, $op_type, $content_id){
        $row = $this->findBy("op_name = ':op_name'",'id',array(':op_name'=>$op_name));
        if($row){
            return $row['id'];
        }else{
            $data = array(
                'op_name' => $op_name,
                'op_type' => $op_type,
                'content_id' => $content_id,
                'trans_status' => 0,
                'create_time' => time()
                );
            $this->setRow($data);
            if($this->save()){
                return $this->id;
            }else{
                return false;
            }
        }
    }
    /**
     * update_status
     * 更新任务状态
     * @param mixed $id 任务流程ID
     * @param mixed $status 要更新的状态值
     * @access public
     * @return boolean
     */
    public function update_status($id, $status){
        $row = $this->find($id);
        if($row){
            return $row->update(array('trans_status'=>$status,'update_time'=>gmdate('Y-m-d H-i-s',time()+8*3600)));
        }else{
            return false;
        }
    }


}
