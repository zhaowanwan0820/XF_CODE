<?php
/**
 * @统计开放平台当天的注册人数、投资金额
 * @author:liuzhenpeng
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';

require_once dirname(__FILE__) . "/../libs/utils/PhalconRPCInject.php";
use libs\rpc\Rpc;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
\libs\utils\PhalconRpcInject::init();

set_time_limit(0);
ini_set('memory_limit', '2048M');

if(!($GLOBALS['openbackRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)){
    $openbackRpcConfig = $GLOBALS['components_config']['components']['rpc']['openback'];
    $GLOBALS['openbackRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($openbackRpcConfig['rpcServerUri'], $openbackRpcConfig['rpcClientId'], $openbackRpcConfig['rpcSecretKey']);
}

$request = new SimpleRequestBase();
$request->setParamArray(array('basedata' => 100));
$data = $GLOBALS['openbackRpc']->callByObject(array('service'=>'NCFGroup\Open\Services\CrontabPush', 'method'=>'mainPush', 'args'=>$request));
$msg = ($data->resCode ==0) ? '推送成功' : '推送失败,请在开放平台日志文件中查看错误原因';
libs\utils\logger::debug('crontab_stats:' . $msg);
echo "done,$msg";
exit;

