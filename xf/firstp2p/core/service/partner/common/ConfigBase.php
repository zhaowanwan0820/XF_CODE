<?php
/**
 *@author longbo
 */
namespace core\service\partner\common;

use core\service\partner\common\Container;
use core\service\partner\common\RequestBase;

class ConfigBase
{
    protected $hostList = [];
    protected $apiList = [];
    protected $env = 'online';
    protected $clientInfo = [];
    protected $clientId = null;

    public function __construct()
    {
        $this->env = app_conf('ENV_FLAG') == 'online' ? 'online' : 'test';
        $this->setRequestService();
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getHostConf($key)
    {
        if (!$this->env) {
            $config = $this->hostList['online'][$key];
        } else {
            $config = $this->hostList[$this->env][$key];
        }
        if (empty($config)) {
            throw new \Exception($key.' is null!');
        }
        return $config;
    }

    public function getApi($key)
    {
        if (empty($this->apiList[$key])) {
            throw new \Exception("api name is not found!");
        }
        return $this->apiList[$key];
    }

    protected function setRequestService()
    {
        Container::register('requestService', function(){
                return new RequestBase();
            }
        );
    }

}

