<?php
namespace libs\queue\TQueue;

use libs\queue\TQueue\ThunderQueue;

/**
 * thunderQueue 动作规范接口
 **/
interface IQueueAction {


    /**
     * 存放数据
     * @param string $queueName 队列名称
     * @param string $queueData 需要存放的队列数据
     * @return bool 返回操作结果
     */
    public function push($queueName, $queueData);

    /**
     * 从队列中取数据
     * @param string $queueName 队列名称
     * @param integer $limit 单次读取队列记录数量
     * @return TQueueRecord
     */
    public function pop($queueName, $limit = 1, $dataType = ThunderQueue::TSTRING);

    /**
     * 获取指定队列的容量
     * @param string $queueName 队列名称
     * @return integer 队列长度
     */
    public function getCapacity($queueName);

    /**
     * 删除指定队列
     * @param string $queueName 队列名称
     * @return bool 删除结果
     */
    public function delete($qname);

}
