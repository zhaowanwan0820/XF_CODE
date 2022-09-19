<?php
namespace task\controllers\dealbidsucess;

use task\controllers\BaseAction;
use libs\utils\Logger;
use core\service\risk\RiskService;
use core\service\user\UserService;
use core\service\deal\P2pIdempotentService;


/**
 * 投资成功后 report信息反作弊RiskService
 * Class Create
 * @package task\controllers\dealbidsucess
 */
class ReportRisk extends BaseAction {

    public function invoke() {
        // msgbus传递的msg信息本身也需要json_decode
        $param = json_decode($this->getParams(),true);
        $userInfo = UserService::getUserById($param['user_id'], 'id, user_name, mobile');
        $orderInfo = P2pIdempotentService::getInfoByOrderId($param['order_id']);
        $dempotent = json_decode(stripslashes($orderInfo['params']),true);
        try{
            $extra = array(
                'user_name' => $userInfo['user_name'],
               "user_id" =>  $param['user_id'],
               "mobile" =>  $userInfo['mobile'],
                "bid_type" =>   $param['deal_type'],
               "amount" => $param['money']*100,
                "business_time" => $param['time'],
                "order_id" => $param['load_id'],
                 "ip" => $param['ip'],
                 "fingerprint" => $dempotent['fingerprint']);
            $checkRet = RiskService::report('BID', RiskService::STATUS_SUCCESS, $extra);
            if (!$checkRet) {
                Logger::error (sprintf('%s | %s, userId: %s, amount: %s, %s', __CLASS__, __FUNCTION__, $param['user_id'],  $param['money'], 'risk report false'));
                return false;
            }
        } catch (\Exception $ex) {
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
