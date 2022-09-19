<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提交银行信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestSubmitBankInfo extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 银行Code
     *
     * @var string
     * @required
     */
    private $bankId;

    /**
     * 用户银行账户
     *
     * @var string
     * @required
     */
    private $bankAccount;

    /**
     * 银行卡密码
     *
     * @var string
     * @required
     */
    private $cardPassword;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestSubmitBankInfo
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
    public function getBankId()
    {
        return $this->bankId;
    }

    /**
     * @param string $bankId
     * @return RequestSubmitBankInfo
     */
    public function setBankId($bankId)
    {
        \Assert\Assertion::string($bankId);

        $this->bankId = $bankId;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    /**
     * @param string $bankAccount
     * @return RequestSubmitBankInfo
     */
    public function setBankAccount($bankAccount)
    {
        \Assert\Assertion::string($bankAccount);

        $this->bankAccount = $bankAccount;

        return $this;
    }
    /**
     * @return string
     */
    public function getCardPassword()
    {
        return $this->cardPassword;
    }

    /**
     * @param string $cardPassword
     * @return RequestSubmitBankInfo
     */
    public function setCardPassword($cardPassword)
    {
        \Assert\Assertion::string($cardPassword);

        $this->cardPassword = $cardPassword;

        return $this;
    }

}