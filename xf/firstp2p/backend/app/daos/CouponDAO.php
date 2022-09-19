<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;

use NCFGroup\Ptp\models\Firstp2pCouponLevelRebate;

class CouponDAO
{
    /**
     * getCouponLevelPrefixByLevelId
     * 获取优惠等级下的所有优惠码前缀
     *
     * @param mixed $leveId
     * @static
     * @access public
     * @return void
     */
    public static function getCouponLevelPrefixByLevelId($levelId)
    {
        $ret = array();
        $res = Firstp2pCouponLevelRebate::query()
            ->columns('prefix')
            ->where('levelId=:levelId: AND dealId=0 AND isEffect=1', array('levelId' => $levelId))
            ->execute();
        foreach ($res as $r) {
            $ret[] = $r->prefix;
        }
        return $ret;
    }
}
