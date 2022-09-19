<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 修改FundExt中的属性
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestSetFundExtInfo extends AbstractRequestBase
{
    /**
     * 申购份额确认时间
     *
     * @var string
     * @required
     */
    private $purchaseShareConfirmTime;

    /**
     * 赎回份额确认时间
     *
     * @var string
     * @required
     */
    private $redeemShareConfirmTime;

    /**
     * 赎回金额预计到账时间
     *
     * @var string
     * @required
     */
    private $redeemAmountExpectedGetTime;

    /**
     * 申购撤销金额到账时间
     *
     * @var string
     * @required
     */
    private $purchaseWithdrawAmountGetTime;

    /**
     * 基金Id
     *
     * @var string
     * @required
     */
    private $fundId;

    /**
     * 清盘资金到账时间
     *
     * @var string
     * @required
     */
    private $windUpAmountGetTime;

    /**
     * 现金红利资金到账时间
     *
     * @var string
     * @required
     */
    private $bonusCashAmountGetTime;

    /**
     * @return string
     */
    public function getPurchaseShareConfirmTime()
    {
        return $this->purchaseShareConfirmTime;
    }

    /**
     * @param string $purchaseShareConfirmTime
     * @return RequestSetFundExtInfo
     */
    public function setPurchaseShareConfirmTime($purchaseShareConfirmTime)
    {
        \Assert\Assertion::string($purchaseShareConfirmTime);

        $this->purchaseShareConfirmTime = $purchaseShareConfirmTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getRedeemShareConfirmTime()
    {
        return $this->redeemShareConfirmTime;
    }

    /**
     * @param string $redeemShareConfirmTime
     * @return RequestSetFundExtInfo
     */
    public function setRedeemShareConfirmTime($redeemShareConfirmTime)
    {
        \Assert\Assertion::string($redeemShareConfirmTime);

        $this->redeemShareConfirmTime = $redeemShareConfirmTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getRedeemAmountExpectedGetTime()
    {
        return $this->redeemAmountExpectedGetTime;
    }

    /**
     * @param string $redeemAmountExpectedGetTime
     * @return RequestSetFundExtInfo
     */
    public function setRedeemAmountExpectedGetTime($redeemAmountExpectedGetTime)
    {
        \Assert\Assertion::string($redeemAmountExpectedGetTime);

        $this->redeemAmountExpectedGetTime = $redeemAmountExpectedGetTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getPurchaseWithdrawAmountGetTime()
    {
        return $this->purchaseWithdrawAmountGetTime;
    }

    /**
     * @param string $purchaseWithdrawAmountGetTime
     * @return RequestSetFundExtInfo
     */
    public function setPurchaseWithdrawAmountGetTime($purchaseWithdrawAmountGetTime)
    {
        \Assert\Assertion::string($purchaseWithdrawAmountGetTime);

        $this->purchaseWithdrawAmountGetTime = $purchaseWithdrawAmountGetTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundId()
    {
        return $this->fundId;
    }

    /**
     * @param string $fundId
     * @return RequestSetFundExtInfo
     */
    public function setFundId($fundId)
    {
        \Assert\Assertion::string($fundId);

        $this->fundId = $fundId;

        return $this;
    }
    /**
     * @return string
     */
    public function getWindUpAmountGetTime()
    {
        return $this->windUpAmountGetTime;
    }

    /**
     * @param string $windUpAmountGetTime
     * @return RequestSetFundExtInfo
     */
    public function setWindUpAmountGetTime($windUpAmountGetTime)
    {
        \Assert\Assertion::string($windUpAmountGetTime);

        $this->windUpAmountGetTime = $windUpAmountGetTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonusCashAmountGetTime()
    {
        return $this->bonusCashAmountGetTime;
    }

    /**
     * @param string $bonusCashAmountGetTime
     * @return RequestSetFundExtInfo
     */
    public function setBonusCashAmountGetTime($bonusCashAmountGetTime)
    {
        \Assert\Assertion::string($bonusCashAmountGetTime);

        $this->bonusCashAmountGetTime = $bonusCashAmountGetTime;

        return $this;
    }

}