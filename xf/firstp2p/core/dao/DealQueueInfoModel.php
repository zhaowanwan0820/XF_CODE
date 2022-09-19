<?php
/**
 * DealQueueInfoModel.php
 * @author wangyiming@ucfgroup.com
 **/

namespace core\dao;

use core\dao\DealModel;
use core\dao\DealQueueModel;
use libs\utils\Logger;

class DealQueueInfoModel extends BaseModel {

    public function deleteDealsByQueueId($queue_id)
    {
        $sql = sprintf("DELETE FROM %s WHERE `queue_id`='%d'", $this->tableName(), $this->escape($queue_id));
        return $this->execute($sql);
    }

    public function deleteDealsByQueueIds($queue_id_arr)
    {
        $queue_ids = implode(',', $queue_id_arr);
        $sql = sprintf("DELETE FROM %s WHERE `queue_id` in (%s)", $this->tableName(), $this->escape($queue_ids));
        return $this->execute($sql);
    }

    /**
     * 根据标的id获取队列信息
     * @param int $deal_id
     * @return object
     */
    public function getDealQueueByDealId($deal_id)
    {
        $deal_queue_info = $this->findBy("`deal_id`=':deal_id'", "*", array(":deal_id" => $deal_id));
        return DealQueueModel::instance()->find(intval($deal_queue_info['queue_id']));
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
            $condition = sprintf("`queue_id` = '%d' ORDER BY `sort_num`, `id`", $queue_id);
        }
        $result = $this->findAllViaSlave($condition ,true,'deal_id');

