<?php

namespace NCFGroup\Ptp\Apis;

use core\dao\DealLoadModel;
use NCFGroup\Common\Library\ApiBackend;
use core\service\MsgBoxService;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

/**
 * 用户信息接口
 */
class UserApi extends ApiBackend {

    /**
     * 发送消息
     * @param $userId int 用户ID
     * @param $message string 消息体
     * @return array
     */
    public function createMessage() {
        $userId = $this->getParam('userId');
        $content = $this->getParam('content');
        $msgBoxService = new MsgBoxService();
        $result = $msgBoxService->create($userId, MsgBoxEnum::TYPE_TASK_CENTER, '任务中心完成任务', $content);

        return $this->formatResult($result);
    }
}
