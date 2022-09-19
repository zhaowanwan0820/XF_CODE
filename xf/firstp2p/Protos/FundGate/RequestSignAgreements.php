<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 基金签订协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestSignAgreements extends AbstractRequestBase
{
    /**
     * 用户Id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 需要签订的协议Id数组
     *
     * @var array
     * @required
     */
    private $agreementIds;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestSignAgreements
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return array
     */
    public function getAgreementIds()
    {
        return $this->agreementIds;
    }

    /**
     * @param array $agreementIds
     * @return RequestSignAgreements
     */
    public function setAgreementIds(array $agreementIds)
    {
        $this->agreementIds = $agreementIds;

        return $this;
    }

}