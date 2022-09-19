<?php
/**
 * rpc 服务封装
 * User: jinhaidong
 * Date: 2015/9/23 11:29
 */


namespace libs\utils;
use NCFGroup\Common\Extensions\RPC\RpcClientAdapter;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class Rpc {

    private $rpcObj = null; // rpc 对象
    private $rpcName = null; // rpc 名字

    // RPC 开关
    private $rpcSwitch = [
        'duotouRpc' => 'DUOTOU_SWITCH', // 多投
        'financeRpc' => 'FINANCE_SWITCH', // 业财系统
        'creditloanRpc' => 'SPEED_LOAN_SWITCH', // 网信速贷
        'contractRpc' => 'CONTRACT_SERVICE_SWITCH', // 合同
        'o2oRpc' => 'O2O_SERVICE_ENABLE', // O2O
        'bonusRpc' => 'BONUS_SERVICE_SWITCH', // 红包
        'marketingRpc' => 'MARKETING_SERVICE_SWITCH', // 营销
    ];

    public function __construct($rpcName) {
        $this->rpcName = $rpcName;

        if(!isset($GLOBALS[$rpcName])) {
            /**
             * 这样加载会导致 Fatal  Cannot redeclare class NCFGroup\Ptp\Srv
             * 原因是phalcon在backend调用里面在调取rpc的时候 Srv类已经声明 框架不支持
             * \libs\utils\PhalconRPCInject::init();
             */
            foreach($GLOBALS['components_config']['components']['rpc'] as $key=>$connectInfo) {
                if(!$connectInfo['mockRpc']) {
                    $_rpcName = $key."Rpc";
                    $GLOBALS[$_rpcName] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($connectInfo['rpcServerUri'], $connectInfo['rpcClientId'], $connectInfo['rpcSecretKey']);
                }
            }
        }
        $this->rpcObj =  $GLOBALS[$rpcName];
    }

    public function go($service,$method,$request,$maxRetryTimes=3,$timeout=3,$connectTimeout=3) {
        try{

            // 服务降级
            if (app_conf($this->rpcSwitch[$this->rpcName]) == 0) {
                throw new \Exception($this->rpcName. ' is down');
            }

            if(!$this->rpcObj) {
                throw new \Exception("rpc:{$this->rpcName} not define");
            }
            $this->rpcObj->setConnectTimeout($connectTimeout);
            $this->rpcObj->setTimeout($timeout);
            $response = false;

            for($i=1;$i<=$maxRetryTimes;$i++) {
                //PaymentApi::log("RPC request retry {$i} services:{$service} method:{$method} request:".json_encode($request));
                Logger::wLog("RPC request retry {$i} services:{$service} method:{$method} request:".json_encode($request),Logger::RPC);
                $response = $this->rpcObj->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
                ));
                if (!empty($response)) {
                    break;
                }
                continue;
            }
        }catch (\Exception $ex) {
            // PaymentApi::log("RPC request ".$ex->getMessage());
            Logger::wLog("RPC request ".$ex->getMessage(),Logger::RPC);
        }

        //PaymentApi::log("RPC response service:$service method:$method response:".json_encode($response));
        Logger::wLog("RPC response service:$service method:$method response:".json_encode($response),Logger::RPC);
        return $response;
    }
}
