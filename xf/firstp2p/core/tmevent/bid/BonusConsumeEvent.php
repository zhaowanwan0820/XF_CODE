<?php

namespace core\tmevent\bid;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\Logger;
use core\service\BonusService;

class BonusConsumeEvent extends GlobalTransactionEvent
{
    // 用户ID
    protected $userId;

    // 可用红包记录
    protected $records;

    // 红包转账信息
    protected $accountInfo;

    // 待使用红包金额
    protected $useMoney;

    // 订单号
    protected $orderId;

    // deal name
    protected $dealName;

    protected $rpc;

    /**
     * 构造函数
     * @param [type] $userId    用户ID
     * @param [type] $bonusInfo 红包信息，获取可用红包的返回值
     * @param [type] $orderId   统一订单号
     * @param [type] $dealName  标的名称
     */
    public function __construct($userId, $bonusInfo, $orderId, $dealName, $dealType)
    {
        //接收参数
        $this->userId = $userId;
        $this->records = $bonusInfo['bonuses'];
        $this->accountInfo = $bonusInfo['accountInfo'];
        $this->useMoney = $bonusInfo['money'];
        $this->orderId = $orderId;
        $this->dealName = $dealName;
        $this->dealType = $dealType;

        $this->service = new BonusService;

        Logger::info(implode('|', [__METHOD__, $userId, json_encode($bonusInfo), $orderId, $dealName]));
    }

    public function execute()
    {
        //处理投资逻辑，成功返回true，失败返回false，其他结果一律会重试
        if (empty($this->accountInfo)) return true; // 没有红包直接过

        return $this->service->consumeBonus($this->userId, $this->records,
            $this->useMoney, $this->orderId, time(), $this->dealName, $this->accountInfo, $this->dealType);
    }

    public function rollback()
    {
        //处理逻辑，成功返回true，失败返回false，其他结果一律会重试
        return $this->service->rollbackBonus($this->orderId);
    }

    public function commit()
    {
        if (empty($this->accountInfo)) return true; // 没有红包直接过
        return $this->service->consumeConfirmBonus($this->orderId);
    }

}
