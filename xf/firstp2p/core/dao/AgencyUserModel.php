<?php
/**
 * AgencyUserModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

/**
 * 担保公司用户
 *
 * @author wenyanlei@ucfgroup.com
 **/
class AgencyUserModel extends BaseModel{

    /**
     * 获取担保公司某个用户
     * @param unknown $agency_id
     * @param unknown $user_name
     * @param unknown $user_id
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    public function getAgencyUsers($agency_id, $user_name, $user_id) {
        $condition = "agency_id=':agency_id' AND user_name=':user_name' AND user_id=':user_id'";
        $params = array(':agency_id' => $agency_id, ':user_name' => $user_name, ':user_id' => $user_id);
        return $this->findBy($condition, '*', $params, true);
    }

    /**
     * 根据用户获取担保公司
     * @param unknown $user_id
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    public function getAgencyByUser($user_id) {
        $condition = "user_id=:user_id";
        return $this->findBy($condition, '*', array(':user_id' => $user_id));
    }

    /**
     * 获取某个用户所属机构的信息
     * @param $user_id 用户id
     * @param $type 机构类型 1：担保 2：咨询 3:委托
     * @return array
     */
    public function getAgencyInfoByUserId($user_id, $type = 1){
        $sql = "select au.*,a.type from ".DB_PREFIX."deal_agency as a left join ".DB_PREFIX."agency_user as au
                on a.id = au.agency_id where au.user_id = ':user_id' and a.type = ':type' order by a.id desc limit 1";
        $params = array(':user_id' => intval($user_id), ':type' => intval($type));
        return $this->findBySql($sql, $params, true);
    }

    /**
     * 获取某个担保公司所有用户
     * @param unknown $agency_id
     */
    public function getAllUserByAgencyId($agency_id) {
        $condition = "agency_id=:agency_id";
        return $this->findAll($condition, true, '*', array(':agency_id' => $agency_id));
    }

} // END class AgencyUserModel extends BaseModel
