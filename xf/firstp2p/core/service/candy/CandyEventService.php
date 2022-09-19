<?php

namespace core\service\candy;

use libs\db\Db;
use libs\utils\Logger;
use core\service\candy\CandyAccountService;

/**
 * 信宝活动相关
 */
class CandyEventService
{

    // 信宝树
    const EVENT_ID_CANDYTREE = 1;
    // 信宝筛子
    const EVENT_ID_CANDYDICE = 2;
    // 游戏中心大转盘
    const EVENT_ID_DZP = 3;
    //助力获得
    const EVENT_ID_ASSISTANCE = 4;
    // SPROW平台公共ID
    const EVENT_ID_SPAROW = 5;

    const CHANGE_TYPE_CANDYTREE_PAY = '信宝树消耗';
    const CHANGE_TYPE_CANDYDICE_PAY = '欢乐猜消耗';
    const CHANGE_TYPE_DZP_PAY = '大转盘游戏消耗';

    const CHANGE_TYPE_CANDYTREE_AWARD = '信宝树获得';
    const CHANGE_TYPE_CANDYDICE_AWARD = '欢乐猜获得';
    const CHANGE_TYPE_DZP_AWARD = '大转盘游戏获得';

    const CHANGE_TYPE_ASSISTANCE_AWARD = '新春助力获得';

    // 处理成功
    const STAUTS_SUCCESS = 1;

    // 异常：token已存在
    const EXCEPTION_CODE_TOKEN_EXISTS = 100001;

    /**
     * 余额变更
     */
    public function changeAmount($eventId, $token, $userId, $amount, $changeType)
    {
        $orderInfo = $this->getOrderInfo($token);
        if (isset($orderInfo['id'])) {
            throw new \Exception('token已存在', self::EXCEPTION_CODE_TOKEN_EXISTS);
        }

        Db::getInstance('candy')->startTrans();
        try {
            // 创建订单
            $this->createOrder($eventId, $token, $userId, $amount);

            // 修改余额
            $note = "event:{$eventId}, token:{$token}";
            $accountService = new CandyAccountService();
            $accountService->changeAmount($userId, $amount, $changeType, $note);

            Db::getInstance('candy')->commit();
        } catch (\Exception $e) {
            Db::getInstance('candy')->rollback();
            throw new \Exception('支付失败:'.$e->getMessage());
        }
    }

    /**
     * 创建订单
     */
    private function createOrder($eventId, $token, $userId, $amount)
    {
        $data = array(
            'event_id' => $eventId,
            'token' => $token,
            'user_id' => $userId,
            'amount' => $amount,
            'status' => self::STAUTS_SUCCESS,
            'create_time' => time(),
        );

        return Db::getInstance('candy')->insert('candy_event_order', $data);
    }

    /**
     * 获取订单信息
     */
    public function getOrderInfo($token)
    {
        $sql = "SELECT * FROM candy_event_order WHERE token='{$token}'";
        return Db::getInstance('candy')->getRow($sql);
    }

}
