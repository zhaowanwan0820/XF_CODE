<?php
namespace core\dao\supervision;

use libs\common\WXException;
use core\dao\BaseModel;

/**
 * 存管账户充值记录表
 **/
class BankLimitModel extends BaseModel {

    /**
     * 获取某银行简码的限额信息
     * @param string $payChannel 支付通道
     * @param string $bankCode 银行简码
     * @return boolean|array
     */
    public function getLimitByChannelCode($payChannel, $bankCode) {
        if (empty($payChannel) || empty($bankCode)) {
            return false;
        }
        return $this->findBy(sprintf("`pay_channel`='%s' AND `code`='%s'", $payChannel, $bankCode));
    }

    /**
     * 获取限额列表
     * @param string $payChannel 支付通道
     * @param string $bankCode 银行简码
     * @return boolean|array
     */
    public function getChargeLimitList($payChannel, $bankCode = '') {
        if (empty($payChannel)) {
            return false;
        }
        $condition = [];
        if (is_array($payChannel)) {
            $condition[] = sprintf("`pay_channel` IN ('%s')", join("','", $payChannel));
            !empty($bankCode) && $condition[] = sprintf("`code`='%s'", addslashes($bankCode));
            $condition = join(' AND ', $condition);
        } else {
            $condition = sprintf("`pay_channel`='%s' ORDER BY `id` ASC", $payChannel);
        }
        return $this->findAll($condition, true);
    }

    /**
     * 创建限额记录
     * @param string $payChannel 支付通道
     * @param string $bankCode 银行简码
     * @param string $subscriptionType 订阅类型(BCL-银行卡限额信息)
     * @param array $limitData 限额信息
     * @return boolean
     */
    public function createLimit($payChannel, $bankCode, $subscriptionType, $limitData) {
        if (empty($payChannel) || empty($bankCode)) {
            throw new WXException('ERR_PARAM');
        }

        $data = [];
        $data['pay_channel'] = addslashes(trim($payChannel));
        $data['code'] = addslashes(trim($bankCode));
        $data['type'] = addslashes(trim($subscriptionType));
        $data['name'] = isset($limitData['bankName']) ? addslashes(trim($limitData['bankName'])) : '';
        if (isset($limitData['minimumQuota'])) {
            $data['min_quota'] = (int)$limitData['minimumQuota'];
        }
        if (isset($limitData['maximumQuota'])) {
            $data['max_quota'] = (int)$limitData['maximumQuota'];
        }
        if (isset($limitData['dayQuota'])) {
            $data['day_quota'] = (int)$limitData['dayQuota'];
        }
        if (isset($limitData['monthQuota'])) {
            $data['month_quota'] = (int)$limitData['monthQuota'];
        }
        $data['limit_intro'] = !empty($limitData['limitIntro']) ? addslashes($limitData['limitIntro']) : '';
        $data['limit_json'] = !empty($limitData['limitJson']) ? addslashes($limitData['limitJson']) : '';
        $data['remark'] = !empty($limitData['remark']) ? addslashes($limitData['remark']) : '';
        $data['repair_start'] = !empty($limitData['repairStartTime']) ? strtotime($limitData['repairStartTime']) : 0;
        $data['repair_end'] = !empty($limitData['repairEndTime']) ? strtotime($limitData['repairEndTime']) : 0;
        $data['create_time'] = time();
        return $this->db->autoExecute($this->tableName(), $data, 'INSERT');
    }

