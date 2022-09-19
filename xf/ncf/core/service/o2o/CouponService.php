<?php

namespace core\service\o2o;

use core\service\BaseService;
use core\enum\CouponGroupEnum;

/**
 * 礼券相关接口
 */
class CouponService extends BaseService {
    private static $funcMap = array(
        'checkReservationRuleAndSendGift' => array('reserveId', 'dealId'),
        'getUserCouponList' => array('userId', 'status', 'page', 'pageSize'),
        'giftMineDetail' => array('couponId', 'userId', 'storeId', 'useRules', 'address_id'),
        'getUnpickList' => array('userId', 'page', 'pageSize', 'status'),
        'giftAcquireDetail' => array('couponGroupId', 'userId', 'moblie', 'action', 'loadId', 'addressId'),
        'giftAcquireExchange' => array(
            'userId', 'mobile', 'addressId', 'storeId', 'useRules',
            'couponGroupId', 'loadId', 'dealType', 'action'
        ),
        'getCouponGroupList' => array('userId', 'action', 'dealLoadId', 'consumeType'),
        'getCouponTriggerList' => array('userId', 'action', 'dealLoadId', 'consumeType'),
        'acquireCoupons' => array('userId', 'couponGroupIds', 'token', 'mobile', 'dealLoadId', 'isSyncResult', 'rebateAmount', 'rebateLimit'),
        'acquireDiscounts' => array('userId', 'discountGroupIds', 'token', 'dealLoadId', 'remark', 'isSyncResult', 'rebateAmount', 'rebateLimit'),
        'giftPickList' => array('userId', 'action', 'dealLoadId', 'dealType'),
        'getUserCouponCount' => array('userId', 'status'),
        'getUnpickCount' => array('userId', 'status'),
        'giftAcquireForm' => array('userId', 'couponId', 'couponGroupId', 'extraInfo'),
        'getExchangeForm' => array('storeId', 'useRules'),
        'giftExchangeCoupon' => array('userId', 'couponId', 'storeId', 'useRules', 'extraInfo'),
        'triggerO2OOrder' => array('userId', 'action', 'dealLoadId', 'siteId', 'money', 'annualizedAmount', 'consumeType', 'triggerType', 'extra'),
        'chargeTriggerO2O' =>  array('userId', 'action', 'orderId', 'money', 'siteId', 'withdrawTime'),
        'updateRankScoreByTrigger' => array('userId', 'bidAmount', 'annualizedAmount', 'dealLoadId', 'dealType', 'extra'),  //投资触发排行榜积分更新
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }

        $p2pInterface = array(
            'checkReservationRuleAndSendGift',
            'getCouponGroupList',
            'getCouponTriggerList',
            'triggerO2OOrder',
            'acquireCoupons',
            'acquireDiscounts',
            'chargeTriggerO2O',
        );

        $ncfphInterface = array(
            'giftMineDetail',
            'getUnpickList',
            'giftAcquireDetail',
            'giftAcquireExchange',
            'giftPickList',
            'getUnpickCount',
            'giftAcquireForm',
            'giftExchangeCoupon',
        );

        $rankInterface = array(
            'updateRankScoreByTrigger',
        );
        if (in_array($name, $p2pInterface)) {
            return self::rpc('ncfwx', 'o2ocoupon/'.$name, $args);
        } else if (in_array($name, $ncfphInterface)) {
            return self::rpc('ncfwx', 'ncfph/'.$name, $args, true);
        } else if (in_array($name, $rankInterface)) {
            return self::rpc('o2o', 'rank/'.$name, $args);
        } else {
            return self::rpc('o2o', 'coupon/'.$name, $args);
        }
    }

    public static function getFormatInfoWithGroupList($userId, $action, $dealLoadId, $token, $consumeType, $isWapCall = false) {
        // $prizeList = self::getCouponGroupList($userId, $action, $dealLoadId, $consumeType);
        $triggerList = self::getCouponTriggerList($userId, $action, $dealLoadId, $consumeType);
        $prizeList = $triggerList ? $triggerList['popup'] : array();
        $prizeType = '';
        $prizeTitle = '';
        $prizeUrl = '';
        $prizeDesc = '';
        if (!empty($prizeList)) {
            $title = urlencode('领取礼券');
            $prizeDesc = '您也可以在“礼券”中领取';
            if (count($prizeList) > 1) {
                // 多个券组
                $prizeType = 'o2o';
                $prizeTitle = '领取礼券';
                $url = urlencode(sprintf(
                    app_conf('O2O_DEAL_OPEN_URL'),
                    $action,
                    $dealLoadId,
                    $consumeType
                ));
                $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
            } else {
                // 单个礼券,根据使用规则封装url
                $prizeType = 'acquire';
                $groupInfo = array_pop($prizeList);
                $prizeTitle = $groupInfo['productName'];
                $groupId = $groupInfo['id'];
                $useRules = $groupInfo['useRules'];
                $storeId = $groupInfo['storeId'];
                // 只有收货，收券, 游戏活动类需要跳转到acquireDetail，其他类型跳转到acquireExchange;大转盘游戏也跳转到acquireDetail保持逻辑一致
                if (in_array($useRules, CouponGroupEnum::$ONLINE_FORM_USE_RULES)) {
                    $url = urlencode(sprintf(
                        app_conf('O2O_DEAL_DETAIL_URL'),
                        $action,
                        $dealLoadId,
                        $groupId,
                        $token,
                        $consumeType
                    ));
                    $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
                } else {
                    // 直接兑换的，不显示返回按钮，增加关闭按钮
                    $url = sprintf(
                        app_conf('O2O_DEAL_EXCHANGE_URL'),
                        $action,
                        $dealLoadId,
                        $groupId,
                        $useRules,
                        $storeId,
                        $token,
                        $consumeType
                    );
                    if ($useRules == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                        $prizeUrl = $url;
                        $prizeType = 'h5';
                    } else {
                        $url = urlencode($url);
                        $prizeUrl = sprintf('coupon://api?type=webview&identity=couponList&needback=false&needrefresh=true&needcloseall=true&title=%s&url=%s', $title, $url);
                    }
                }
            }
        } else {
            // 引导到精彩活动
            if (!empty($triggerList['event'])) {
                $event = array_pop($triggerList['event']);
                $prizeType = 'h5';
                $prizeTitle = $event['title'];
                $prizeDesc = $event['desc'];
                $prizeUrl = $event['url'];
            }
        }

        return array(
            'prizeList' => $prizeList,
            'prizeType' => $prizeType,
            'prizeTitle' => $prizeTitle,
            'prizeDesc' => $prizeDesc,
            'prizeUrl'  => $prizeUrl
        );
    }
}
