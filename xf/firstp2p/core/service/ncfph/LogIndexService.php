<?php

namespace core\service\ncfph;

use libs\db\Db;
use libs\utils\Logger;

class LogIndexService
{
    const SERVICE_TYPE_USER_LOG_ZX  = 1; //资金记录，投资专享
    const SERVICE_TYPE_USER_LOG_PH  = 2; //资金记录，投资普惠

    public function createUserLogIndexZX($userId, $serviceId, $serviceInfo, $serviceTime = '')
    {
        return $this->create($userId, $serviceId, $serviceInfo, $serviceTime, self::SERVICE_TYPE_USER_LOG_ZX);
    }

    public function createUserLogIndexP2P($userId, $serviceId, $serviceInfo, $serviceTime = '')
    {
        return $this->create($userId, $serviceId, $serviceInfo, $serviceTime, self::SERVICE_TYPE_USER_LOG_PH);
    }

    public function create($userId, $serviceId, $serviceInfo, $serviceTime, $type)
    {
        $db = Db::getInstance();

        $now = time();
        if ($serviceTime == '') {
            $serviceTime = $now;
        }

        $token = implode('_', [$userId, $serviceId, $type]);
        try {
            $insertData = [
                'user_id' => $userId,
                'service_type' => $type,
                'token' => $token,
                'service_info' => $serviceInfo,
                'service_id' => $serviceId,
                'service_time' => $serviceTime,
                'create_time' => $createTime
            ];
            $middleIndexId = $db->insert($insertData);
        } catch (\Exception $e) {
            Logger::error(implode(' | ', [__CLASS__, __FUNCTION__, __LINE__, $e->getMessage(), json_encode($insertData)]));
            return false;
        }

        Logger::error(implode(' | ', [__CLASS__, __FUNCTION__, __LINE__, $middleIndexId, json_encode($insertData)]));

        return $middleIndexId;
    }

    public function getUserLogPage($userId, $page = [], $serviceInfo = '')
    {
        $db = Db::getInstance();
        $offset = ($page - 1) * count;
        $types = implode(',', [self::SERVICE_TYPE_USER_LOG_ZX, self::SERVICE_TYPE_USER_LOG_PH]);

        return $db->getAll("SELECT * FROM middle_index WHERE user_id = '{$userId}' AND type IN({$types}) ORDER BY service_time DESC LIMIT {$offset}, {$count}");
    }
}

