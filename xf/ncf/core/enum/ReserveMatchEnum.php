<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ReserveMatchEnum extends AbstractEnum {

    /**
     * 预约服务启动类型(1:优先预约2:人工投资+预约)
     * @var int
     */
    const RESERVE_TYPE_DEFAULT_RESERVING = 1;
    const RESERVE_TYPE_DELAY_RESERVE = 2;

    /**
     * 预约服务启动类型配置
     * @var array
     */
    public static $reserveTypeConfig = array(
        self::RESERVE_TYPE_DEFAULT_RESERVING => '优先预约投资',
        self::RESERVE_TYPE_DELAY_RESERVE => '人工直接投资+预约投资',
    );

    /**
     * 是否有效(0:无效1:有效)
     * @var int
     */
    const IS_EFFECT_INVALID = 0;
    const IS_EFFECT_VALID = 1;

    /**
     * TAG名称-预约标匹配-优先预约
     * @var string
     */
    const TAGNAME_RESERVATION_1 = 'RESERVATION_MATCH_1';

    /**
     * TAG名称-预约标匹配-人工投资+预约
     * @var string
     */
    const TAGNAME_RESERVATION_2 = 'RESERVATION_MATCH_2';

    /**
     * TAG名称集合
     */
    public static $tagNameList = array(
        self::TAGNAME_RESERVATION_1,
        self::TAGNAME_RESERVATION_2,
    );

}
