<?php

namespace NCFGroup\Common\Library\GTM;

use NCFGroup\Common\Library\GTM\GlobalTransactionStorage;

/**
 * 分布式事务管理器 异步任务消费
 */
class GlobalTransactionWorker
{

    public function __construct()
    {
        $this->storage = new GlobalTransactionStorage();
    }

    /**
     * 获取异步可执行的事务
     * @param int $count 数量
     * @return array
     */
    public function getAsyncTransactions($count = 100)
    {
        return $this->storage->getAsyncTransactions($count);
    }

}
