<?php
/**
 * DealQueueInfosModel.php
 * @author wangzhen，chenyanbing
 **/

namespace core\dao;


use libs\utils\Logger;

class DealQueueInfosModel extends BaseModel {

    public function deleteDealsByQueueIds($queue_id_arr)
    {
        $queue_ids = implode(',', $queue_id_arr);
        $sql = sprintf("DELETE FROM %s WHERE `queue_id` in (%s)", $this->tableName(), $this->escape($queue_ids));
        return $this->execute($sql);
    }

    public function deteteById($id){
        $sql = sprintf("DELETE FROM %s WHERE `id` = %d ", $this->tableName(),$id);
        return $this->execute($sql);
    }
    public function deleteByDealIds($dealIds){
        $dealIds = implode(',', $dealIds);
        $sql = sprintf("DELETE FROM %s WHERE `deal_id` in (%s)", $this->tableName(), $this->escape($dealIds));
        return $this->execute($sql);
    }

    /**
    * 根据标的queue_id获取标信息
    * @param int $queue_id
    * @return array
    */
    public function getDealIdsByQueueId($queue_id = 0) {
        $dealIds = array();
        $queue_id = intval($queue_id);
        $condition = '';
        if(!empty($queue_id)){
            $condition = sprintf("`queue_id` = '%d' ", $queue_id);
        }
        $result = $this->findAllViaSlave($condition ,true,'deal_id');
        if(!empty($result)){
            foreach ($result as $val){
                $dealIds[]= $val['deal_id'];
            }
        }
         return $dealIds;
    }

    /**
     * 根据标的queue_id获取队列
     * @param int $queue_id
     * @return array
     */
    public function getByQueueId($queue_id = 0) {
        $deals = array();
        $queue_id = intval($queue_id);
        $condition = '';
        if(!empty($queue_id)){
            $condition = sprintf("`queue_id` = '%d' ", $queue_id);
        }
        $deals = $this->findAllViaSlave($condition ,true);
        return $deals;
    }

    /**
     * 根据pre获取数据
     * @param int $queueId
     * @param int $pre
     * @return array
     */
    public function getByPre($pre,$queueId){
        return $this->findBy("queue_id='{$queueId}' and pre='{$pre}'");
    }

    /**
     * 根据next获取数据
     * @param int $queueId
     * @param int $next
     * @return array
     */
    public function getByNext($next,$queueId){
        return $this->findBy("queue_id='{$queueId}' and next='{$next}'");
    }

    /**
     * 根据标I获取数据
     * @param intval $dealId
     * @param intval $queueId
     * @return array
     */
    public function getByDealId($dealId,$queueId){
        return $this->findBy("deal_id='{$dealId}' and queue_id='{$queueId}'");
    }

    /**
     * 插入节点或者更新节点
     * @param int $dealId
     * @param int $queueId
     * @param int $pre
     * @param int $next
     * @throws \Exception
     * @return boolean
     */
    public function updateByDealId($dealId,$queueId,$serviceType,$pre = false,$next = false){
        if(empty($dealId)){
            throw new \Exception("dealId不能为空");
        }

        if(empty($queueId)){
            throw new \Exception("queueId不能为空");
        }

        $data = array();
        $data['queue_id'] = $queueId;
        $data['deal_id'] = $dealId;
        $data['service_type'] = $serviceType;
        if($pre !== false ){
            $data['pre'] = $pre;
        }
        if($next !== false ){
            $data['next'] = $next;
        }

        $node = $this->getByDealId($dealId,$queueId);
        if(empty($node)){
           $result = $this->db ->insert($this->tableName(), $data);
        }else{
           $result = $this->updateBy($data, "deal_id='{$dealId}' and queue_id='{$queueId}'");
        }

        if(empty($result)){
            throw new \Exception("更新失败");
        }
        return $result;
    }
    
    /**
     * 根据标id获取队列id
     * @param array $dealId
     * @param array $serviceType
     * @return array
     */
    public function getQueueIdByDealId($dealId,$serviceType){
        $queueId = 0;
        $result = $this->findBy("deal_id='{$dealId}' and service_type='{$serviceType}'",'queue_id');
        if(!empty($result)){
            $queueId = $result['queue_id'];
        }
        return $queueId;
    }
    
    /**
     * 更加项目类型获取标id
     * @param string $serviceType
     * @return array
     */
    public function getDealIdsByServiceType($serviceType){
        $dealIds = array();
        $condition = '';
        if(!empty($serviceType)){
            $condition = sprintf("`service_type` = '%s' ", $serviceType);
        }
        $result = $this->findAllViaSlave($condition ,true,'deal_id');
        if(!empty($result)){
            foreach ($result as $val){
                $dealIds[]= $val['deal_id'];
            }
        }
        return $dealIds;
    }
}
