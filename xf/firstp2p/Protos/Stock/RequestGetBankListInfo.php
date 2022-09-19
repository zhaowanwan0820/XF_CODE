<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取银行列表信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestGetBankListInfo extends AbstractRequestBase
{
    /**
     * 银行代码
     *
     * @var string
     * @required
     */
    private $bankCode;

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @param string $bankCode
     * @return RequestGetBankListInfo
     */
    public function setBankCode($bankCode)
    {
        \Assert\Assertion::string($bankCode);

        $this->bankCode = $bankCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetBankListInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}