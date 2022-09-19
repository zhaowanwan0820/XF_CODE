<?php
/**
 * AgencyContractModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

/**
 * 合同签署表
 *
 * @author wenyanlei@ucfgroup.com
 **/
class AgencyContractModel extends BaseModel
{
    /**
     * 检查是否有合同签署通过的记录
     * @param int $contract_id
     * @param int $user_id
     * @return array
     */
    public function getAgencyContractByUser($contract_id, $user_id) {
        //用户是否签署过该合同
        $condition = "`contract_id` =':contract_id' AND `user_id`=':user_id' AND `pass`=1";
        return $this->findBy($condition, '*', array(':contract_id' => $contract_id, ':user_id' => $user_id), true);
    }

    /**
     * 获取某个标 某个角色的签署数量
     * @param unknown $deal_id
     * @param number $is_agency
     * @return Ambigous <number, string, boolean>
     */
    public function getContSignNumByDeal($deal_id, $is_agency = 0, $agency_id = null){
        $condition = "`deal_id`=':deal_id' AND `agency_id` = 0";
        if($is_agency){
            $condition = "`deal_id`=':deal_id' AND `agency_id`=':agency_id'";
        }
        return $this->countViaSlave($condition, array(':deal_id' => $deal_id,':agency_id' => $agency_id));
    }

    /**
     * 获取某个标已签署数量（整个标的）
     * @param unknown $deal_id
     * @param number $is_agency
     * @return Ambigous <number, string, boolean>
     */
    public function getContSignAllNumByDeal($deal_id){
        $condition = "`deal_id`=':deal_id' AND `pass` = 1";
        return $this->countViaSlave($condition, array(':deal_id' => $deal_id));
    }

    /**
     * 删除一个标的合同签署信息
     * @param unknown $deal_id
     * @return boolean
     */
    public function delSignByDealId($deal_id){
        $sql = sprintf("DELETE FROM %s WHERE `deal_id` = %d", self::tableName(), $this->escape($deal_id));
        return $this->execute($sql);
    }
} // END class AgencyContractModel extends BaseModel
