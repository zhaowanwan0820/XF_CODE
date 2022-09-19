<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取用户可赠送的投资券个数
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestGetUserUnusedDiscountCount extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 投资券类型，1为返现券，2为加息券，3为黄金券，0不区分
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 交易类型，1为p2p，2为duotou，3为gold
     *
     * @var int
     * @optional
     */
    private $consumeType = 1;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetUserUnusedDiscountCount
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetUserUnusedDiscountCount
     */
    public function setType($type = 0)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsumeType()
    {
        return $this->consumeType;
    }

    /**
     * @param int $consumeType
     * @return RequestGetUserUnusedDiscountCount
     */
    public function setConsumeType($consumeType = 1)
    {
        $this->consumeType = $consumeType;

        return $this;
    }

}