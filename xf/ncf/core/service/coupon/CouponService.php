<?php

namespace core\service\coupon;

use core\service\BaseService;
use core\enum\CouponEnum;

/**
 * 优惠码相关接口.
 */
class CouponService extends BaseService
{
    // consume，redeem 智多鑫也需要用，所有不能用默认type
    private static $funcMap = array(
        'saveCouponDeal' => array('dealId', 'rebateDays', 'payType', 'payAuto'), // 获取优惠码标的设置
        'getCouponDealByDealId' => array('dealId'), // 保存优惠码标的设置
        'queryCoupon' => array('shortAlias', 'isFinancePassedNeeded'), // 检查优惠码
        'getCouponLatest' => array('consumeUserId'), // 获取用户绑定优惠码
        'consume' => array('type', 'coupon', 'money', 'userId', 'dealId', 'dealLoadId', 'repayStartTime', 'siteId', 'amount', 'price'), //消费优惠码
        'getUserFriendCount' => array('userId'),
        'redeem' => array('type', 'dealLoadId', 'dealRepayTime'),//赎回
        'getOneUserCoupon' => array('userId'),//获取用自己的一个邀请码
        'checkCoupon' => array('shortAlias'),
        'isShowCoupon' => array('dealId'),
        'getByUserId' => array('userId','shortAlias'),//通过userId获取一条优惠码绑定记录.
    );

    private static $defaultParam = array(
        'getCouponDealByDealId',
        'saveCouponDeal',
        'queryCoupon',
        'checkCoupon',
        'getCouponLatest',
    );

    /**
     * 邀请码链接存储短码的cookie的key.
     */
    const LINK_COUPON_KEY = 'link_coupon';

    /**
     * Handles calls to static methods.
     *
     * @param string $name   Method name
     * @param array  $params Method parameters
     *
     * @return mixed
     */
    public static function __callStatic($name, $params)
    {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];

        foreach ($params as $key => $arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        if (in_array($name, self::$defaultParam)) {
            $args['type'] = CouponEnum::TYPE;
        }
        return self::rpc('ncfwx', 'coupon/'.$name, $args,false,5);
    }

    /**
     * 根据传入的字段返回用于返利的邀请码字.
     *
     * @param $str 邀请码，可能是字符串，也可能是电话
     *
     * @return 返回字符串的邀请码,形式如FA0FA
     */
    public static function getShortAliasFormMobilOrAlias($str)
    {
        if (empty($str)) {
            return false;
        }
        $str = addslashes($str);
        $rule = array('options' => array('regexp' => '/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/'));
        $mobileValide = filter_var($str, FILTER_VALIDATE_REGEXP, $rule);
        if (false === $mobileValide) {
            return array('type' => 'alias', 'alias' => $str, 'userName' => 'null');
        } else {
            return false;
        }
    }
}