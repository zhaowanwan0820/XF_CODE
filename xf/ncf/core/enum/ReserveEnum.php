<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use core\enum\DealEnum;

class ReserveEnum extends AbstractEnum {

    /**
     * 预约状态(0:预约中1:预约结束)
     * @var int
     */
    const RESERVE_STATUS_ING = 0;
    const RESERVE_STATUS_END = 1;

    /**
     * 投资期限的单位(1:天2:月)
     * @var int
     */
    const INVEST_DEADLINE_UNIT_DAY = 1;
    const INVEST_DEADLINE_UNIT_MONTH = 2;

    //每月的天数
    const DAYS_OF_MONTH = 30;

    /**
     * 投资期限的单位配置(1:天2:月)
     * @var array
     */
    public static $investDeadLineUnitConfig = array(
        self::INVEST_DEADLINE_UNIT_DAY => '天',
        self::INVEST_DEADLINE_UNIT_MONTH => '月',
    );

    /**
     * 预约有效期的单位(1:小时2:天)
     * @var int
     */
    const EXPIRE_UNIT_HOUR = 1;
    const EXPIRE_UNIT_DAY = 2;

    /**
     * 预约有效期的单位配置(1:小时2:天)
     * @var array
     */
    public static $expireUnitConfig = array(
        self::EXPIRE_UNIT_HOUR => '小时',
        self::EXPIRE_UNIT_DAY => '天',
    );

    /**
     * 预约来源(1:APP2:Wap3:Admin)
     * @var int
     */
    const RESERVE_REFERER_APP = 1;
    const RESERVE_REFERER_WAP = 2;
    const RESERVE_REFERER_ADMIN = 3;

    /**
     * 预约来源名称配置
     * @var array
     */
    public static $reserveRefererConfig = array(
        self::RESERVE_REFERER_APP => 'APP',
        self::RESERVE_REFERER_WAP => 'M',
        self::RESERVE_REFERER_ADMIN => 'ADMIN',
    );

    /**
     * 处理状态(0:默认1:处理中)
     * @var int
     */
    const PROC_STATUS_NORMAL = 0;
    const PROC_STATUS_ING = 1;

    /**
     * 投资券使用状态(1:预约使用中 2:已使用 3:未使用)
     * @var int
     */
    const DISCOUNT_STATUS_DEFAULT = 0;//默认
    const DISCOUNT_STATUS_PROCESSING = 1;//预约使用中
    const DISCOUNT_STATUS_SUCCESS = 2;//已使用
    const DISCOUNT_STATUS_FAILED = 3;//未使用

    /**
     * 投资券使用状态映射
     * @var array
     */
    public static $discountStatusMap = [
        self::DISCOUNT_STATUS_DEFAULT => '默认',
        self::DISCOUNT_STATUS_PROCESSING => '预约使用中',
        self::DISCOUNT_STATUS_SUCCESS => '已使用',
        self::DISCOUNT_STATUS_FAILED => '未使用',
    ];

    //随心约借款类型
    public static $reserveDealTypeList = [DealEnum::DEAL_TYPE_GENERAL];

}
