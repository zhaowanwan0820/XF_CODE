<?php
/**
 * @检查绑定银行卡脚本
 * @author:liuzhenpeng
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';

require_once dirname(__FILE__) . "/../libs/utils/PhalconRPCInject.php";
use libs\rpc\Rpc;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
\libs\utils\PhalconRpcInject::init();

use core\dao\DealLoanRepayModel;
use core\service\DealCompoundService;

set_time_limit(0);
ini_set('memory_limit', '2048M');

if(!($GLOBALS['openbackRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)){
        $openbackRpcConfig = $GLOBALS['components_config']['components']['rpc']['openback'];
            $GLOBALS['openbackRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($openbackRpcConfig['rpcServerUri'], $openbackRpcConfig['rpcClientId'], $openbackRpcConfig['rpcSecretKey']);
}

$title = 'p2peye检查绑定银行卡脚本:';
$request = new SimpleRequestBase();
$request->setParamArray(array('basedata' => 100));
$data = $GLOBALS['openbackRpc']->callByObject(array('service'=>'NCFGroup\Open\Services\WebUnion', 'method'=>'getNotBindCardUser', 'args'=>$request));
if(!count($data)){
        libs\utils\logger::debug($title . '没有要检查的数据');exit;
}

foreach($data as $vals){
    $id_list[] = $vals['userId'];
    $key_list[$vals['userId']]    = $vals['bindKey'];
    $mobile_list[$vals['userId']] = $vals['mobile'];
}

$users = implode(',',$id_list);
libs\utils\logger::debug($title . '需要查询的的userid:' . $users);

$res = $GLOBALS['db']->get_slave()->getAll("SELECT id,user_id FROM firstp2p_user_bankcard WHERE user_id in ($users)");
if(empty($res)){
        libs\utils\logger::debug($title . '没有查到对应的绑定银行卡' . $users);
}
foreach($res as $vals){
    if(empty($mobile_list[$vals['user_id']])) continue;

    $bind_mobile_list[$mobile_list[$vals['user_id']]] = $key_list[$vals['user_id']];
    $bind_user_list[] = $vals['user_id'];
}

if(!count($bind_user_list)){
    libs\utils\logger::debug($title . '没有绑定银行卡的数据:');exit;
}

libs\utils\logger::debug($title . '已经绑定银行卡的userid:' . implode(',', $bind_user_list));
$request = new SimpleRequestBase();
$request->setParamArray(array('mobile_lists' => json_encode($bind_mobile_list)));
$data = $GLOBALS['openbackRpc']->callByObject(array('service'=>'NCFGroup\Open\Services\WebUnion', 'method'=>'sendBindCardList', 'args'=>$request));



