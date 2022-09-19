<?php
/**
 * DealQueuesModel.php
 * @author wangzhen，chenyanbing
 **/

namespace core\dao;

class DealQueuesModel extends BaseModel {
    /**
     * 通过id获取队信息
     * @param intval $id
     * @return array
     */
    public function getById($id){
        return $this->findViaSlave($id);
    }
    /**
     * 检查tag唯一标识以及产品类别是否相同
     */
    public function getCntByName($name,$id=false) {
        if ($id) {
            $condition = "`name`='{$name}' AND `id`!='{$id}'";
        } else {
            $condition = "`name`='{$name}'";
        }
        return $this->count($condition);
    }

    public function updateQueue($data,$id){
        $condition="`id`='{$id}'";
        return $this->updateBy($data,$condition);
    }
    /**
     *通过队列名称查询队列列表
     */
    public function getQueueListByName($name=''){
        if(empty($name)){
            $condition=sprintf("SELECT * FROM %s ORDER BY id DESC",$this->tableName());
            $res=$this->findAllBySql($condition);
        }else{
            $condition="`name` LIKE '%{$name}%' ORDER BY id DESC";
            $res=$this->findAll($condition);
        }
        return $res;
    }

    /**
     * 更加开始时间获取队列
     * @param string $serviceType
     * @param intval $startTime
     * @return array
     */
    public function getQueueByStartTime($serviceType,$startTime,$isEffect=1){
        $sql = "SELECT * FROM {$this->tableName()} where start_time<={$startTime} and start_time != 0 and is_effect='{$isEffect}'";
        if(!empty($serviceType)){
            $sql .= " and service_type = '{$serviceType}'";
        }
        return $this->findAllBysql($sql,true);
    }

    /**
     * 获取有效的队列
     * @param string $serviceType
     * @return array
     */
    public function getQueuesList($serviceType,$isEffect=1){
        $sql = "SELECT * FROM {$this->tableName()} where is_effect='{$isEffect}'";
        if(!empty($serviceType)){
            $sql .= " and service_type = '{$serviceType}'";
        }
        return $this->findAllBysql($sql,true);
    }

    /**
     *获取需要截标的队列
     */
    public function getSellOutQueues($serviceType,$isEffect=1,$sellOut=1){
        $sql = "SELECT * FROM {$this->tableName()} where is_effect='{$isEffect}' AND sell_out='{$sellOut}'";
        if(!empty($serviceType)){
            $sql .= " and service_type = '{$serviceType}'";
        }
        return $this->findAllBysql($sql,true);
    }

   /**
    * 删除队列
    */
    public function deleteQueues($queue_id_arr)
    {
        try {
            if (empty($queue_id_arr)) {
                return true;
            }

            $this->db->startTrans();
            $sql = sprintf("DELETE FROM %s WHERE `id` in (%s)", $this->tableName(), $this->escape(implode(',', $queue_id_arr)));
            if (false === $this->execute($sql)) {
                throw new \Exception("deal queue error : delete deal queue fail");
            }
            if (false === DealQueueInfosModel::instance()->deleteDealsByQueueIds($queue_id_arr)) {
              throw new \Exception("deal queue info error : delete deals fail");
              }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $e->getMessage(), "line:" . __LINE__)));
            return false;
        }
    }
}

