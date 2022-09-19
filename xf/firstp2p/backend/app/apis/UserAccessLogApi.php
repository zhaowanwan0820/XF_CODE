<?php

namespace NCFGroup\Ptp\Apis;

use libs\db\Db;
use libs\utils\Logger;
use core\service\UserAccessLogService;
use NCFGroup\Common\Library\Msgbus;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;

/**
 * 用户访问日志接口
 */
class UserAccessLogApi
{
    /**
     * 保存日志数据
     */
    public function saveLog()
    {
        $input = file_get_contents('php://input');
        Logger::info(implode('|', [__METHOD__, $input]));
        $params = json_decode($input, true);

        if ($params['Topic'] != UserAccessLogEnum::USER_ACCESS_LOG_TOPIC) {
            return $this->echoJson(10001, 'error topic');
        }

        $data = json_decode($params['Message'], true);
        if (empty($data)) {
            return $this->echoJson(10002, 'error message');
        }

        $userAccessLogService = new UserAccessLogService();
        $result = $userAccessLogService->saveLog($data);
        if (empty($result)) {
            return $this->echoJson(10003, 'error save');
        }
        return $this->echoJson(0, 'success');

    }

    public function echoJson($code, $msg)
    {
        if ($code != 0) {
            Logger::info(implode('|', [__METHOD__, $msg]));
        }
        echo json_encode([
            'code' => $code,
            'message' => $msg,
        ]);
        die;
    }
}
