<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 赠送投资券
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong<yanbingrong@ucfgroup.com>
 */
class RequestGiveDiscount extends AbstractRequestBase
{
    /**
     * 赠送者ID
     *
     * @var int
     * @required
     */
    private $fromUserId;

    /**
     * 接受者ID
     *
     * @var int
     * @required
     */
    private $toUserId;

    /**
     * 投资券ID，多个用逗号分隔
     *
     * @var string
     * @optional
     */
    private $discountId = '';

    /**
     * 接受者手机号
     *
     * @var string
     * @optional
     */
    private $toMobile = '';

    /**
     * @return int
     */
    public function getFromUserId()
    {
        return $this->fromUserId;
    }

    /**
     * @param int $fromUserId
     * @return RequestGiveDiscount
     */
    public function setFromUserId($fromUserId)
    {
        \Assert\Assertion::integer($fromUserId);

        $this->fromUserId = $fromUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getToUserId()
    {
        return $this->toUserId;
    }

    /**
     * @param int $toUserId
     * @return RequestGiveDiscount
     */
    public function setToUserId($toUserId)
    {
        \Assert\Assertion::integer($toUserId);

        $this->toUserId = $toUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDiscountId()
    {
        return $this->discountId;
    }

    /**
     * @param string $discountId
     * @return RequestGiveDiscount
     */
    public function setDiscountId($discountId = '')
    {
        $this->discountId = $discountId;

        return $this;
    }
    /**
     * @return string
     */
    public function getToMobile()
    {
        return $this->toMobile;
    }

    /**
     * @param string $toMobile
     * @return RequestGiveDiscount
     */
    public function setToMobile($toMobile = '')
    {
        $this->toMobile = $toMobile;

        return $this;
    }

}