<?php
/**
 * UserGrowthModel Class
 * @author longbo
 *
 */
namespace core\dao;
use libs\db\MysqlDb;

class UserGrowthModel extends BaseModel
{

    public function getUserGrowth ($user_id)
    {
        $this->db = MysqlDb::getInstance('itil');
        $condition = 'user_id = :user_id';
        $params = array(':user_id' => $user_id);
        $res = parent::findBy($condition, "*", $params, false);
        if ($res) {
            return $res->getRow();
        }
        return false;
    }
}
