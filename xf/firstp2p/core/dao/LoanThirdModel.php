<?php
/**
 * 享花等第三方还款申请记录表
 * @author guofeng3<guofeng3@ucfgroup.com>
 */

namespace core\dao;

class LoanThirdModel extends BaseModel {
    const STATUS_APPLY = 0; // 未处理
    const STATUS_ACCEPT = 1; // 已受理
    const STATUS_SUCCESS = 2; // 划扣成功
    const STATUS_FAIL  = 3; // 划扣失败

    const TYPE_PART = 0; // 不是部分还款
    const TYPE_WHOLE = 1; // 是部分还款

    /**
     * 创建还款申请记录
     * @param array $params
     * @return bool
     */
    public function createLoanThird($params) {
        if (empty($params['user_id']) || empty($params['deal_id']) || (int)$params['repay_id'] < 0) {
            return false;
        }

        foreach ($params as $key => $value) {
            !empty($value) && $this->{$key} = addslashes($value);
        }
        !isset($this->create_time) && $this->create_time = time();
        return $this->insert();
    }

    /**
     * 更新还款申请记录
     * @param int $userId 用户ID
     * @param int $dealId 标的ID
     * @param int $repayId 回款记录ID
     * @param int $repayType 还款类型
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateLoanThird($userId, $dealId, $repayId, $repayType, $data) {
        if (empty($userId) || empty($dealId) || (int)$repayId < 0 || empty($repayType)
            || empty($data)) {
            return false;
        }

        $updateParams = [];
        !empty($data['out_order_id']) && $updateParams['out_order_id'] = addslashes($data['out_order_id']);
        isset($data['repay_type']) && $updateParams['repay_type'] = (int)$data['repay_type'];
        isset($data['type']) && $updateParams['type'] = (int)$data['type'];
        !empty($data['bankcard']) && $updateParams['bankcard'] = addslashes($data['bankcard']);
        !empty($data['repay_money']) && $updateParams['repay_money'] = addslashes($data['repay_money']); // 申请还款的金额
        !empty($data['total_money']) && $updateParams['total_money'] = addslashes($data['total_money']); // 标的回款的金额
        !empty($data['service_fee']) && $updateParams['service_fee'] = addslashes($data['service_fee']); // 业务服务费
        isset($data['status']) && $updateParams['status'] = (int)$data['status']; // 状态(0:申请中 1:划款已受理 2:划款成功 3:划款失败)
        !empty($data['loan_time']) && $updateParams['loan_time'] = (int)$data['loan_time']; // 银行划款回调时间
        $updateParams['update_time'] = time();
        $this->updateBy(
            $updateParams,
            sprintf('`user_id`=%d AND `deal_id`=%d AND `repay_id`=%d AND `repay_type`=%d AND `status` NOT IN(%s)', $userId, $dealId, $repayId, $repayType, join(',', [self::STATUS_SUCCESS, self::STATUS_FAIL]))
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 更新还款申请记录
     * @param int $userId 用户ID
     * @param int $dealId 标的ID
     * @param int $repayId 回款记录ID
     * @param int $repayType 还款类型
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateLoanThirdByOrderId($userId, $orderId, $data) {
        if (empty($userId) || empty($orderId) || empty($data)) {
            return false;
        }

        $updateParams = [];
        isset($data['repay_type']) && $updateParams['repay_type'] = (int)$data['repay_type'];
        isset($data['type']) && $updateParams['type'] = (int)$data['type'];
        !empty($data['bankcard']) && $updateParams['bankcard'] = addslashes($data['bankcard']);
        !empty($data['repay_money']) && $updateParams['repay_money'] = addslashes($data['repay_money']); // 申请还款的金额
        !empty($data['total_money']) && $updateParams['total_money'] = addslashes($data['total_money']); // 标的回款的金额
        !empty($data['service_fee']) && $updateParams['service_fee'] = addslashes($data['service_fee']); // 业务服务费
        isset($data['status']) && $updateParams['status'] = (int)$data['status']; // 状态(0:申请中 1:划款已受理 2:划款成功 3:划款失败)
        !empty($data['loan_time']) && $updateParams['loan_time'] = (int)$data['loan_time']; // 银行划款回调时间
        $updateParams['update_time'] = time();
        $this->updateBy(
            $updateParams,
            sprintf('`user_id`=%d AND `out_order_id`=%d AND `status` NOT IN(%s)', $userId, $orderId, join(',', [self::STATUS_SUCCESS, self::STATUS_FAIL]))
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 查询还款申请记录
     * @param int $userId 用户ID
     * @param int $dealId 标的ID
     * @param int $repayId 回款记录ID
     * @param int $repayType 还款类型
     * @return array
     */
    public function getLoanThird($userId, $dealId, $repayId, $repayType) {
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

    /**
     * 查询有效的还款申请记录
     * @param int $userId 用户ID
     * @param int $dealId 标的ID
     * @param int $loadId 投资记录ID
     * @param int $repayId 回款记录ID
     * @return array
     */
    public function getValidLoanThird($userId, $dealId, $repayId, $repayType) {
        $param = array(
            ':user_id' => (int)$userId,
            ':deal_id' => (int)$dealId,
            ':repay_id' => (int)$repayId,
            ':repay_type' => (int)$repayType,
            ':status' => join(', ', [self::STATUS_SUCCESS, self::STATUS_FAIL]),
        );
        return $this->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `repay_id` = ':repay_id' AND `repay_type` = ':repay_type' AND `status` NOT IN(:status)", '*', $param);
    }

    /**
     * 通过订单号，查询还款申请记录
     * @param int $orderId
     * @param boolean $isStatus
     * @return array
     */
    public function getLoanThirdByOrderId($orderId, $isStatus=false) {
        $param = array(
            ':out_order_id' => addslashes($orderId),
        );
        $condition = "`out_order_id` = ':out_order_id'";
        if ($isStatus) {
            $param[':status'] = join(', ', [self::STATUS_SUCCESS, self::STATUS_FAIL]);
            $condition = 'AND `status` NOT IN(:status)';
        }
        return $this->findBy($condition, '*', $param);
    }

    /**
     * 查询用户是否有【享花还款】记录
     * @param int $userId
     * @return \libs\db\model
     */
    public function hasExistByUserId($userId) {
        $data = $this->findByViaSlave("user_id=':user_id' LIMIT 1", 'id', array(':user_id'=>(int)$userId));
        return !empty($data['id']) ? true : false;
    }
}