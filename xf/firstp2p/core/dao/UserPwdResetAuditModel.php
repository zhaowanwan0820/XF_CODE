<?php
/**
 * UserPwdResetAuditModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao;

/**
 * 用户密码重置审核
 *
 * @author weiwei12@ucfgroup.com
 */
class UserPwdResetAuditModel extends BaseModel
{
    CONST STATUS_NORMAL = 0; //待审核
    CONST STATUS_SUCCESS = 1; //审核通过
    CONST STATUS_FAILURE = 2; //审核拒绝

    public static $statusMap = [
        self::STATUS_NORMAL => '未审核',
        self::STATUS_SUCCESS => '已通过',
        self::STATUS_FAILURE => '已拒绝',
    ];

    /**
     * 添加审核
     * @return bool
     */
    public function addAudit($params)
    {
        if (empty($params)) {
            return false;
        }

        $params['create_time'] = time();
        $params['apply_time'] = time();
        $params['status'] = self::STATUS_NORMAL;
        $this->setRow($params);

        return $this->insert();
    }

    /**
     * 确认审核
     * @return bool
     */
    public function confirmAudit($id, $updateParams) {
        if (empty($updateParams)) {
            return false;
        }

        $condition = sprintf("`id` = '%d' and `status` = %d", $id, self::STATUS_NORMAL);
        $updateParams['update_time'] = time();
        $updateParams['audit_time'] = time();
        $this->updateBy($updateParams, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

}
