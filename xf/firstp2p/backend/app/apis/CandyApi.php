<?php

namespace NCFGroup\Ptp\Apis;

use core\service\candy\CandyAccountService;
use core\service\candy\CandyEventService;

/**
 * 信力相关内部接口
 */
class CandyApi extends SparowBaseApi
{

    private $sourceConfig = [
        'xbtree' => CandyEventService::EVENT_ID_CANDYTREE,
        'xb666' => CandyEventService::EVENT_ID_CANDYDICE,
    ];

    private $eventConfig = [
        CandyEventService::EVENT_ID_CANDYTREE => [
            CandyEventService::CHANGE_TYPE_CANDYTREE_PAY,
            CandyEventService::CHANGE_TYPE_CANDYTREE_AWARD
        ],
        CandyEventService::EVENT_ID_CANDYDICE => [
            CandyEventService::CHANGE_TYPE_CANDYDICE_PAY,
            CandyEventService::CHANGE_TYPE_CANDYDICE_AWARD,
        ],
    ];

    /**
     * 检查用户是否在白名单
     */
    public function checkGame()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $res = (new \core\service\BwlistService)->inList('CANDY_GAME_WHITE', $userInfo['id']);
        if ($res) {
            $this->echoJson(0, 'ok');
        } else {
            $this->echoJson(20001, 'check failed');
        }
    }


    public function getUsable()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }

        $res = CandyAccountService::getAccountInfo($userInfo['id']);
        $coin = $res['amount'];
        $this->echoJson(0, 'ok', ['coin' => $coin]);
    }

    public function consume()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $orderId = $this->req['orderId'];
        if (empty($orderId)) $this->echoJson(10002, 'orderid missing');

        $amount = $this->req['candy'];
        if ($amount <= 0) $this->echoJson(10003, 'candy amount error');

        try {

            $event = $this->getEvent();
            (new CandyEventService)->changeAmount($event['eventId'], $orderId, $userInfo['id'], -$amount, $event['pay']);

        } catch (\Exception $e) {

            if ($e->getCode() == CandyEventService::EXCEPTION_CODE_TOKEN_EXISTS) {
                $this->echoJson(0, 'ok');
            }
            $this->echoJson($e->getCode() ?: 10000, $e->getMessage());
        }
        $this->echoJson(0, 'ok');

    }

    public function acquire()
    {
        $userInfo = $this->getUserByToken();
        // $userInfo['id'] = 2066470;
        if (!$userInfo['id']) {
            $this->echoJson(10001, '获取用户信息失败');
        }
        $orderId = $this->req['orderId'];
        if (empty($orderId)) $this->echoJson(10002, 'orderid missing');

        $amount = $this->req['candy'];
        if ($amount <= 0) $this->echoJson(10003, 'amount error');

        try {

            $event = $this->getEvent();
            (new CandyEventService)->changeAmount($event['eventId'], $orderId, $userInfo['id'], $amount, $event['award']);

        } catch (\Exception $e) {
            if ($e->getCode() == CandyEventService::EXCEPTION_CODE_TOKEN_EXISTS) {
                $this->echoJson(0, 'ok');
            }
            $this->echoJson($e->getCode() ?: 10000, $e->getMessage());
        }

        $this->echoJson(0, 'ok');
    }


    private function getEvent()
    {
        $source = $this->req['source'] ?: 'xbtree';
        if (!array_key_exists($source, $this->sourceConfig)) {
            throw new \Exception("非法来源", 10004);
        }

        $eventId = $this->sourceConfig[$source];

        return [
            'eventId' => $eventId,
            'pay' => $this->eventConfig[$eventId][0],
            'award' => $this->eventConfig[$eventId][1],
        ];
    }

}
