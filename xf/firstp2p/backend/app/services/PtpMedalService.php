<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use core\service\O2OService;
use NCFGroup\Protos\Ptp\RequestAcquireMedalCoupon;
use NCFGroup\Protos\Ptp\ResponseAcquireMedalCoupon;


class PtpMedalService extends ServiceBase {
    
    public function acquireMedalCoupon(RequestAcquireMedalCoupon $request) {
        $groupIds = $request->getGroupIds();
        $userId = $request->getUserId();
        $appToken = $request->getAppToken();

        $o2oService = new O2OService();

        $result = $o2oService->acquireMedalCoupon($groupIds, $userId, $appToken);
        $response = new ResponseAcquireMedalCoupon();
        if (empty($result)) {
            $response->setResCode(strval(RPCErrorCode::FAILD));
            $response->setErrorCode('99');
            $response->setErrorMsg('领券失败');
        } else {
            $response->setResCode(strval(RPCErrorCode::SUCCESS));
            $response->setErrorCode('0');
            $response->setErrorMsg('');
        }
        return $response;
    }
}
