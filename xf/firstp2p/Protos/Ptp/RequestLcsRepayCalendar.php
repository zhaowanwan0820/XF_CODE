<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 理财师客户相关
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestLcsRepayCalendar extends ProtoBufferBase
{
    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $cfpId;

    /**
     * 还款类型
     *
     * @var string
     * @optional
     */
    private $repayType = 'alRepay';

    /**
     * 时间类型2016-02-03
     *
     * @var string
     * @required
     */
    private $date;

    /**
     * 数量
     *
     * @var int
     * @optional
     */
    private $count = 10;

    /**
     * 偏移量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestLcsRepayCalendar
     */
    public function setCfpId($cfpId)
    {
        \Assert\Assertion::integer($cfpId);

        $this->cfpId = $cfpId;

        return $this;
    }
    /**
     * @return string
     */
    public function getRepayType()
    {
        return $this->repayType;
    }

    /**
     * @param string $repayType
     * @return RequestLcsRepayCalendar
     */
    public function setRepayType($repayType = 'alRepay')
    {
        $this->repayType = $repayType;

        return $this;
    }
    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $date
     * @return RequestLcsRepayCalendar
     */
    public function setDate($date)
    {
        \Assert\Assertion::string($date);

        $this->date = $date;

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
     * @return RequestLcsRepayCalendar
     */
    public function setCount($count = 10)
    {
        $this->count = $count;

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
     * @return RequestLcsRepayCalendar
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return $this;
    }

}