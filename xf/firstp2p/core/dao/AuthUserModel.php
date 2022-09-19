<?php
/**
 * 第三方网贷平台用户Model
 * @author longbo
 */

namespace core\dao;
use libs\db\MysqlDb;

class AuthUserModel extends BaseModel
{

    public function __construct()
    {
        parent::__construct();
        $this->db = MysqlDb::getInstance('firstp2p_thirdparty');
    }


    public function isExist($clientId = '', $userId = '')
    {
        if (!$clientId || !$userId) {
            return false;
        }
        $condition = 'client_id = ":client_id" AND user_id = :user_id';
        $params = array(
                ':client_id' => strval($clientId),
                ':user_id' => intval($userId),
                );
        $count = $this->count($condition, $params);
        return $count > 0;
    }

}
