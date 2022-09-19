<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class O2oEnum extends AbstractEnum {

    /**
    * 触发类型-1:预约结束累计投资额
    * @var int
    */
    const TYPE_ACCUMULATE  = 1;

    /**
    * 触发类型-2:单笔内单次投资成功
    * @var int
    */
    const TYPE_SINGLE  = 2;

    /**
     * 状态-0:无效
     * @var int
     */
    const STATUS_UNVALID = 0;

    /**
    * 状态-1:有效
    * @var int
    */
    const STATUS_VALID = 1;

    /**
     * 礼品类型-1:礼券2:投资券
     * @var int
     */
    const GIFT_TYPE_COUPON = 1;
    const GIFT_TYPE_DISCOUNT = 2;

    /**
     * 礼品类型的单位配置(1:礼券2:投资券)
     * @var array
     */
    public static $giftTypeConfig = array(
        self::GIFT_TYPE_COUPON => '礼券',
        self::GIFT_TYPE_DISCOUNT => '投资券',
    );

}
