<?php

namespace NCFGroup\Ptp;

use Phalcon\DI\FactoryDefault;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use NCFGroup\Common\Extensions\Cache\RedisCache;
use NCFGroup\Common\Phalcon\Module as CommonModule;
use NCFGroup\Common\Extensions\Varz\VarzAdapter;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Ptp\Instrument\Listeners\DbListener;

class Srv extends CommonModule {

    public function registerAutoloaders() {
        $loader = new \Phalcon\Loader();
        $loader->registerDirs(array(
            __DIR__ . "/models/",
            __DIR__ . "/services/",
            __DIR__ . "/exceptions/",
            __DIR__ . "/daos/",
            APP_ROOT_DIR . "/core/dao",
            APP_ROOT_DIR . "/Common/Vendor/jsonmapper/src/",
        ))->register();
        $loader->registerNamespaces(array(
            'NCFGroup\Ptp' => __DIR__ . '/',
            'NCFGroup\Ptp\services' => __DIR__ . '/services/',
            'NCFGroup\Ptp\Instrument' => __DIR__ . '/Instrument/',
            'NCFGroup\Ptp\daos' => __DIR__ . '/daos/',
            'NCFGroup\Ptp\models' => __DIR__ . '/models/',
            'Minime\Annotations' => APP_ROOT_DIR . "/Common/Vendor/annotations/src/",
            'core\service' => APP_ROOT_DIR . "/core/service",
            'NCFGroup\Ptp\Apis' => __DIR__ . '/apis/',
        ))->register();

        require_once APP_ROOT_DIR . "/Common/Vendor/guzzle/vendor/autoload.php";
        //require_once APP_ROOT_DIR . "/Common/Vendor/PHPMailer/PHPMailerAutoload.php"; //接张总指示，报PHPMailer高危漏洞，目测这个组件没有使用，删除。--20161228
    }

    public function registerServices() {
        $di = $this->di;
        $config = $di->getConfig();

        $di->set('logger', function () use ($config) {
            try {
                $logger = new FileAdapter($config->application->logFilePath);
                return $logger;
            } catch (\Exception $e) {
                throw $e;
            }
        }, true);

        $di->set('varz', function () {
            $varz = new VarzAdapter("bk", new RedisCache());
            $varz->startMonitor();
            return $varz;
        }, true);

        $di->set('profiler', function() {
            return new \Phalcon\Db\Profiler();
        }, true);

        $di->setShared('cfp', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->cfp->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->cfp->host,
                    'username' => $config->cfp->username,
                    'password' => $config->cfp->password,
                    'dbname' => $config->cfp->dbname,
                    'port' => $config->cfp->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->setShared('firstp2p', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->firstp2p->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->firstp2p->host,
                    'username' => $config->firstp2p->username,
                    'password' => $config->firstp2p->password,
                    'dbname' => $config->firstp2p->dbname,
                    'port' => $config->firstp2p->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->setShared('firstp2p_r', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->firstp2p_r->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->firstp2p_r->host,
                    'username' => $config->firstp2p_r->username,
                    'password' => $config->firstp2p_r->password,
                    'dbname' => $config->firstp2p_r->dbname,
                    'port' => $config->firstp2p_r->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->setShared('firstp2p_push', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->firstp2p_push->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->firstp2p_push->host,
                    'username' => $config->firstp2p_push->username,
                    'password' => $config->firstp2p_push->password,
                    'dbname' => $config->firstp2p_push->dbname,
                    'port' => $config->firstp2p_push->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->setShared('firstp2p_push_r', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->firstp2p_push_r->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->firstp2p_push_r->host,
                    'username' => $config->firstp2p_push_r->username,
                    'password' => $config->firstp2p_push_r->password,
                    'dbname' => $config->firstp2p_push_r->dbname,
                    'port' => $config->firstp2p_push_r->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->setShared('firstp2p_msg_box', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->firstp2p_msg_box->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->firstp2p_msg_box->host,
                    'username' => $config->firstp2p_msg_box->username,
                    'password' => $config->firstp2p_msg_box->password,
                    'dbname' => $config->firstp2p_msg_box->dbname,
                    'port' => $config->firstp2p_msg_box->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->setShared('firstp2p_msg_box_r', function () use ($di, $config) {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->attach('db', new DbListener($config->firstp2p_msg_box_r->type));
            try {
                $db = new DbAdapter(array(
                    'host' => $config->firstp2p_msg_box_r->host,
                    'username' => $config->firstp2p_msg_box_r->username,
                    'password' => $config->firstp2p_msg_box_r->password,
                    'dbname' => $config->firstp2p_msg_box_r->dbname,
                    'port' => $config->firstp2p_msg_box_r->port
                ));
                $db->setEventsManager($eventsManager);
                return $db;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        //$di->setShared('modelsManager', function() {
        //    return new \Phalcon\Mvc\Model\Manager();
        //});

        //$di->setShared('transactions', function () {
        //    return new \Phalcon\Mvc\Model\Transaction\Manager();
        //});

        $di->setShared('redis', function () use ($config) {
            try {
                $redis = new \Redis();
                $redis->pconnect($config->redis->host, $config->redis->port);
                return $redis;
            } catch (\Exception $e) {
                throw $e;
            }
        });

        $di->set('modelsMetadata', function () use ($config) {
            $metaData = new \Phalcon\Mvc\Model\Metadata\Files(array(
                'metaDataDir' => $config->application->metaDataDir
            ));
            return $metaData;
        });

        $di->set('crypt', function() {
            $crypt = new Phalcon\Crypt();
            $crypt->setKey('firstp2P@Wangxinjinrong'); //Use your own key!
            //$crypt->setKey('#1dj8$=dp?.ak//j1V$');
            return $crypt;
        });

        if (!defined('TASK_APP_NAME')) {
            define('TASK_APP_NAME', 'p2p');
            $di->getBootstrap()->dependModule('task');
        }

        set_exception_handler(function($exception) use ($di) {
            $error = $exception->getFile() . ':' . $exception->getLine() . PHP_EOL . $exception->getTraceAsString();
            Logger::error($error);
            throw $exception;
        });
    }
}