    /**
     * 更新限额记录
     * @param string $payChannel 支付通道
     * @param string $bankCode 银行简码
     * @param array $updateData 限额信息
     * @throws WXException
     * @return boolean
     */
    public function updateLimitByChannelCode($payChannel, $bankCode, $updateData) {
        if (empty($payChannel) || empty($bankCode) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }

        $data = [];
        $data['update_time'] = time();
        if (!empty($updateData['bankName'])) {
            $data['name'] = addslashes(trim($updateData['bankName']));
        }
        if (!empty($updateData['bankCode'])) {
            $data['code'] = addslashes(trim($updateData['bankCode']));
        }
        if (isset($updateData['minimumQuota'])) {
            $data['min_quota'] = intval($updateData['minimumQuota']);
        }
        if (isset($updateData['maximumQuota'])) {
            $data['max_quota'] = intval($updateData['maximumQuota']);
        }
        if (isset($updateData['dayQuota'])) {
            $data['day_quota'] = intval($updateData['dayQuota']);
        }
        if (isset($updateData['monthQuota'])) {
            $data['month_quota'] = intval($updateData['monthQuota']);
        }
        if (isset($updateData['repairStartTime'])) {
            $data['repair_start'] = !empty($updateData['repairStartTime']) ? strtotime($updateData['repairStartTime']) : 0;
        }
        if (isset($updateData['repairEndTime'])) {
            $data['repair_end'] = !empty($updateData['repairEndTime']) ? strtotime($updateData['repairEndTime']) : 0;
        }
        if (isset($updateData['status'])) {
            $data['status'] = !empty($updateData['status']) ? intval($updateData['status']) : 0;
        }
        if (isset($updateData['limitIntro'])) {
            $data['limit_intro'] = !empty($updateData['limitIntro']) ? addslashes($updateData['limitIntro']) : '';
        }
        if (isset($updateData['limitJson'])) {
            $data['limit_json'] = !empty($updateData['limitJson']) ? addslashes($updateData['limitJson']) : '';
        }
        if (isset($updateData['remark'])) {
            $data['remark'] = !empty($updateData['remark']) ? addslashes($updateData['remark']) : '';
        }
        $this->db->autoExecute($this->tableName(), $data, 'UPDATE', sprintf("`pay_channel`='%s' AND `code`='%s'", $payChannel, $bankCode));
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过id更新限额记录
     * @param int $id 自增id
     * @param array $updateData 限额信息
     * @throws WXException
     * @return boolean
     */
    public function updateLimitById($id, $updateData) {
        if (empty($id) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }

        $data = [];
        $data['update_time'] = time();
        if (!empty($updateData['bankName'])) {
            $data['name'] = addslashes(trim($updateData['bankName']));
        }
        if (!empty($updateData['bankCode'])) {
            $data['code'] = addslashes(trim($updateData['bankCode']));
        }
        if (isset($updateData['minimumQuota'])) {
            $data['min_quota'] = intval($updateData['minimumQuota']);
        }
        if (isset($updateData['maximumQuota'])) {
            $data['max_quota'] = intval($updateData['maximumQuota']);
        }
        if (isset($updateData['dayQuota'])) {
            $data['day_quota'] = intval($updateData['dayQuota']);
        }
        if (isset($updateData['monthQuota'])) {
            $data['month_quota'] = intval($updateData['monthQuota']);
        }
        if (isset($updateData['repairStartTime'])) {
            $data['repair_start'] = !empty($updateData['repairStartTime']) ? strtotime($updateData['repairStartTime']) : 0;
        }
        if (isset($updateData['repairEndTime'])) {
            $data['repair_end'] = !empty($updateData['repairEndTime']) ? strtotime($updateData['repairEndTime']) : 0;
        }
        if (isset($updateData['status'])) {
            $data['status'] = !empty($updateData['status']) ? intval($updateData['status']) : 0;
        }
        if (isset($updateData['limitIntro'])) {
            $data['limit_intro'] = !empty($updateData['limitIntro']) ? addslashes($updateData['limitIntro']) : '';
        }
        if (isset($updateData['limitJson'])) {
            $data['limit_json'] = !empty($updateData['limitJson']) ? addslashes($updateData['limitJson']) : '';
        }
        if (isset($updateData['remark'])) {
            $data['remark'] = !empty($updateData['remark']) ? addslashes($updateData['remark']) : '';
        }
        $this->db->autoExecute($this->tableName(), $data, 'UPDATE', sprintf("`id`='%d'", $id));
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 删除限额数据
     * @param int $id
     * @throws WXException
     */
    public function delLimit($id) {
        if (empty($id)) {
            return false;
        }

        $limitInfo = $this->find($id, 'id,pay_channel,code,name', true);
        if (empty($limitInfo)) {
            return false;
        }
        return $limitInfo->remove();
    }
}