<?php

namespace NCFGroup\Common\Library\GTM;

use NCFGroup\Common\Library\GTM\GlobalTransactionStorage;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\Exception\GlobalTransactionCommitException;
use NCFGroup\Common\Library\GTM\Exception\GlobalTransactionRollbackException;
use NCFGroup\Common\Library\GTM\Exception\GlobalTransactionStorageException;
use NCFGroup\Common\Library\GTM\Toolkit\ExceptionTester;
use NCFGroup\Common\Library\CommonLogger;

/**
 * 分布式事务管理器
 */
class GlobalTransactionManager
{

    /**
     * 事务Id
     */
    private $tid = 0;

    /**
     * 所有的event
     */
    private $event = array();

    /**
     * prepare方法名
     */
    private $prepareMethod = 'execute';

    /**
     * commit方法名
     */
    private $commitMethod = 'commit';

    /**
     * rollback方法名
     */
    private $rollbackMethod = 'rollback';

    /**
     * 执行次数
     */
    private $times = 0;

    public function __construct($tid = 0)
    {
        $this->storage = new GlobalTransactionStorage($tid);
    }

    /**
     * 获取事务Id
     */
    public function getTid()
    {
        return $this->storage->getTid();
    }

    /**
     * 设置事务别名(方便检索与统计，建议不超过20个字节)
     */
    public function setName($name)
    {
        $this->storage->setName($name);
        return $this;
    }

    private $timeout = 60;

    /**
     * 设置事务超时时间(秒)，超过此时间事务将被后台执行
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 添加Event，并进行一些初始化和检查
     * @param GlobalTransactionEvent $event
     */
    public function addEvent(GlobalTransactionEvent $event)
    {
        $event->hasPrepare = $event->methodExists($this->prepareMethod);
        $event->hasCommit = $event->methodExists($this->commitMethod);
        $event->hasRollback = $event->methodExists($this->rollbackMethod);
        $event->setTid($this->getTid());

        $this->checkEvent($event);

        $this->event[] = $event;

        return $this;
    }

    /**
     * 添加异步执行的Event
     */
    public function addAsyncEvent(GlobalTransactionEvent $event)
    {
        $event->async = true;
        return $this->addEvent($event);
    }

    /**
     * 第一个没有rollback的event
     */
    private $firstWithoutRollbackEvent = '';

    /**
     * event合法性检查
     * @param GlobalTransactionEvent $event
     */
    private function checkEvent(GlobalTransactionEvent $event)
    {
        //必须包含prepare与commit中的至少一个
        if ($event->hasPrepare === false && $event->hasCommit === false) {
            throw new \Exception("event must have {$this->prepareMethod}/{$this->commitMethod}");
        }

        //async类的Event只允许有commit
        if ($event->async && ($event->hasPrepare || $event->hasRollback)) {
            throw new \Exception("async event can only have {$this->commitMethod}");
        }

        //如果必须成功，则不能有准备和回滚
        if ($this->firstWithoutRollbackEvent !== '') {
            if ($event->hasPrepare || $event->hasRollback || !$event->hasCommit) {
                throw new \Exception('event after ['.$this->firstWithoutRollbackEvent.'] cannt has prepare or rollback. event:'.get_class($event));
            }
            return true;
        }

        //如果当前event不能回滚，则其后面的event必须能成功
        if ($event->hasRollback === false) {
            $this->firstWithoutRollbackEvent = get_class($event);
        }

        return true;
    }

    /**
     * 同步执行
     * @return bool
     */
    public function execute()
    {
        CommonLogger::info("gtm start. name:".$this->storage->getName().", tid:".$this->storage->getTid());

        $this->storage->saveTransactionData($this->event, $this->timeout, $this->times + 1);
        $result = $this->executeAll();

        CommonLogger::info("gtm end. name:".$this->storage->getName().", tid:".$this->storage->getTid().', result:'.var_export($result, true));
        return $result;
    }

    /**
     * 异步执行
     * @param int $delay 延迟执行时间(秒)
     */
    public function executeAsync($delay = 0)
    {
        return $this->storage->saveTransactionData($this->event, $delay);
    }

    /**
     * 后台执行
     * @return bool
     */
    public function executeBackgroud()
    {
        $result = $this->storage->getTransactionData();

        $this->event = $result['events'];
        $this->times = $result['times'];

        return $this->executeAll();
    }

    /**
     * 是否有未执行的异步Event
     */
    private $hasUnexecutedAsyncEvent = false;

    /**
     * 事务执行，执行所有的Event
     * @return bool
     */
    private function executeAll()
    {
        $status = '';

        //第二次及以后检查已执行状态
        if ($this->times > 0) {
            $status = $this->storage->getTransactionIntermediateStatus();
        }

        if (empty($status)) {
            $status = $this->prepareAll() === true ? 'COMMITTING' : 'ROLLBACKING';
            $this->storage->setTransactionIntermediateStatus($status);
        }

        if ($status === 'COMMITTING') {
            $this->commitAll();
            if (!$this->hasUnexecutedAsyncEvent) {
                $this->storage->setTransactionFinalSuccess();
            }
            return true;
        }

        if ($status === 'ROLLBACKING') {
            $this->rollbackAll();
            $this->storage->setTransactionFinalFailed();
            return false;
        }
    }

