<?php
/**
 * 签到Model
 * @author longbo
 */

namespace core\dao;
use libs\db\MysqlDb;

class UserCheckinModel extends BaseModel
{
    public function add($user_id)
    {
        $data = array(
            'user_id' => $user_id,
            'first_time' => time(),
            'recent_time' => time(),
            'round_data' => json_encode([time()]),
            'current_count' => 1,
            'sum' => 1,
            'create_time' => time(),
            'is_effect' => 1,
        );
        $this->setRow($data);

        if ($this->insert()) {
            return $this->getRow();
        } else {
            return false;
        }
    }

    public function updateData($user_id, $data)
    {
        if (empty($user_id) || empty($data)) {
            return false;
        }
        $sql = "update firstp2p_user_checkin set ";
        foreach($data as $k => $v) {
            if (is_array($v)) {
                $sql_arr[] = $k . "=" . $v[0];
            } else {
                $sql_arr[] = $k . "='" . $v . "'";
            }
        }
        $sql .= implode(',', $sql_arr);
        $sql .= ' where user_id='.$user_id;
        return $this->updateRows($sql);
    }

}
