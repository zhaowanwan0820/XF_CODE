<?php
/**
 * OpLogModel class file.
 *
 * @author yangqing@ucfgroup.com
 */

namespace core\dao;

use libs\utils\Logger;
use core\dao\DealLoadModel;

class OpLogModel extends BaseModel
{
    const OPNAME_DEAL_CONTRACT = 'DEAL_SEND_CONTRACT'; //合同生成

    /**
     * get_count_by_opname
     * 根据任务名获取任务总数
     * @param mixed $opname 任务名称
     * @access public
     * @return int
     */
    public function get_count_by_opname($opname, $status = false){
        $condition = "op_name=':op_name'";
        $params = array(':op_name'=>$opname);
        if($status !== false){
            $condition .= " AND op_status=:status";
            $params[':status'] = $status;
        }
        return $this->count($condition,$params);
    }

    /**
     * get_opname_by_content
     * 根据业务ID获取opname全名
     * @param mixed $content_id 业务ID，如deal_id
     * @param mixed $pre_opname 业务前缀
     * @access public
     * @return string
     */
    public function get_opname_by_content($content_id, $pre_opname){
        return $pre_opname.'_'.$content_id;
    }

    /**
     * get_content_by_opname
     * 根据业务全名获取业务ID
     * @param mixed $opname 业务全名
     * @param mixed $pre_opname 业务前缀
     * @access public
     * @return string
     */
    public function get_content_by_opname($opname, $pre_opname){
        return ltrim($opname,$pre_opname.'_');
    }

    /**
     * get_row_by_opname_content
     * 根据业务名和业务内容获取日志信息
     * @param mixed $opname 业务名称
     * @param mixed $content_id 业务内容
     * @access public
     * @return void
     */
    public function get_row_by_opname_content($op_name, $content_id){
        return $this->findBy('op_name=":op_name" AND op_content=":op_content"','*',array(':op_name'=>$op_name,':op_content'=>$content_id));
    }
    /**
     * update_status
     * 更新任务状态
     * @param mixed $id 任务ID
     * @param mixed $status 要更新的状态值
     * @access public
     * @return boolean
     */
    public function update_status($id, $status, $update_time = false){
        $row = $this->find($id);

        if($row){
            if($row['op_status'] === $status){
                return false;
            }
            if($update_time){
                return $row->updateBy(array('op_status'=>$status,'update_time'=>$update_time),"id = ".$row['id']." AND op_status = '".$row['op_status']."'");
            }else{
                return $row->updateBy(array('op_status'=>$status),"id = ".$row['id']." AND op_status = '".$row['op_status']."'");
            }

        }else{
            return false;
        }
    }

    /**
     * insert_deal_contract
     * 插入合同任务
     * @param mixed $deal_id 项目ID
     * @param mixed $load_id 投资ID
     * @access public
     * @return int
     */
    public function insert_deal_contract($deal_id, $load_id){
        $data = array(
            'op_name' => $this->get_opname_by_content($deal_id, self::OPNAME_DEAL_CONTRACT),
            'op_content' => $load_id,
            'op_status' => 0,
            'create_time' => time(),
            'update_time' => gmdate('Y-m-d H-i-s',time()+8*3600)
            );
        $this->setRow($data);
        if($this->save()){
            return $this->id;
        }else{
            return false;
        }
    }

    /**
     * get_contract_reissue_list
     * 根据项目ID获取不在任务队列中的合同
     * @param mixed $deal_id 项目ID
     * @access public
     * @return array
     */
    public function get_contract_reissue_list($deal_id){
        $sql = "SELECT `deal_id`,`id` as `load_id` FROM `:deal_load_table` WHERE `deal_id`=:deal_id AND `id` NOT IN (SELECT `op_content` FROM `:op_log_table` WHERE `op_name`=':op_name')";
        $params = array(
            ':deal_load_table' => DealLoadModel::instance()->tableName(),
            ':deal_id' => $deal_id,
            ':op_log_table' => $this->tableName(),
            ':op_name' => $this->get_opname_by_content($deal_id,self::OPNAME_DEAL_CONTRACT),
            );
        $list = $this->findAllBySql($sql, true, $params, false);
        if($list){
            return $list;
        }else{
            return false;
        }
    }
}
