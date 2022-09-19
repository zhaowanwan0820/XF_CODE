<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * fundcode与userid做参数
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestFundCodeAndUserId extends AbstractRequestBase
{
    /**
     * fundCode
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * userId
     *
     * @var int
     * @optional
     */
    private $userId = NULL;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestFundCodeAndUserId
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestFundCodeAndUserId
     */
    public function setUserId($userId = NULL)
    {
        $this->userId = $userId;

        return $this;
    }

}