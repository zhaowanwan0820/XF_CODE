<?php
/**
 * DealGroupService class file.
 *
 * @author 杨庆<yangqing@ucfgroup.com>
 **/

namespace core\service;

use core\dao\DealGroupModel;

/**
 * DealGroupService
 */
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
     * @return boolean
     */
    public function checkUserDealGroup($deal_id, $user_id){
        return DealGroupModel::instance()->checkUserDealGroup($deal_id, $user_id);
    }
}
