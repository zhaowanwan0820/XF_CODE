<?php
/**
 * 用户消息订阅配置
 */

namespace core\dao;

class UserMsgConfigModel extends BaseModel
{

    const MAX_SWITCHES_LENGTH = 255;

    /**
     * 设置开关
     */
    public function setSwitches($userId, $field, array $switches)
    {
        $data = array();
        $data['user_id'] = intval($userId);
        $data[$field] = json_encode($switches);
        $data['update_time'] = time();

        if (strlen($data[$field]) > self::MAX_SWITCHES_LENGTH) {
            throw new \Exception('开关长度超过限制');
        }

        $ret = parent::findBy("user_id='{$data['user_id']}'", 'id');
        if (empty($ret)) {
            $data['create_time'] = time();
            $this->setRow($data);
            return $this->insert();
        }

        return $this->updateAll($data, "user_id='{$data['user_id']}'");
    }

    /**
     * 读取开关
     */
    public function getSwitches($userId, $field)
    {
        $ret = parent::findByViaSlave("user_id='{$userId}'", $field);
        return isset($ret[$field]) ? json_decode($ret[$field], true) : array();
    }

}
