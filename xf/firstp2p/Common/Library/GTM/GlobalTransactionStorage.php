<?php

namespace NCFGroup\Common\Library\GTM;

use NCFGroup\Common\Library\RedisCreator;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\GTM\Exception\GlobalTransactionStorageException;
use NCFGroup\Common\Library\CommonLogger;

/**
 * 分布式事务存储
 */
class GlobalTransactionStorage
{

    /**
     * 事务进行中状态
     */
    const GLOBAL_STATUS_INHAND = 0;

    /**
     * 事务成功状态
     */
    const GLOBAL_STATUS_SUCCESS = 1;

    /**
     * 事务失败状态
     */
    const GLOBAL_STATUS_FAILED = 2;

    /**
     * 事务Id
     */
    private $tid = 0;

    /**
     * 事务别名
     */
    private $name = '';

    /**
     * redis实例
     */
    private $redis = null;

    /**
     * db实例
     */
    private $db = null;

    public function __construct($tid = 0)
    {
        $this->tid = $tid > 0 ? $tid : Idworker::instance()->getId();

        $config = getDi()->getConfig()->redis_gtm;
        $this->redis = RedisCreator::getRedis($config->sentinels);
        if (isset($config->password)) {
            $this->redis->auth($config->password);
        }

        $config = getDi()->getConfig()->db_gtm->toArray();
        $this->db = new DbAdapter($config);
    }

    /**
     * 获取事务Id
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * 设置别名
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * 获取事务别名
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 保存所有的Event
     */
    public function saveTransactionData(array $event, $timeout, $times = 0)
    {
        $data = array(
            'app' => $this->getAppName(),
            'name' => $this->name,
            'timeout' => $timeout,
            'tid' => $this->tid,
            'events' => serialize($event),
            'status' => self::GLOBAL_STATUS_INHAND,
            'starttime' => $this->getMicrotime(),
            'times' => $times,
            'nexttime' => time() + $timeout,
        );

        return $this->db->insert('gtm_transactions', $data, array_keys($data));
    }

    /**
     * 获取APP名称
     */
    private function getAppName()
    {
        return defined('APP_NAME') ? APP_NAME : '';
    }

    /**
     * 保存错误信息
     */
    public function saveError($event, $content)
    {
        $data = array(
            'tid' => $this->tid,
            'name' => $this->name,
            'event' => $event,
            'content' => $content,
            'server' => gethostname(),
            'createtime' => $this->getMicrotime(),
        );

        $this->db->insert('gtm_error', $data, array_keys($data));

        CommonLogger::info("gtm event error. event:{$event}, content:{$content}");
    }

    /**
     * 获取事务数据
     */
    public function getTransactionData()
    {
        $result = $this->db->fetchOne("SELECT * FROM gtm_transactions WHERE tid='{$this->tid}'", \Phalcon\Db::FETCH_ASSOC);
        $result['events'] = unserialize($result['events']);

        $this->name = $result['name'];

        $data = array(
            'times' => $result['times'] + 1,
            'nexttime' => $this->calcNextTime($result['timeout'], $result['times']),
        );
        $this->db->update('gtm_transactions', array_keys($data), array_values($data), "tid='{$this->tid}'");

        return $result;
    }

    /**
     * 获取异步可执行的事务
     */
    public function getAsyncTransactions($count)
    {
        $now = time();
        $status = self::GLOBAL_STATUS_INHAND;
        $app = $this->getAppName();

        return $this->db->fetchAll("SELECT tid, name FROM gtm_transactions WHERE status='{$status}' AND app='{$app}' AND nexttime<='{$now}' LIMIT {$count}", \Phalcon\Db::FETCH_ASSOC);
    }

    /**
     * 设置事务终态为成功
     */
    public function setTransactionFinalSuccess()
    {
        $data = array(
            'status' => self::GLOBAL_STATUS_SUCCESS,
            'endtime' => $this->getMicrotime(),
        );

        return $this->db->update('gtm_transactions', array_keys($data), array_values($data), "tid='{$this->tid}'");
    }

    /**
     * 设置事务终态为失败
     */
    public function setTransactionFinalFailed()
    {
        $data = array(
            'status' => self::GLOBAL_STATUS_FAILED,
            'endtime' => $this->getMicrotime(),
        );

        return $this->db->update('gtm_transactions', array_keys($data), array_values($data), "tid='{$this->tid}'");
    }

    /**
     * 设置事务中间状态
     */
    public function setTransactionIntermediateStatus($status)
    {
        return $this->redis->HSET($this->getKey(), 'TM', $status);
    }

    /**
     * 获取事务中间状态
     */
    public function getTransactionIntermediateStatus()
    {
        return $this->redis->HGET($this->getKey(), 'TM');
    }

    /**
     * 设置任务状态
     * @param string $step 阶段(PREPARE/COMMIT/ROLLBACK)
     * @param int $offset Event在事务中的位置
     * @param string $status 执行终态(SUCCESS/FAILED/EXCEPTION)
     * @param int $starttime Event执行开始时间
     */
    public function setEventStatus($step, $offset, $status, $starttime)
    {
        $cost = round(microtime(true) - $starttime, 6);

        try {
            $result = $this->redis->HSET($this->getKey(), $step.'_'.$offset, $status.','.$cost);
        } catch (\Exception $e) {
            throw new GlobalTransactionStorageException('setEventStatus failed. msg:'.$e->getMessage());
        }

        if ($result === false) {
            throw new GlobalTransactionStorageException('setEventStatus failed. result:false');
        }
    }

    /**
     * 获取任务状态
     * @param string $step 阶段(PREPARE/COMMIT/ROLLBACK)
     * @param int $offset Event在事务中的位置
     */
    public function getEventStatus($step, $offset)
    {
        $result = $this->redis->HGET($this->getKey(), $step.'_'.$offset);
        $value = explode(',', $result);

        return isset($value[0]) ? $value[0] : '';
    }

    /**
     * 获取所有状态 (不包括事务终态)
     */
    public function getAllStatus()
    {
        $result = $this->redis->HGETALL($this->getKey());

        foreach ($result as $key => $value) {
            $result[$key] = explode(',', $value);
        }

        return $result;
    }

    /**
     * 获取Redis key
     */
    private function getKey()
    {
        return 'H_GTM_STATUS_'.$this->tid;
    }

    /**
     * 获取当前微秒值
     */
    private function getMicrotime()
    {
        return intval(microtime(true) * 1000000);
    }

    /**
     * 计算下次执行时间
     */
    private function calcNextTime($timeout, $times)
    {
        return time() + max($timeout, 60) * pow(2, $times);
    }

}
