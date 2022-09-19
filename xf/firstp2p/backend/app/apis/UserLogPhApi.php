<?php

namespace NCFGroup\Ptp\Apis;

use libs\db\Db;
use libs\utils\Logger;
use core\dao\UserLogModel;
use core\service\WashUserLogService;
use libs\utils\PaymentApi;
use NCFGroup\Common\Library\Msgbus;

/**
 * 同步普惠UserLog接口
 */
class UserLogPhApi
{

    const USER_LOG_TOPIC = 'account_log_sync';
    const USER_LOG_REPORT_TOPIC = 'account_log_report';

    /**
     * 同步数据
     */
    public function sync()
    {
        $input = file_get_contents('php://input');
        Logger::info(implode('|', [__METHOD__, $input]));
        $params = json_decode($input, true);

        if ($params['Topic'] != self::USER_LOG_TOPIC) {
            return $this->echoJson(10001, 'error topic');
        }

        $data = json_decode($params['Message'], true);
        $phId = intval($data['id']);
        $userId = intval($data['user_id']);

        if (empty($phId)) {
            return $this->echoJson(10002, 'id not empty');
        }
        if (empty($userId)) {
            return $this->echoJson(10002, 'userId not empty');
        }

        $type = sprintf('ULOGPH_%s', $data['user_id']);
        $orderId = $phId;

        $db = Db::getInstance('firstp2p');

        $sql = "SELECT * FROM `firstp2p_user_log_sync` WHERE `user_id` = {$userId} AND `ph_id` = {$phId}";
        $res = $db->getOne($sql, true);
        if ($res) {
            return $this->echoJson(0, 'success');
        }

        try {

            $db->startTrans();

            $user_log = new UserLogModel();
            $user_log->log_info = $data['log_info'];
            $user_log->note = $data['note'];
            $user_log->log_time = $data['log_time'];
            $user_log->log_admin_id = $data['log_admin_id'];
            $user_log->log_user_id = $data['log_user_id'];
            $user_log->user_id = $userId;
            $user_log->biz_token = isset($data['biz_token']) ? $data['biz_token'] : ''; //业务标识
            $user_log->deal_type = $data['deal_type'];
            $user_log->money = isset($data['money']) ? floatval($data['money']) : 0;
            $user_log->lock_money = isset($data['lock_money']) ? floatval($data['lock_money']) : 0;
            $user_log->remaining_money = $data['remaining_money'];
            $user_log->remaining_total_money = $data['remaining_total_money'];
            $user_log->remaining_lock_money = isset($data['remaining_lock_money']) ? $data['remaining_lock_money'] : '';
            $user_log->deal_id = isset($data['deal_id']) ? $data['deal_id'] : 0; //标ID
            $user_log->out_order_id = isset($data['out_order_id']) ? $data['out_order_id'] : ''; //业务ID

            if(!$user_log->save()){
                throw new \Exception("insert log error");
            }

            $wxId = $user_log->id;
            $sql = [
                'ph_id' => $phId,
                'wx_id' => $wxId,
                'user_id' => $userId,
                'create_time' => time(),
            ];
            if (!$db->insert('firstp2p_user_log_sync', $sql)) {
                throw new \Exception("already insert");

            }

            // 修改消息的id为网信库id, 实现全局幂等
            $data['id'] = $wxId;
            // 产生消息
            Msgbus::instance()->produce(self::USER_LOG_REPORT_TOPIC, $data);

            Logger::info(implode('|', [__METHOD__, $userId, $phId, $wxId]));
            $db->commit();
        } catch (\Exception $e) {

            $db->rollback();
            Logger::info(implode('|', [__METHOD__, $e->getMessage(), $input]));

            return $this->echoJson(10000, $e->getMessage());
        }
        return $this->echoJson(0, 'success');

    }

    /**
     * 处理ifa 用户流水推送
     */
    public function handleIfaUserLog()
    {
        $input = file_get_contents('php://input');
        PaymentApi::log(implode('|', [__METHOD__, $input]));
        $params = json_decode($input, true);

        if ($params['Topic'] != self::USER_LOG_REPORT_TOPIC) {
            return $this->echoJson(10001, 'error topic');
        }

        $data = json_decode($params['Message'], true);
        $partition = $data['user_id'] % 64;
        $service = new WashUserLogService($partition);
        if (!in_array($data['log_info'], array_keys($service->allowUserLogInfo)))
        {
            // 不符合处理数据类型
            return $this->echoJson(0, 'success');
        }
        $logData = $service->parseUserLog($data);
        if ($logData === true || $logData === false)
        {
            return $this->echoJson(0, 'success');
        }
        $service->addLog($logData);
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