        if(!empty($result)){
            foreach ($result as $idal){
                $dealIds[]= $idal['deal_id'];
            }
        }
         return $dealIds;
    }

    /**
     * 批量判断标的是否存在于队列
     * @param array $dealIds array(1,2,3)
     * @return array array(1,2)
     */
    public function getQueueExistDealIds($dealIds) {
        $dealIds = array_map('intval', (array) $dealIds);
        if (empty($dealIds)) {
            return array();
        }

        $condition = "deal_id IN (" . implode(',', $dealIds) . ")";
        $result = $this->findAllViaSlave($condition, true, 'deal_id');
        if (empty($result)) {
            return array();
        }

        $return = array();
        foreach ($result as $item) {
            $return[$item['deal_id']] = $item['deal_id'];
        }

        return $return;
    }

    public function getQueueByDealIds($dealIds) {
        $dealIds = array_map('intval', (array) $dealIds);
        if (empty($dealIds)) {
            return array();
        }

        $dealQueueInfo = $this->findAllViaSlave("deal_id IN (" . implode(',', $dealIds) . ")", true, "queue_id, deal_id");
        return empty($dealQueueInfo) ? array() : $dealQueueInfo ;
    }

    public function getQueueByDealId($deal_id) {
        $cond = sprintf('`deal_id` = %d', $deal_id);
        return $this->findBy($cond);
    }

    public function getFirstDealIdByQueueId($queue_id)
    {
        $cond = sprintf('`queue_id` = %d ORDER BY `sort_num`, `id`', $queue_id);
        $res = $this->findBy($cond, 'deal_id');
        return isset($res['deal_id']) ? $res['deal_id'] : 0;
    }

    /**
     * 获取指定queueIdArr的count值
     * @param array 索引数组形式存queue_id
     * @param array $add_deal_id_arr 要增加的deal_id
     * @return array 数组 array('0'=>array('count'=>1,'queue_id'=>2), '1'=>array('count'=>5, 'queue_id'=>3));
     */
    public function getCountsByQueueIds($queueIdArr, $isSlave = false)
    {
        $sql = sprintf( "SELECT `queue_id`, count(1) as `count` FROM %s WHERE `queue_id` IN (%s) GROUP BY `queue_id`", $this->tableName(), implode(",", $queueIdArr));
        $result = $this->findAllBySql($sql, true, array(), $isSlave);
        //对于其中count值为0的队列，这种count为0的信息没有保存到result中。
        //需要将这个count为0的信息重新加入到result中
        if(count($result)<count($queueIdArr)){
            foreach($result as $row){
                //释放已经有结果的queueId
                //找到reuslt对应queue_id，对应的$queueIdArr的key值，然后相应的元素unset掉
                $resultKey = array_search($row['queue_id'], $queueIdArr);
                unset($queueIdArr[$resultKey]);
            }
            //如果该id不在result中，就讲这个count为0的信息重新保存到result中
            foreach($queueIdArr as $id){
                //从数据库中得到的数据类型是字符串
                //$id和0转为字符串是为了保持数据格式的一致
                $result[] = array('queue_id' => strval($id), 'count'=> '0');
            }
        }
        return $result;
    }

    /**
     * 更新队列信息
     * @param array $del_deal_id_arr 要删除的deal_id
     * @param array $add_deal_id_arr 要增加的deal_id
     * @return bool
     */
    public function updateDealQueue($queue_id, $del_deal_id_arr = array(), $add_deal_id_arr = array()) {
        try {
            $this->db->startTrans();
            if (false === $this->insertDealsIntoQueue($queue_id, $add_deal_id_arr)) {
                throw new \Exception("deal queue info error : insert deals");
            }
            if (false === $this->deleteByDealIds($queue_id, $del_deal_id_arr)) {
                throw new \Exception("deal queue info error : delete deals");
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $e->getMessage(), "line:" . __LINE__)));
            return false;
        }
    }

    // 向队列批量插入标的
    public function insertDealsIntoQueue($queue_id, $add_deal_id_arr, $jump_deal_id = 0) {
        try {
            if (empty($add_deal_id_arr))
                return true;

            $this->db->startTrans();
            $new_sort_num = $this->createNewSortNum($queue_id, count($add_deal_id_arr), $jump_deal_id) - 1;
            foreach ($add_deal_id_arr as $deal_id) {
                if (false === $this->insertDealIntoQueue($queue_id, $deal_id, ++$new_sort_num)) {
                    throw new \Exception("deal queue info error : insert deal");
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $e->getMessage(), "line:" . __LINE__)));
            return false;
        }

    }

    // 为要插入的 deal 生成新的 sort_num， 如果插队，则会先更新队列顺序
    private function createNewSortNum($queue_id, $inserted_deals_count, $jump_deal_id)
    {
        if (!empty($jump_deal_id)) {
            $new_sort_num = $this->getSortNumByDealId($queue_id, $jump_deal_id);
            // 后移插入点之后的标的
            if (false === $this->moveBackwardDealsBySortNum($queue_id, $new_sort_num, $inserted_deals_count)) {
                throw new \Exception("deal queue info error : move backward deals");
            }
        } else {
            $new_sort_num = $this->getMaxSortNum($queue_id) + 1;
        }
        return $new_sort_num;
    }

    private function insertDealIntoQueue($queue_id, $deal_id, $new_sort_num)
    {
        $obj = new self();
        $obj->queue_id = $queue_id;
        $obj->deal_id = $deal_id;
        $obj->sort_num = $new_sort_num;
        return $obj->save();
    }

    public function deleteByDealIds($queue_id, $deal_id_arr)
    {
        if (empty($deal_id_arr)) {
            return true;
        }

        $deal_ids = implode(',', $deal_id_arr);
        $sql = sprintf("DELETE FROM %s WHERE `queue_id` = %d AND `deal_id` in (%s)", $this->tableName(), $queue_id, $this->escape($deal_ids));
        return $this->execute($sql);
    }

    // 后移标的列表片段
    private function moveBackwardDealsBySortNum($queue_id, $begin_sort_num, $moved_backward_step = 1)
    {
        $sql = sprintf('UPDATE %s SET `sort_num` = `sort_num` + %d WHERE `queue_id` = %d AND `sort_num` >= %d', $this->tableName(), $moved_backward_step, $queue_id, $begin_sort_num);
        return $this->updateRows($sql);
    }

    public function getMaxSortNum($queue_id) {
        $params = array(':queue_id' => intval($queue_id));
        $res = $this->findBy('queue_id = :queue_id', 'MAX(`sort_num`) as max_sort_num', $params);
        return isset($res['max_sort_num']) ? $res['max_sort_num'] : -1;
    }


    public function getSortNumByDealId($queue_id, $deal_id)
    {
        $cond = sprintf('`queue_id` = %d AND `deal_id` = %d', $queue_id, $deal_id);
        $res = $this->findBy($cond, 'sort_num');
        return isset($res['sort_num']) ? $res['sort_num'] : 0;
    }

    // $direction 1-向后 2-向前
    public function moveDeal($queue_id, $deal_id, $direction)
    {
        try {
            $exchange_deal = (1 == $direction) ? $this->getNextDealByDealId($queue_id, $deal_id) : $this->getPreviousDealByDealId($queue_id, $deal_id);
            // 若没有找到，则说明此标位于队首或队尾
            if (empty($exchange_deal)) {
                return true;
            }
            $deal = $this->getQueueByDealId($deal_id);
            $temp_num = $exchange_deal->sort_num;
            $exchange_deal->sort_num = $deal->sort_num;
            $deal->sort_num = $temp_num;

            $this->db->startTrans();
            $res_deal = $deal->save();
            $res_exchange = $exchange_deal->save();
            if (false === $res_deal || false === $res_exchange) {
                throw new \Exception("move deal queue info error");
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $e->getMessage(), "line:" . __LINE__)));
            return false;
        }
    }

    // 获取队列中此标的后第二个 sort_num 值
    private function getNextDealByDealId($queue_id, $deal_id)
    {
        $now_sort_num = $this->getSortNumByDealId($queue_id, $deal_id);

        // 获取此队列中前两个比此 sort_num
        $cond = sprintf('`queue_id` = %d AND `sort_num` >= %d ORDER BY `sort_num` LIMIT 2', $queue_id, $now_sort_num);
        $next_deal = $this->findAll($cond);

        return isset($next_deal[1]) ? $next_deal[1] : array();
    }

    // 获取队列中此标前边第一个标的 sort_num
    private function getPreviousDealByDealId($queue_id, $deal_id)
    {
        $now_sort_num = $this->getSortNumByDealId($queue_id, $deal_id);

        // 获取此队列中前两个比此 sort_num
        $cond = sprintf('`queue_id` = %d AND `sort_num` <= %d ORDER BY `sort_num` DESC LIMIT 2', $queue_id, $now_sort_num);
        $previous_deal = $this->findAll($cond);
        return isset($previous_deal[1]) ? $previous_deal[1] : array();
    }

}
