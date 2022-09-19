<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\daos\RegionDAO;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\candy\CandyActivityService;
use core\service\UserService;
use libs\utils\Logger;
use core\service\candy\CandyEventService;

/**
 * PtpCandyService
 *
 * @uses ServiceBase
 * @package default
 */
class PtpCandyService extends ServiceBase
{
    public function activityCreateByToken(SimpleRequestBase $req)
    {
        $userId = $req->userId;
        $token = $req->token;
        $activity = $req->activity;
        $sourceType = $req->sourceType;

        Logger::info(implode('|', [__METHOD__, 'START', $token]));

        if ($sourceType <= 0) {
            $sourceType = CandyActivityService::SOURCE_TYPE_BOUNS;
        }

        try {

            return (new CandyActivityService)->activityCreateByToken($token, $userId, $activity, $sourceType);

        } catch (\Exception $e) {
            Logger::info(implode('|', [__METHOD__, $e->getMessage(), $token]));
            if ($e->getCode() == CandyActivityService::ERR_CODE_TOKEN) return $activity;
            throw $e;
        }
    }

    public function checkBonus(SimpleRequestBase $req)
    {
        $userId = $req->userId;
        return (new CandyActivityService)->inBonus($userId) && (new UserService())->hasLoan($userId);
    }

    public function acquireAssistanceCandy(SimpleRequestBase $req)
    {
        $userId = $req->userId;
        $amount = $req->amount;
        $token = $req->token;
        $info = $req->info;
        try {
            return (new CandyEventService())->changeAmount(CandyEventService::EVENT_ID_ASSISTANCE, $token, $userId, $amount, $info);
        } catch (\Exception $e) {
            if ($e->getCode() == CandyEventService::EXCEPTION_CODE_TOKEN_EXISTS) return true;
        }

        return false;
    }
}
