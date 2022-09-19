<?php

namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;
use NCFGroup\Common\Extensions\Enum\EnumExceptionLevel;

class RPCErrorCode extends AbstractErrorCodeBase {

    //维护期间异常
    const MAINTENANCE_EXCEPTION = 800;
    //model保存异常
    const MODEL_SAVE_EXCEPTION = 900;
    const DUPLICATE_SUBMIT = 901;
    // 业务工程请从1000开始，[0, 1000) 保留给框架层。
    
    const MEDAL_NOT_EXIST = 1200;
    const MEDAL_USER_NOT_OWN = 1201;
    const MEDAL_USER_HAS_AWARDED = 1202;
    const MEDAL_NO_AWARD = 1203;
    const MEDAL_COUPON_ID_WRONG = 1204;
    const MEDAL_AWARD_EXPIRED = 1205;

    public static function initErrorInfo() {
        self::addToMapping(static::MAINTENANCE_EXCEPTION, array(EnumExceptionLevel::NONE, "系统正在维护"));

        self::addToMapping(static::MODEL_SAVE_EXCEPTION, array(EnumExceptionLevel::ERROR, "model save error！"));
        self::addToMapping(static::DUPLICATE_SUBMIT, array(EnumExceptionLevel::WARNING, "请求已经受理，请勿重复提交"));
        
        self::addToMapping(static::MEDAL_NOT_EXIST, array(EnumExceptionLevel::ERROR, "该勋章不存在"));
        
        self::addToMapping(static::MEDAL_USER_NOT_OWN, array(EnumExceptionLevel::ERROR, "用户没有获得该勋章，无法领奖"));

        self::addToMapping(static::MEDAL_USER_HAS_AWARDED, array(EnumExceptionLevel::ERROR, "用户已经领取过奖励"));
   
        self::addToMapping(static::MEDAL_NO_AWARD, array(EnumExceptionLevel::ERROR, "该勋章没有奖励"));

        self::addToMapping(static::MEDAL_COUPON_ID_WRONG, array(EnumExceptionLevel::ERROR, "传入错误的券ID"));

        self::addToMapping(static::MEDAL_AWARD_EXPIRED, array(EnumExceptionLevel::ERROR, "奖励已过期"));
    }

}
