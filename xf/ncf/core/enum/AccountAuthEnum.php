<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AccountAuthEnum extends AbstractEnum
{
    /**
     * 授权类型
     * @var int
     */
    const GRANT_TYPE_INVEST = 1; //免密投标
    const GRANT_TYPE_REPAY = 2; //免密还款
    const GRANT_TYPE_PAYMENT = 3; //免密缴费

    const GRANT_TYPE_SXY = 1; //随心约授权
    const GRANT_TYPE_ZDX = 2; //智多鑫授权
    const GRANT_TYPE_BORROW = 3; //借款授权

    //免密出借权限
    const GRANT_INVEST = 'INVEST';

    //免密还款授权
    const GRANT_REPAY = 'REPAY';

    //免密缴费授权
    const GRANT_SHARE_PAYMENT = 'SHARE_PAYMENT';

    //免密提现权限
    const GRANT_WITHDRAW = 'WITHDRAW';

    //免密提现至超级账户权限
    const GRANT_WITHDRAW_TO_SUPER = 'WITHDRAW_TO_SUPER';

    //免密提现至银信通账户权限
    const GRANT_WITHDRAW_TO_YXT = 'WITHDRAW_TO_YXT';

    //免密受托支付权限
    const GRANT_WITHDRAW_TO_ENTRUSTED = 'WITHDRAW_TO_ENTRUSTED';

    /**
     * 投资类型映射
     * @var array
     */
    public static $grantTypeMap = [
        self::GRANT_INVEST        => self::GRANT_TYPE_INVEST,
        self::GRANT_REPAY         => self::GRANT_TYPE_REPAY,
        self::GRANT_SHARE_PAYMENT => self::GRANT_TYPE_PAYMENT,
    ];

    /**
     * 投资类型映射
     * @var array
     */
    public static $grantTypeName = [
        self::GRANT_TYPE_INVEST => '免密投标授权',
        self::GRANT_TYPE_REPAY => '免密还款授权',
        self::GRANT_TYPE_PAYMENT => '免密缴费授权',
    ];

    const BIZ_TYPE_SXY = 1; //随心约授权
    const BIZ_TYPE_ZDX = 2; //智多鑫授权
    const BIZ_TYPE_BORROW = 3; //借款授权
}