    /**
     * 准备所有
     * @return bool
     */
    private function prepareAll()
    {
        foreach ($this->event as $i => $event) {
            if ($event->hasPrepare === false) {
                continue;
            }

            //是否已经执行过
            if ($this->times > 0) {
                $status = $this->storage->getEventStatus('PREPARE', $i);
                if ($status === 'SUCCESS') {
                    continue;
                } elseif ($status === 'FAILED' || $status === 'EXCEPTION') {
                    return false;
                } else {
                    //无状态，继续执行
                }
            }

            try {
                $starttime = microtime(true);

                $result = $this->call($event, $this->prepareMethod);

                //false等同于没执行，不需要回滚
                if ($result === false) {
                    $this->storage->setEventStatus('PREPARE', $i, 'FAILED', $starttime);
                    $this->setError($event->getError());
                    return false;
                //true表示执行成功
                } elseif ($result === true) {
                    $this->storage->setEventStatus('PREPARE', $i, 'SUCCESS', $starttime);
                //其他情况均为失败
                } else {
                    throw new \Exception("invailed return value. return:".var_export($result, true));
                }
            } catch (GlobalTransactionStorageException $e) {
                //gtm存储出问题，进入下一步重试
                $info = "GlobalTransactionStorage on {$this->prepareMethod}() throw exception. will retry. msg:".$e->getMessage();
                $this->storage->saveError(get_class($event), $info);
                throw new \Exception("{$info}. class:".get_class($event));
            } catch (\Exception $e) {
                //如果是不能回滚的任务抛异常，进入下一次重试
                if ($event->hasRollback === false) {
                    $info = "{$this->prepareMethod}() throw exception. will retry. msg:".$e->getMessage();
                    $this->storage->saveError(get_class($event), $info);
                    throw new \Exception("{$info}. class:".get_class($event));
                } else {
                    $this->storage->setEventStatus('PREPARE', $i, 'EXCEPTION', $starttime);
                    $this->storage->saveError(get_class($event), "{$this->prepareMethod}() throw exception. transaction will rollback. msg:".$e->getMessage());
                    $this->setError($e->getMessage());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 提交所有
     */
    private function commitAll()
    {
        foreach ($this->event as $i => $event) {
            if ($event->hasCommit === false) {
                continue;
            }

            //第一次执行忽略异步执行的的Event
            if ($this->times === 0 && $event->async === true) {
                $this->hasUnexecutedAsyncEvent = true;
                continue;
            }

            //是否已执行成功
            if ($this->times > 0) {
                $status = $this->storage->getEventStatus('COMMIT', $i);
                if ($status === 'SUCCESS') {
                    continue;
                }
            }

            try {
                $starttime = microtime(true);

                //任务提交，必须成功
                $result = $this->call($event, $this->commitMethod);

                if ($result !== true) {
                    throw new \Exception("invailed return value. return:".var_export($result, true));
                }

                $this->storage->setEventStatus('COMMIT', $i, 'SUCCESS', $starttime);
            } catch (\Exception $e) {
                $this->storage->saveError(get_class($event), "{$this->commitMethod}() throw exception. will retry. msg:".$e->getMessage());
                throw new GlobalTransactionCommitException($e->getMessage());
            }
        }
    }

    /**
     * 回滚所有
     */
    private function rollbackAll()
    {
        foreach ($this->event as $i => $event) {
            //检查准备阶段状态，确定回滚范围(成功、异常)
            $status = $this->storage->getEventStatus('PREPARE', $i);
            if (!($status === 'SUCCESS' || $status === 'EXCEPTION')) {
                break;
            }

            if ($this->times > 0) {
                //检查执行状态，用于恢复
                $status = $this->storage->getEventStatus('ROLLBACK', $i);
                if ($status === 'SUCCESS') {
                    continue;
                }
            }

            try {
                $starttime = microtime(true);

                //任务回滚，必须成功
                $result = $this->call($event, $this->rollbackMethod);

                if ($result !== true) {
                    throw new \Exception("invailed return value. return:".var_export($result, true));
                }

                $this->storage->setEventStatus('ROLLBACK', $i, 'SUCCESS', $starttime);
            } catch (\Exception $e) {
                $this->storage->saveError(get_class($event), "{$this->rollbackMethod}() throw exception. will retry. msg:".$e->getMessage());
                throw new GlobalTransactionRollbackException($e->getMessage());
            }
        }
    }

    /**
     * 函数调用封装
     */
    private function call($object, $method)
    {
        CommonLogger::info("gtm event start. class:".get_class($object).", method:{$method}");

        $env = get_cfg_var('phalcon.env');
        if (in_array($env, array('dev', 'test', 'pdtest'))) {
            $result = ExceptionTester::instance($this->storage->getName())->call($object, $method);
        } else {
            $result = call_user_func(array($object, $method));
        }

        CommonLogger::info("gtm event end. class:".get_class($object).", method:{$method}, result:".var_export($result, true));

        return $result;
    }

    private $errorInfo = '';

    /**
     * 设置错误信息
     */
    private function setError($errorInfo)
    {
        $this->errorInfo = $errorInfo;

        CommonLogger::info("gtm error set. info:{$errorInfo}");
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->errorInfo;
    }

}
