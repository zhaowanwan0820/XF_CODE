<?php
/**
 * FinanceAuditModel class file.
 * @author pengchanglu
 **/

namespace core\dao;

/**
 * FinanceAuditModel class
 * @author pengchanglu
 **/
class FinanceAuditModel extends BaseModel {

    const TYPE_TRANSFER = 1; // 转账
    const TYPE_COUPON = 2;  // 优惠码
    const TYPE_REGISTER = 3;  // 注册返利
    const TYPE_ENTERPRISE_TRANSFER = 4; // 企业管家平台转账


    const STATUS_REFUSED = -1; //已拒绝
    const STATUS_NEED_A = 1; // A角色待审核
    const STATUS_NEED_B = 2; // B角色待审核
    const STATUS_PASS = 3; // 审核通过
}
