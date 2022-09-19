<?php
/**
 * DealGroupModel class file.
 *
 * @author 杨庆 <yangqing@ucfgroup.com>
 **/
namespace core\dao;

use core\dao\UserModel;

/**
 * 标的用户组信息
 */
class DealGroupModel extends BaseModel
{
    /**
     * 获取标的分组列表
     *
     * @param type $deal_id
     * @return boolean
     */
    public function getListByDeal ($deal_id)
    {
        if (intval($deal_id) != 0) {
            $sql = "SELECT * FROM " . $this->tableName() . " WHERE  deal_id = %d";
            $sql = sprintf($sql, $this->escape($deal_id));
            $res = $this->findAllBySql($sql, false, array(), true);
            return $res;
        } else {
            return false;
        }
        return false;
    }

    /**
     * 检查用户 是否可以投资 特定用户组的标
     *
     * @param $deal_id 借款id
     * @param $user_id 用户id
     * @return boolean
     */
    public function checkUserDealGroup($deal_id, $user_id){
        $user_info = UserModel::instance()->find(intval($user_id));
        $group_list = DealGroupModel::instance()->getListByDeal(intval($deal_id));

        if(empty($group_list) || empty($user_info)){
            return false;
        }
        foreach ($group_list as $key => $value) {
            if($value['user_group_id'] == $user_info['group_id']){
                return true;
            }
        }
        return false;
    }
}
