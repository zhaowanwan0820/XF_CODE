<?php

namespace core\service\bonus;

use core\service\BaseService;

/**
 * 红包相关接口
 *
 * 原BonusService与WXBonusService相关接口
 */
class BonusService extends BaseService
{
    private static $funcMap = [

        // 单个红包
        'getUsableBonus' => ['userId', 'isDetail', 'money', 'orderId', 'isEnterprise'], // 获取红包余额
        'getBonusLogList' => ['userId', 'page', 'size', 'isEnterprise'], // 红包列表
        'acquireBonus' => ['userId', 'money', 'token', 'itemId', 'itemType', 'createTime',
                           'expireTime', 'info', 'accountId'], // 发红包

        // 消费相关
        'consumeBonus' => ['userId', 'records', 'money', 'orderId', 'itemId', 'itemType',
                           'createTime', 'info', 'accountInfo'], // 消费
        'rollbackBonus' => ['orderId'], // 回滚
        'consumeConfirmBonus' => ['orderId'], // 消费确认

        // 红包组
        'getUnsendCount' => ['userId'], // 获取没抢完红包组个数
        'getGroupList' => ['userId', 'page', 'size'], // 红包组列表
        'getBonusGroupGrabList' => ['id'], // 获取红包组已抢列表
        'getBonusGroup' => ['id'], // 获取红包组信息

        // 其他
        'getUserBonusInfo' => ['userId'], // 获取汇总信息
        'getIncomeStatus' => ['userId'], //获取小红点
        'delIncomeStatus' => ['userId'], // 小红点消除

        //分享红包相关
        'bonusSend' => ['userId', 'siteId', 'page', 'pageSize'],
        'bonusGet' => ['userId'],
    ];


    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params)
    {
        // TODO灾备降级，目前普惠只有这一个需要降级，以后可能在ApiService中做统一处理
        if (ENV_IN_DISASTER) {
            self::setError('红包灾备降级', 1);
            return true;
        }

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

        // 处理特殊的ncfph的api接口
        $ncfphApiArr = array('bonusSend', 'bonusGet');
        if (in_array($name, $ncfphApiArr)) {
            return self::rpc('ncfwx', 'ncfph/'.$name, $args);
        }

        return self::rpc('bonus', 'ncf/' . $name, $args);
    }

    /**
     * 红包使用开关
     */
    public static function isBonusEnable() {
        return app_conf('BONUS_DISABLED_SWITCH') ? 0 : 1;
    }
}
