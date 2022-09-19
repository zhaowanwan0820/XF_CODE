<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 修改存管信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestEditBankInfo extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 存管银行id
     *
     * @var string
     * @required
     */
    private $bankId;

    /**
     * 银行卡账户
     *
     * @var string
     * @optional
     */
    private $bankAccount = '';

    /**
     * 银行卡密码
     *
     * @var string
     * @optional
     */
    private $cardPW = '';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestEditBankInfo
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
    public function getBankId()
    {
        return $this->bankId;
    }

    /**
     * @param string $bankId
     * @return RequestEditBankInfo
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
     * @return RequestEditBankInfo
     */
    public function setBankAccount($bankAccount = '')
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }
    /**
     * @return string
     */
    public function getCardPW()
    {
        return $this->cardPW;
    }

    /**
     * @param string $cardPW
     * @return RequestEditBankInfo
     */
    public function setCardPW($cardPW = '')
    {
        $this->cardPW = $cardPW;

        return $this;
    }

}