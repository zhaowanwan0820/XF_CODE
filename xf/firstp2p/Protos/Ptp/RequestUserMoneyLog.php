<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 资金纪录proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei5
 */
class RequestUserMoneyLog extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 资金类型
     *
     * @var string
     * @optional
     */
    private $logInfo = '';

    /**
     * 开始时间
     *
     * @var int
     * @optional
     */
    private $beginTime = 0;

    /**
     * 结束时间
     *
     * @var int
     * @optional
     */
    private $endTime = 0;

    /**
     * 纪录偏移量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * 纪录数量
     *
     * @var int
     * @optional
     */
    private $count = 20;

    /**
     * 资金记录显示的类型
     *
     * @var string
     * @optional
     */
    private $moneyType = 'money_only';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUserMoneyLog
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogInfo()
    {
        return $this->logInfo;
    }

    /**
     * @param string $logInfo
     * @return RequestUserMoneyLog
     */
    public function setLogInfo($logInfo = '')
    {
        $this->logInfo = $logInfo;

        return $this;
    }
    /**
     * @return int
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param int $beginTime
     * @return RequestUserMoneyLog
     */
    public function setBeginTime($beginTime = 0)
    {
        $this->beginTime = $beginTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return RequestUserMoneyLog
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return RequestUserMoneyLog
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return $this;
    }
    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return RequestUserMoneyLog
     */
    public function setCount($count = 20)
    {
        $this->count = $count;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoneyType()
    {
        return $this->moneyType;
    }

    /**
     * @param string $moneyType
     * @return RequestUserMoneyLog
     */
    public function setMoneyType($moneyType = 'money_only')
    {
        $this->moneyType = $moneyType;

        return $this;
    }

}