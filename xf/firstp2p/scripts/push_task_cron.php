<?php
require(dirname(__FILE__) . '/../app/init.php');
require(APP_ROOT_PATH.'libs/utils/PhalconRPCInject.php');
\libs\utils\PhalconRpcInject::init();
use core\dao\PushTaskModel;
use libs\db\Db;
use core\service\PushService;
use core\service\MsgBoxService;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
error_reporting(E_ALL);
set_time_limit(0);
$pushService = new PushService();
$msgBoxService = new MsgBoxService();
$db = Db::getInstance('firstp2p');
$sql = 'SELECT * FROM firstp2p_push_task WHERE is_delete = ' . PushTaskModel::NO_DELETE. ' AND send_status = ' . PushTaskModel::SEND_INIT. ' AND send_time < UNIX_TIMESTAMP()';
$pushTasks = $db->getAll($sql);
if (empty($pushTasks)) {
    return false;
}

function sendMsg($userId, $task) {

    $pushService = new PushService();
    $msgBoxService = new MsgBoxService();
    $userId = intval($userId);

    try {
        if ($task['type'] == PushTaskModel::TASK_MSG) {
            $extraContent = [];
            if ($task['url']) {
                $extraContent = [
                    'url' => $task['url'],
                    'turn_type' => MsgBoxEnum::TURN_TYPE_URL
                ];
            }
            return $msgBoxService->create($userId, $task['msg_type'], $task['title'], $task['content'], $extraContent);
        }

        return $pushService->toSingle($userId, $task['content'], $task['title']);
    } catch (\Exception $e) {
        PaymentApi::log('pushTask任务:' .$task['id'] . '用户:'. $userId. '失败:' . $e->getMessage());
    }
}

PaymentApi::log('pushTask消息推送任务开始, 任务量:' . count($pushTasks));
foreach($pushTasks as $task) {
    $sql = "UPDATE firstp2p_push_task SET send_status = " .PushTaskModel::SEND_PROCESS. " WHERE id = {$task['id']} AND send_status = " . PushTaskModel::SEND_INIT;
    $res = $db->query($sql);
    if (!$res || $db->affected_rows() == 0) {
        PaymentApi::log('pushTask任务' . $task['id'] . '已被其他进程处理');
        continue;
    }
    try {
        switch($task['scope']) {
        case PushTaskModel::SCOPE_ALL:
            try {
                if ($task['type'] == PushTaskModel::TASK_MSG) {
                    $data = [
                        'title' => $task['title'],
                        'content' => $task['content'],
                        'url' => $task['url'],
                        'status' => 1,
                        'type' => 0,
                        'create_time' => time()
                    ];
                    $msgBoxDb = Db::getInstance('msg_box');
                    $res = $msgBoxDb->insert('firstp2p_notice', $data);
                }
                $pushService->toAll($task['title'], $task['content']);
            } catch (\Exception $e) {
                PaymentApi::log('pushTask任务:' .$task['id']. '失败:' . $e->getMessage());
            }
            break;
        case PushTaskModel::SCOPE_CSV:
            $file = 'http:' . app_conf("STATIC_HOST") .'/' . $task['param'];
            PaymentApi::log('pushTask任务:文件地址.' . $file );
            $data = file($file);
            if (empty($data)) {
                throw new \Exception('pushTask Ftp服务不可用' . $task['id']);
            }
            array_shift($data);
            foreach($data as $userId) {
                sendMsg($userId, $task);
            }
            break;
        case PushTaskModel::SCOPE_USERIDS:
            $userIds = explode(',', $task['param']);
            foreach($userIds as $userId) {
                sendMsg($userId, $task);
            }
            break;
        default:
            break;
        }
        $sql = "UPDATE firstp2p_push_task SET send_status = " .PushTaskModel::SEND_COMPLETE. " WHERE id = {$task['id']}";
        $res = $db->query($sql);
        if ($res) {
            PaymentApi::log('pushTask任务' . $task['id'] . '处理成功');
        } else {
            PaymentApi::log('pushTask任务' . $task['id'] . '更新失败');
        }
    } catch (\Exception $e) {
        PaymentApi::log('pushTask任务' . $task['id'] . '失败' . $e->getMessage());
        $sql = "UPDATE firstp2p_push_task SET send_status = " .PushTaskModel::SEND_INIT. " WHERE id = {$task['id']}";
        $db->query($sql);
    }
}
