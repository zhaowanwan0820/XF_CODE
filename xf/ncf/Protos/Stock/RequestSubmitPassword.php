<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提交用户交易密码
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestSubmitPassword extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 资金密码
     *
     * @var string
     * @required
     */
    private $fundPassword;

    /**
     * 交易密码
     *
     * @var string
     * @required
     */
    private $tradePassword;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestSubmitPassword
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundPassword()
    {
        return $this->fundPassword;
    }

    /**
     * @param string $fundPassword
     * @return RequestSubmitPassword
     */
    public function setFundPassword($fundPassword)
    {
        \Assert\Assertion::string($fundPassword);

        $this->fundPassword = $fundPassword;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradePassword()
    {
        return $this->tradePassword;
    }

    /**
     * @param string $tradePassword
     * @return RequestSubmitPassword
     */
    public function setTradePassword($tradePassword)
    {
        \Assert\Assertion::string($tradePassword);

        $this->tradePassword = $tradePassword;

        return $this;
    }

}