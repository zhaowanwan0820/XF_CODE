<?php
/**
 * SupervisionWithdrawService 存管提现辅助函数
 */

namespace core\service;
use core\dao\SupervisionWithdrawModel;

class SupervisionWithdrawService extends BaseService {


    /**
     * 根据标的ID获取最新的借款人提现申请记录
     */
    public function getLatestByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if (empty($deal_id)) {
            return false;
        }
        $condition = " bid = '{$deal_id}' ORDER BY id DESC LIMIT 1";
        $item = SupervisionWithdrawModel::instance()->findBy($condition);
        return $item;
    }

    /**
     * 判断是否可以重新发起提现
     */
    public function canRedoWithdraw($withdraw) {
        if (!empty($withdraw['bid']) && $withdraw['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED) {
            $latest = $this->getLatestByDealId($withdraw['bid']);
            if (!empty($latest) && $latest['id'] == $withdraw['id']) {
                return true;
            }
        }
        return false;
    }

    public function getWithdrawByOrderId($outOrderId) {
        return SupervisionWithdrawModel::instance()->getWithdrawRecordByOutId($outOrderId);
    }


}
