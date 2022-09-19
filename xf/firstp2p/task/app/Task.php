<?php
namespace NCFGroup\Task;

use NCFGroup\Common\Phalcon\Module as CommonModule;
use NCFGroup\Common\Library\Date\XDateTime;
use Phalcon\Logger\Adapter\File as FileAdapter;
use NCFGroup\Task\Instrument\FrequencyHandler;

//if(!defined('TASK_WORK_MODE')) {
//    define('TASK_WORK_MODE', 'redis');
//}

class Task extends CommonModule
{
    public function registerAutoloaders()
    {
        $loader = new \Phalcon\Loader();
        $loader->registerNamespaces(array(
            'NCFGroup\Task' => __DIR__.'/',
        ))->register();
    }

    public function registerServices()
    {
        $di = $this->di;
        $config = $di->getConfig();

        $di->setShared('taskDb', function () use ($config) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                'host' => $config->taskDb->host,
                'username' => $config->taskDb->username,
                'password' => $config->taskDb->password,
                'dbname' => $config->taskDb->dbname,
                'port' => $config->taskDb->port,
            ));
        });

        $di->set('taskMonitor', function () use ($config) {
            try {
                $sentinelRedis = new \NCFGroup\Task\Instrument\SentinelRedis($config->taskSentinels->toArray(), 1, false);
                $redis = $sentinelRedis->getRedisInstance();
                if(!$redis) {
                    return false;
                }
                // redis库默认1
                $result = $redis->select(1);
                if (!$result) {
                    return false;
                }
                return $redis;
            } catch (\Exception $e) {
                return false;
            }
        });

        $di->set('taskRedis', function () use ($config) {
            try{
                $redis = new \Redis();
                $redis->pconnect($config->taskRedis->host, $config->taskRedis->port, $config->taskRedis->timeout);

                return $redis;
            }catch (\Exception $e) {
                return false;
            }
        });

        $di->setShared('frequencyHandler', function () use ($di, $config) {
            return new FrequencyHandler($di->get('taskRedis'));
        });

        $di->setShared('taskLogger', function () use ($config) {
            return new FileAdapter($config->taskLogger->file->path);
        });

        $di->set('modelsMetadata', function () use ($config) {
            $metaData = new \Phalcon\Mvc\Model\Metadata\Files(array(
                'metaDataDir' => $config->application->metaDataDir,
            ));

            return $metaData;
        });
    }
}
