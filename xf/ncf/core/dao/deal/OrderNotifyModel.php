<?php
namespace core\dao\deal;

use core\dao\BaseModel;
class OrderNotifyModel extends BaseModel
{

    const STATUS_UNNOTIFY = 0; // 未通知
    const STATUS_FAIL = 1; // 失败
    const STATUS_SUCCESS = 2; // 成功

    public function insertData($data)
    {
        if (empty($data)) return false;

        $this->client_id = $data['client_id'];
        $this->order_id = $data['order_id'];
        $this->notify_url = $data['notify_url'];
        $this->notify_params = json_encode($data['notify_params']);

        $this->status = self::STATUS_UNNOTIFY;
        $this->latest_notify_time = 0;
        $this->notify_cnt = 0;
        $this->create_time = $this->update_time = $this->next_notify_time = time();

        if ($this->insert()) {
            return $this->db->insert_id();
        } else {
            return false;
        }
    }

    public function updateParams($clientId, $orderId, $params)
    {

        $condition = sprintf("`client_id` = '%s' AND `order_id` = '%s'",
            $this->escape($clientId), $this->escape($orderId));
        return $this->updateBy(['notify_params' => json_encode($params)], $condition);
    }


    public function findViaOrderId($clientId, $orderId)
    {
        return $this->findByViaSlave('`client_id` = ":clientId" AND `order_id` = ":orderId"',
            '*', [':clientId' => $clientId, ':orderId' => $orderId]);
    }

    public function findToNotify($page, $size)
    {
        $start = ($page - 1) * $size;
        $now = time();
        $condition = "`status` <> 2 AND `next_notify_time` <= {$now} LIMIT {$start}, {$size}";
        return $this->findAll($condition, true);
    }


    public function updateNotifyStatus($id, $status, $nextNotifyTime, $errMsg)
    {
        $now = time();
        $sql = "UPDATE `firstp2p_order_notify`
                SET `status` = %s,
                    `next_notify_time` = %s,
                    `err_msg` = '%s',
                    `latest_notify_time` = %s,
                    `update_time` = %s,
                    `notify_cnt` = `notify_cnt` + 1
                WHERE `id` = %s";

        $sql = sprintf($sql, $this->escape($status), $this->escape($nextNotifyTime),
            $this->escape($errMsg), $now, $now, $this->escape($id));

        return $this->execute($sql);
    }
}