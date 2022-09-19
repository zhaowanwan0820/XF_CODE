<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获得资金记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ResponseGetUserFinancialRecord extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 时间
     *
     * @var string
     * @optional
     */
    private $time = '';

    /**
     * 类型
     *
     * @var string
     * @optional
     */
    private $type = '';

    /**
     * money
     *
     * @var string
     * @optional
     */
    private $money = '';

    /**
     * remark
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ResponseGetUserFinancialRecord
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
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param string $time
     * @return ResponseGetUserFinancialRecord
     */
    public function setTime($time = '')
    {
        $this->time = $time;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ResponseGetUserFinancialRecord
     */
    public function setType($type = '')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return ResponseGetUserFinancialRecord
     */
    public function setMoney($money = '')
    {
        $this->money = $money;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return ResponseGetUserFinancialRecord
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }

}