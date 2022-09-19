<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 提现接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class RequestPaymentCashOut extends AbstractRequestBase
{
    /**
     *  用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     *  提现金额
     *
     * @var string
     * @required
     */
    private $money;

    /**
     * 客户端2android,3ios
     *
     * @var int
     * @required
     */
    private $os;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestPaymentCashOut
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
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return RequestPaymentCashOut
     */
    public function setMoney($money)
    {
        \Assert\Assertion::string($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param int $os
     * @return RequestPaymentCashOut
     */
    public function setOs($os)
    {
        \Assert\Assertion::integer($os);

        $this->os = $os;

        return $this;
    }

}