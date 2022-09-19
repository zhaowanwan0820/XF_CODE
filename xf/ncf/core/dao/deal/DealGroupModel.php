<?php

namespace core\dao\deal;


use core\dao\BaseModel;
use core\dao\deal\DealModel;
use core\service\user\UserService;
use core\service\coupon\CouponService;


class DealGroupModel extends BaseModel {

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
     * @param $user_id 用户id 如果$user_group_id 为零该参数为必传
     * @param int $user_group_id 大于零不再查询用户信息
     * @return boolean
     */
    public function checkUserDealGroup($deal_id, $user_id,$user_group_id = 0){

        if (empty($user_group_id)){
            $user_info = UserService::getUserById(intval($user_id),'group_id');
        }else{
            $user_info['group_id'] = $user_group_id;
        }

        $group_list = DealGroupModel::instance()->getListByDeal(intval($deal_id));

        if(empty($group_list) || empty($user_info['group_id'])){
            return false;
        }

        $groupRelation   = ['4' => 0, '5' => 0, '6' => 0, '7' => 0]; //组内关系
        $inviteRelation  = ['2' => 0, '3' => 0, '6' => 0, '7' => 0]; //邀请关系
        $serviceRelation = ['1' => 0, '3' => 0, '5' => 0, '7' => 0]; //服务关系

        $group_keys = [];
        $relation = 0;
        foreach ($group_list as $item) {
            $group_keys[$item['user_group_id']] = $item['user_group_id'];
            $relation = $item['relation'];
        }

        if (isset($groupRelation[$relation])) {
            if (isset($group_keys[$user_info['group_id']])) {
                return true;
            }
        }

        if (isset($inviteRelation[$relation]) || isset($serviceRelation[$relation])) {
            $inviteServiceRelation = CouponService::getByUserId($user_id);
            if (isset($inviteRelation[$relation]) && $inviteServiceRelation['invite_user_id'] > 0) {
                $user_info = UserService::getUserById(intval($inviteServiceRelation['invite_user_id']), 'group_id');
                if (isset($group_keys[$user_info['group_id']])) {
                    return true;
                }
            }

            if (isset($serviceRelation[$relation]) && $inviteServiceRelation['refer_user_id'] > 0) {
                $user_info = UserService::getUserById(intval($inviteServiceRelation['refer_user_id']), 'group_id');
                if (isset($group_keys[$user_info['group_id']])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function saveDealGroup($dealId, $groupIds, $relation)
    {
        $cnt = $this->count("deal_id='$dealId'");
        $cnt = intval($cnt);
        $dealId = intval($dealId);
        if ($cnt > 0) {
            $result = $this->updateRows("delete from firstp2p_deal_group where deal_id='$dealId' LIMIT $cnt");
        }

        if (empty($groupIds)) {
            return true;
        }

        $valuesArr = [];
        $sql = 'INSERT INTO firstp2p_deal_group (deal_id, user_group_id, relation) VALUES';
        $value = '("%s", "%s", "%s")';
        $createTime = $updateTime = time();
        foreach ($groupIds as $groupId) {
            if ($groupId > 0) {
                $valuesArr[] = sprintf($value, $dealId, $groupId , $relation);
            }
        }
        if (empty($valuesArr)) {
            return true;
        }
        $valuesStr = implode(',', $valuesArr);
        $result =  $this->updateRows($sql.$valuesStr);
        return $result;
    }
}
