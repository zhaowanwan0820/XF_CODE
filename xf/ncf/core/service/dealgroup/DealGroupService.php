<?php
/**
 *  标的分组
 *
 **/

namespace core\service\dealgroup;

use core\service\BaseService;

use core\dao\deal\DealGroupModel;


class DealGroupService extends BaseService {


    public function getListByDeal($deal_id) {
        $result = DealGroupModel::instance()->getListByDeal($deal_id);
        return $result;
    }

    /**
     * 检查用户 是否可以投资 特定用户组的标
     *
     * @param $deal_id 借款id
     * @param $user_id 用户id
     * @param int $user_group_id
     * @return boolean
     */
    public function checkUserDealGroup($deal_id, $user_id,$user_group_id = 0){
        return DealGroupModel::instance()->checkUserDealGroup($deal_id, $user_id,$user_group_id);
    }
}
