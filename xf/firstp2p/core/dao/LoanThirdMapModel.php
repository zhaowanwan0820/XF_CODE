<?php
/**
 * 享花等第三方标的订单号关系表
 * @author guofeng3<guofeng3@ucfgroup.com>
 */

namespace core\dao;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;

class LoanThirdMapModel extends BaseModel {
    /**
     * 创建标的订单号关系记录-不会重复添加
     * @param int $userId
     * @param int $dealId
     * @param int $repayId
     * @param int $repayType
     * @return bool
     */
    public function addNxLoanThirdMap($userId, $dealId, $repayId, $repayType) {
        if (empty($userId) || empty($dealId) || (int)$repayId < 0 || empty($repayType)) {
            return false;
        }
        // 检查标的订单号关系记录是否存在
        $loanThirdMap = $this->getLoanThirdMap($userId, $dealId, $repayId, $repayType);
        if (!empty($loanThirdMap)) {
            return $loanThirdMap['out_order_id'];
        }

        // 创建标的订单号关系记录
        return $this->createLoanThirdMap($userId, $dealId, $repayId, $repayType);
    }

    /**
     * 创建标的订单号关系记录
     * @param int $userId
     * @param int $dealId
     * @param int $repayId
     * @param int $repayType
     * @return bool
     */
    public function createLoanThirdMap($userId, $dealId, $repayId, $repayType) {
        if (empty($userId) || empty($dealId) || (int)$repayId < 0 || empty($repayType)) {
            return false;
        }
        try{
            $orderId = Idworker::instance()->getId();
            $this->user_id = (int)$userId;
            $this->deal_id = (int)$dealId;
            $this->repay_id = (int)$repayId;
            $this->out_order_id = (int)$orderId;
            $this->create_time = time();
            $this->repay_type = (int)$repayType;
            $ret = $this->insert();
            return $ret ? $orderId : false;
        }catch (\Exception $e) {
            Logger::error('LoanThirdMapModel::createLoanThirdMap, 获取Idworker异常, errMsg:' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询标的订单号关系记录
     * @param int $orderId 订单ID
     * @return array
     */
    public function getLoanThirdMapByOrderId($orderId) {
        if (empty($orderId)) {
            return [];
        }
        return $this->findBy("`out_order_id`=':out_order_id'", '*', [':out_order_id'=>(int)$orderId]);
    }

    /**
     * 查询标的订单号关系记录
     * @param int $userId 用户ID
     * @param int $dealId 标的ID
     * @param int $repayId 回款记录ID
     * @param int $repayType 还款类型
     * @return array
     */
    public function getLoanThirdMap($userId, $dealId, $repayId, $repayType) {
        if (empty($userId) || empty($dealId) || (int)$repayId < 0 || empty($repayType)) {
            return [];
        }

        $param = array(
            ':user_id' => (int)$userId,
            ':deal_id' => (int)$dealId,
            ':repay_id' => (int)$repayId,
            ':repay_type' => (int)$repayType,
        );
        return $this->findBy('`user_id`=:user_id AND `deal_id`=:deal_id AND `repay_id`=:repay_id AND `repay_type`=:repay_type', '*', $param);
    }
}