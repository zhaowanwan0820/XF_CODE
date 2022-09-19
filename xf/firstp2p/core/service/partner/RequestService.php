<?php
/**
 *@author longbo
 */

namespace core\service\partner;

use core\service\partner\common\Container;
use core\service\partner\common\Tools;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\PartnerRequestEvent;
use libs\utils\Logger;
use libs\utils\Monitor;
use Exception;

class RequestService
{
    static $projectConfigMap = [];
    private $apiName = null;
    private $postData = [];
    private $getData = [];
    private $project = null;
    private $retries = 2;
    private $timeout = 10;
    private $asyn = false;
    private $asynRetries = 0;

    private static $serviceObj = [];

    public function __construct($project)
    {
        $this->project = $project;
        return $this;
    }

    public static function init($project)
    {
        $project = trim($project);
        if (!(isset(self::$serviceObj[$project]) && self::$serviceObj[$project] instanceof RequestService)) {
            self::$serviceObj[$project] =  new RequestService($project);
        }
        return self::$serviceObj[$project];
    }

    public function request()
    {
        $projectConf = $this->_getConfigClass($this->project);
        $apiInfo = $projectConf->getApi($this->apiName);
        $request = [];
        $request['host'] = $projectConf->getHostConf('host');
        $request['action'] = $apiInfo['action'];
        $request['secret'] = $projectConf->getHostConf('client_secret');
        if (!empty($apiInfo['get'])) {
            $request['getParams'] = $this->_validate($apiInfo['get'], $this->getData);
        }
        $request['postParams'] = $this->_validate($apiInfo['post'], $this->postData);
        $request['method'] = isset($apiInfo['method']) ? $apiInfo['method'] : 'post';

        Monitor::add(strtoupper('REQSRV_'.$this->project.'_'.str_replace('.', '_', $this->apiName)));
        if (true === $this->asyn) {
            $requestServer = Container::book('requestService');
            $event = new PartnerRequestEvent($requestServer, $request);
            $taskObj = new GTaskService();
            $taskId = $taskObj->doBackground($event, $this->asynRetries);
            Logger::info("GtaskId:".$taskId);
            return $taskId;
        } else {
            $res = Container::book('requestService')
                ->setRequestData($request)
                ->execute();
            return $this->output($res);
        }
    }

    public function output($res)
    {
        return $res::$response;
    }

    public function setApi($apiName)
    {
        $this->apiName = $apiName;
        return $this;
    }

    public function setPost($post)
    {
        $this->_toOpenId($post);
        $this->postData = $post;
        return $this;
    }

    public function setGet($get)
    {
        $this->_toOpenId($post);
        $this->getData = $get;
        return $this;
    }

    public function setRetries($times)
    {
        $this->retries = $times;
        return $this;
    }

    public function setTimeout($time)
    {
        $this->timeout = $time;
        return $this;
    }

    public function setAsyn($retries = 5)
    {
        $this->asyn = true;
        $this->asynRetries = $retries;
        return $this;
    }

    public function getConfig($key = '')
    {
        try {
            $conf = $this->_getConfigClass($this->project);
            $res = $conf->getHostConf($key);
        } catch (\Exception $e) {
            Logger::error('GetPartnerConfigError:'.$e->getMessage());
            $res = '';
        }
        return $res;
    }

    private function _getConfigClass($project)
    {
        $projectConfClass = isset(self::$projectConfigMap[$project]) ?
            self::$projectConfigMap[$project] :
            'core\\service\\partner\\'.strtolower($project).'\\Config';

        if (class_exists($projectConfClass)) {
            return new $projectConfClass();
        } else {
            throw new Exception('project config is not found!');
        }
    }

    private function _validate($params, $req)
    {
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                if (!empty($v['required']) && (!isset($req[$k]) || $req[$k] == '')) {
                    throw new Exception($k.' is required!');
                }
            }
        }
        return $req;
    }

    private function _toOpenId(&$data)
    {
        if (isset($data['open_id'])) {
            $data['open_id'] = Tools::encryptID($data['open_id']);
        }
    }

}


