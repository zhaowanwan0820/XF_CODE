<?php

namespace itzlib\sdk;

use Exception;
use ZooKeeper;
use Yii;

/**
 * 服务发现类
 * Created by PhpStorm.
 * User: Devon
 * Date: 16/8/12
 * Time: 10:32
 */
class ServiceDiscovery
{
    /**
     * 服务配置 node
     * @var string
     */
    public $node = '';
    /**
     * Zookeeper 地址
     * @var string
     */
    public $address = '';
    /**
     * @var null|Zookeeper
     */
    private $zookeeper = null;


    public function __construct($address = '', $node = '/itzservices')
    {
        if (extension_loaded('zookeeper')) {
            $this->connect($address);
            $this->node = $node;
        } else {
            throw new \ErrorException("Zookeeper extension not found.");
        }
    }

    public function connect($address = false)
    {
        $address = $address ?: $this->address;
        $zk = new Zookeeper($address);
        $this->zookeeper = $zk;
    }

    public function run()
    {
        if (!is_null($this->zookeeper)) {
            Yii::log("Service discovering...", \CLogger::LEVEL_INFO);
            $this->cacheService();
        }
    }

    public function callback($event, $stats, $node)
    {
        $this->cacheService();
        $value = $this->zookeeper->get($node, [$this, __FUNCTION__]);
        return $value;
    }

    public function cacheService()
    {
        Yii::log("Caching config", \CLogger::LEVEL_TRACE);
        $service = $this->fetchService();

        if($service){//如果获得的
            $content = ['create_time' => time(), 'service_list' => $service];
            file_put_contents('/tmp/service.json', json_encode($content));
            Yii::log("Caching cached successful", \CLogger::LEVEL_TRACE);
        }else{
            Yii::log("Caching cached failed: services is null ", \CLogger::LEVEL_TRACE);
        }
    }

    /**
     * @return array
     */
    public function fetchService()
    {
        $zk = $this->zookeeper;
        $root = $this->node;
        /* return empty array if node /itzservices is not exists */
        if (!$zk->exists($root)) {
            return [];
        }

        $serviceNodes = $zk->getChildren($root);
        /* return empty array if node /itzservices is empty */
        if (empty($serviceNodes)) {
            return [];
        }

        $services = [];
        foreach ($serviceNodes as $service) {
            $serviceNodePath = implode('/', [$root, $service]);
            /* 获取服务节点值， 如 协议，健康检查地址。 */
            $services[$service]['value'] = json_decode($zk->get($serviceNodePath), true);

            $providerNodePath = implode('/', [$serviceNodePath, 'providers']);
            $child = [];
            if ($zk->exists($providerNodePath)) {
                $nodes = $zk->getChildren($providerNodePath);
                if (!empty($nodes)) {
                    foreach ($nodes as $node) {
                        $nodePath = implode('/', [$providerNodePath, $node]);
                        $nodeValue = json_decode($zk->get($nodePath), true);
                        if (isset($nodeValue['status']) && $nodeValue['status'] == 1) {
                            $child[$node] = $nodeValue;
                        } else {
                            unset($nodePath, $nodeValue);
                        }
                    }
                }
            }

            $services[$service]['providers'] = $child;
        }

        return $services;
    }

}
