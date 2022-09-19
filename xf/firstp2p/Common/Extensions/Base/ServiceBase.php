<?php
namespace NCFGroup\Common\Extensions\Base;

use NCFGroup\Common\Library\PhalconLib;

class ServiceBase
{

    protected $transactionCount = 0;

    const IPC_KEY = 1939203451892314;

    public $di;

    public $db;

    public function __construct()
    {
        $this->transactionCount = 0;
    }

    protected function info($msg, $debugLevel = 0)
    {
        PhalconLib::info($this->di->get("logger"), $msg, $debugLevel + 1);
    }

    protected function debug($msg, $debugLevel = 0)
    {
        PhalconLib::debug($this->di->get("logger"), $msg, $debugLevel + 1);
    }

    protected function error($msg, $debugLevel = 0)
    {
        PhalconLib::error($this->di->get("logger"), $msg, $debugLevel + 1);
    }

    protected function startTransaction()
    {
        $semId = sem_get(ServiceBase::IPC_KEY);
        if ($this->transactionCount == 0) {
            $this->di['db']->begin();
        }
        sem_acquire($semId);
        $this->transactionCount++;
        sem_release($semId);
    }

    protected function commitTransaction()
    {
        $semId = sem_get(ServiceBase::IPC_KEY);
        if ($this->transactionCount == 1) {
            try {
                $this->di['db']->commit();
                sem_acquire($semId);
                $this->transactionCount = 0;
                sem_release($semId);
            } catch (\Exception $e) {
                throw $e;
            }
        } else
            if ($this->transactionCount >= 1) {
                sem_acquire($semId);
                $this->transactionCount--;
                sem_release($semId);
            } else {
                throw new \Exception("Transation 'commit' does not match the 'start'!");
            }
    }

    protected function rollbackTransaction()
    {
        $semId = sem_get(ServiceBase::IPC_KEY);
        if ($this->transactionCount > 0) {
            try {
                sem_acquire($semId);
                $this->transactionCount = 0;
                sem_release($semId);
                $this->di['db']->rollback();
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    public function __get($key)
    {
        if(property_exists($this, $key)) {
            return $this->{$key};
        }

        if($this->di->has($key)) {
            return $this->di->get($key);
        }

        return null;
    }
}
