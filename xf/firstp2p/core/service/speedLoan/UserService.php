<?php
/**
 * 用户信用服务类
 * @data 2017.09.13
 * @author weiwei12 weiwei12@ucfgroup.com
 */

namespace core\service\speedLoan;

use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\RequestCommon;
use libs\utils\Rpc;

class UserService extends BaseService
{
    /**
     * 获取用户信用数据
     * @return array
     */
    public function getUserCreditInfo($userId)
    {
        $request = new RequestCommon();
        $request->setVars(['userId'=>$userId]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'getUserCreditInfo', $request);
        return $response;
    }

    public function applyCredit($params)
    {
        $request = new RequestCommon();
        $request->setVars(['userId'=>$params['userId'], 'frontPhoto' => 'a', 'backPhoto' => 'c', 'handHoldPhoto' => 'b']);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'applyCredit', $request);
        return $response;
    }

    public function refreshUserCreditAmount($params)
    {
        $request = new RequestCommon();
        $request->setVars(['userId'=>$params['userId'], 'totalAsset' => $params['totalAsset'], 'totalAmount' => $params['totalAmount']]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'refreshUserCreditAmount', $request);
        return $response;
    }

    public function creditNotify($params)
    {
        $request = new RequestCommon();
        $request->setVars(['userId'=>$params['userId'], 'creditStatus'=> $params['creditStatus']]);
        $response = $this->requestCreditloan('NCFGroup\Creditloan\Services\CreditUser', 'creditNotify', $request);
        return $response;
    }

}
