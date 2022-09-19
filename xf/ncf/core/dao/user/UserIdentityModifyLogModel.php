<?php
/**
 * UserIdentityModifyLogModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao\user;
use core\dao\BaseModel;

/**
 * 实名变更日志
 *
 * @author weiwei12@ucfgroup.com
 */
class UserIdentityModifyLogModel extends BaseModel
{
    CONST STATUS_NORMAL = 0; //待处理
    CONST STATUS_SUCCESS = 1; //已通过
    CONST STATUS_FAILURE = 2; //已拒绝

    public static $statusMap = [
        self::STATUS_NORMAL => '待人工审核',
        self::STATUS_SUCCESS => '已通过',
        self::STATUS_FAILURE => '已拒绝',
    ];

    /**
     * 存在待审核日志
     */
    public function isPending($userId) {
        $result = $this->findBy(sprintf('`user_id`=:user_id AND status = %d', self::STATUS_NORMAL), '*', array(':user_id'=>intval($userId)), true);
        return $result ? true : false;
    }

    public function getLogByOrderId($orderId) {
        return $this->findBy('`order_id`=:order_id', '*', array(':order_id'=>intval($orderId)), true);
    }

    /**
     * 添加实名变更日志
     * @return bool
     */
    public function saveLog($params)
    {
        if (empty($params)) {
            return false;
        }

        //幂等
        if ($this->getLogByOrderId($params['order_id'])) {
            return true;
        }

        $params['idno'] = addslashes($params['idno']);
        $params['create_time'] = time();
        $params['status'] = self::STATUS_NORMAL;
        $this->setRow($params);

        return $this->insert();
    }

    /**
     * 添加实名变更日志
     * @return bool
     */
    public function updateLog($params) {
        if (empty($params)) {
            return false;
        }

        $condition = sprintf("`order_id` = '%s' and `status` = %d", $params['order_id'], self::STATUS_NORMAL);
        $params = array(
            'status'        => $params['status'],
            'fail_reason'   => $params['fail_reason'],
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }
}