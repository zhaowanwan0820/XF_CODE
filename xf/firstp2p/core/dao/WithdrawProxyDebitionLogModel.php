<?php
namespace core\dao;

class WithdrawProxyDebitionLogModel extends BaseModel
{
    // 创建债权关系
    const TYPE_CREATE = 1;
    // 回款
    const TYPE_REPAY = 2;
    // 置为无效
    const TYPE_DISABLED = 3;

    static $typeDesc = array(
        self::TYPE_CREATE => '新增',
        self::TYPE_REPAY => '回款',
        self::TYPE_DISABLED => '置为无效',
    );

}
