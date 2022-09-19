<?php

namespace NCFGroup\Ptp\services;

use Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Library\Logger;
use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Cache\RedisCache;
use core\service\MobileCodeService;


class PtpMobileCodeService extends ServiceBase {

    public function checkMobileCode(SimpleRequestBase $request) {
        $param = $request->getParamArray();
        $serviceObj = new MobileCodeService();
        $param['isPc'] = isset($param['isPc']) ? $param['isPc'] : 1;

        $response = new ResponseBase();
        $response->code = $serviceObj->getMobilePhoneTimeVcode($param['mobile'], 180, $param['isPc']);
        return $response;
    }

}
