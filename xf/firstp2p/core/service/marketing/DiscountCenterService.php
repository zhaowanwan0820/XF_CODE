<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class DiscountCenterService extends BaseService
{
    public static function updateUserVipGrade($userId, $grade)
    {
        $req = new SimpleRequestBase();
        $req->userId = $userId;
        $req->grade  = $grade;
        $res = self::requestMarketing('NCFGroup\Marketing\Services\DiscountCenter', 'updateVipGrade', $req);

        if ($res['errCode']) {
            return [];
        }
        return $res['data'];
    }

    public static function acquireSparowCoupon($mobile, $couponId, $eventId = 0)
    {
        $req = new SimpleRequestBase;
        $req->setParamArray([
            'mobile' => $mobile,
            'couponId' => $couponId,
            'eventId' => $eventId,
        ]);

        return self::requestMarketing('NCFGroup\Marketing\Services\AcquireLog', 'acquireSparowCoupon', $req);
    }

    public static function acquireSparowDiscount($mobile, $discountId, $eventId = 0)
    {
        $req = new SimpleRequestBase;
        $req->setParamArray([
            'mobile' => $mobile,
            'discountId' => $discountId,
            'eventId' => $eventId,
        ]);

        return self::requestMarketing('NCFGroup\Marketing\Services\AcquireLog', 'acquireSparowDiscount', $req);
    }

    public static function acquireSparowDiscountNoCheck($mobile, $discountId)
    {
        $req = new SimpleRequestBase;
        $req->setParamArray([
            'mobile' => $mobile,
            'discountId' => $discountId,
        ]);

        return self::requestMarketing('NCFGroup\Marketing\Services\AcquireLog', 'acquireSparowDiscountNoCheck', $req);
    }

    public static function replaceSparowDiscount($mobile, $discountId, $replacedDiscountId)
    {
        $req = new SimpleRequestBase;
        $req->setParamArray([
            'mobile' => $mobile,
            'discountId' => $discountId,
            'replacedDiscountId' => $replacedDiscountId,
        ]);

        return self::requestMarketing('NCFGroup\Marketing\Services\AcquireLog', 'replaceSparowDiscount', $req);
    }
}
