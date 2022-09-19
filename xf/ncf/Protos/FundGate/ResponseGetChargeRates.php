<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取公募基金交易费率
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetChargeRates extends ResponseBase
{
    /**
     * 日常申购费率
     *
     * @var array
     * @required
     */
    private $purchaseRates;

    /**
     * 日常赎回费率
     *
     * @var array
     * @required
     */
    private $redeemRates;

    /**
     * @return array
     */
    public function getPurchaseRates()
    {
        return $this->purchaseRates;
    }

    /**
     * @param array $purchaseRates
     * @return ResponseGetChargeRates
     */
    public function setPurchaseRates(array $purchaseRates)
    {
        $this->purchaseRates = $purchaseRates;

        return $this;
    }
    /**
     * @return array
     */
    public function getRedeemRates()
    {
        return $this->redeemRates;
    }

    /**
     * @param array $redeemRates
     * @return ResponseGetChargeRates
     */
    public function setRedeemRates(array $redeemRates)
    {
        $this->redeemRates = $redeemRates;

        return $this;
    }

}