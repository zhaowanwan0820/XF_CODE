<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取公募基金交易费率
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetChargeRates extends AbstractRequestBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 业务场景类别
     *
     * @var int
     * @optional
     */
    private $sceneType = 0;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetChargeRates
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getSceneType()
    {
        return $this->sceneType;
    }

    /**
     * @param int $sceneType
     * @return RequestGetChargeRates
     */
    public function setSceneType($sceneType = 0)
    {
        $this->sceneType = $sceneType;

        return $this;
    }

}