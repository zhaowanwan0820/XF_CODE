<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use core\enum\DealEnum;

class ReserveConfEnum extends AbstractEnum {

    /**
     * 投资期限配置json字段
     * @var array
     */
    public static $investFieldConfig = array(
        'deadline',
        'deadline_unit',
        'rate',
    );

    /**
    * 预约期限配置json字段
    * @var array
    */
    public static $expireFieldConfig = array(
        'expire',
        'expire_unit',
    );

    /**
     * 预约类型-1:公告
     * @var int
     */
    const TYPE_NOTICE = 1;

    /**
     * 预约类型-2:配置
     * @var int
     */
    const TYPE_CONF = 2; //废弃

    /**
     * 预约类型-3:期限
     * @var int
     */
    const TYPE_DEADLINE = 3; //废弃

    /**
     * 预约类型-4:网贷公告
     * @var int
     */
    const TYPE_NOTICE_P2P = 4;

    /**
     * 预约类型数组(1:公告2:配置)
     * @var array
     */
    public static $typeConfConfig = array(
        self::TYPE_NOTICE,
        self::TYPE_CONF,
        self::TYPE_DEADLINE,
        self::TYPE_NOTICE_P2P,
    );

    /**
     * 默认预约最低金额 100元
     */
    const RESERVE_MIN_AMOUNT_DEFAULT = 100;

    /**
     * 默认预约最大值 0没有限制
     */
    const RESERVE_MAX_AMOUNT_DEFAULT = 0;

    //最小投资额
    const RESERVE_P2P_MIN_LOAN_MONEY = 100;
    const RESERVE_EXCLUSIVE_MIN_LOAN_MONEY = 1000;
}
