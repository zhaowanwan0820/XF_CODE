<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\candy\CandyActivityService;
use libs\utils\Logger;

/**
 * 信宝相关内部接口
 */
class CandyActivityApi extends ApiBackend
{

    // 允许的类型
    private $sourceTypeAllowed = array(
        CandyActivityService::SOURCE_TYPE_P2P => 1,
        CandyActivityService::SOURCE_TYPE_DT => 1,
    );

    /**
     * 按业务类型增加类型
     */
    public function activityCreateByType()
    {
        $token = $this->getParam('token');
        $userId = $this->getParam('userId');
        $sourceType = $this->getParam('sourceType');
        $sourceValue = $this->getParam('sourceValue');
        $sourceValueExtra = $this->getParam('sourceValueExtra');

        if (empty($token) || empty($userId) || empty($sourceValue)) {
            return $this->formatResult(array(), -1, '参数错误');
        }

        if (!isset($this->sourceTypeAllowed[$sourceType])) {
            return $this->formatResult(array(), -1, '参数错误：不允许的业务类型');
        }

        try {
            $activityService = new CandyActivityService();
            $activity = $activityService->activityCreateByType($sourceType, $token, $userId, $sourceValue, $sourceValueExtra);
        } catch (\Exception $e) {
            return $this->formatResult(array(), $e->getCode(), $e->getMessage());
        }

        return $this->formatResult(array('activity' => $activity));
    }

    /**
     * 按照token增加
     * 从PtpCandyService中迁移过来
     * @return [type] [description]
     */
    public function activityCreateByToken()
    {
        $userId = $this->getParam('userId');
        $token = $this->getParam('token');
        $activity = $this->getParam('activity');
        $sourceType = $this->getParam('sourceType');

        Logger::info(implode('|', [__METHOD__, 'START', $token]));

        if ($sourceType <= 0) {
            $sourceType = CandyActivityService::SOURCE_TYPE_BOUNS;
        }

        try {
            $activity = (new CandyActivityService)->activityCreateByToken($token, $userId, $activity, $sourceType);
        } catch (\Exception $e) {
            Logger::info(implode('|', [__METHOD__, $e->getMessage(), $token]));
            if ($e->getCode() == CandyActivityService::ERR_CODE_TOKEN) {
                return $this->formatResult(['activity' => $activity]);
            }
            return $this->formatResult([], $e->getCode(), $e->getMessage());
        }
        return $this->formatResult(['activity' => $activity]);
    }
}
