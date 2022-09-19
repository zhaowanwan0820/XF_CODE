<?php
/**
 * @desc 对于投资失败的进行重试
 * ----------------------------------------------------------------------------
 * 监控每1分钟运行一次
 * ----------------------------------------------------------------------------
 *
 * ----------------------------------------------------------------------------
 */

set_time_limit(0);
ini_set('memory_limit','1024M');

require_once dirname(__FILE__)."/../../app/init.php";

use core\service\DtBidService;
use core\dao\IdempotentModel;
use core\dao\UserModel;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\RequestCommon;

class BidRetry {
    public $db;
    public function __construct() {
        $this->db = $GLOBALS["db"];
    }

    public function run() {
        $condition = "source='duotou' and mark=1 and status=".IdempotentModel::STATUS_WAIT;
        $res = IdempotentModel::instance()->findAll($condition);
        $rpc = new Rpc('duotouRpc');
        $request = new RequestCommon();
        $s = new DtBidService();

        foreach($res as $row) {
            $token = $row->token;
            $data = json_decode($row->data,true);
            if(empty($data) || !isset($data['userId']) || !isset($data['dealId']) || !isset($data['money'])) {
                continue;
            }

            $request->setVars(array(
                'deal_id' => $data['dealId'],
                'token' => $token,
                'user_id' => $data['userId'],
                'money' => $data['money']
            ));
            $response = $rpc->go('NCFGroup\Duotou\Services\Bid','doBid',$request,2,3);
            $user = UserModel::instance()->find($data['userId']);
            $s->handleRpcResponse($user,$data['dealId'],$data['money'],$token,$response);
        }
    }
}
echo "begin:".date('Y-m-d H:i:s')."\n";
$c = new BidRetry();
$c->run();
echo "end:".date('Y-m-d H:i:s')."\n";
