<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 查询用户持有的某支基金的可赎回份额
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetUsableRedeemShare extends ResponseBase
{
    /**
     * 可赎回份额
     *
     * @var float
     * @required
     */
    private $usableRedeemShare;

    /**
     * 未结算收益
     *
     * @var float
     * @optional
     */
    private $unliquidated = 0;

    /**
     * @return float
     */
    public function getUsableRedeemShare()
    {
        return $this->usableRedeemShare;
    }

    /**
     * @param float $usableRedeemShare
     * @return ResponseGetUsableRedeemShare
     */
    public function setUsableRedeemShare($usableRedeemShare)
    {
        \Assert\Assertion::float($usableRedeemShare);

        $this->usableRedeemShare = $usableRedeemShare;

        return $this;
    }
    /**
     * @return float
     */
    public function getUnliquidated()
    {
        return $this->unliquidated;
    }

    /**
     * @param float $unliquidated
     * @return ResponseGetUsableRedeemShare
     */
    public function setUnliquidated($unliquidated = 0)
    {
        $this->unliquidated = $unliquidated;

        return $this;
    }

}