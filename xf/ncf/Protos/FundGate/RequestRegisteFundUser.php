<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 基金开户请求对象
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class RequestRegisteFundUser extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户名字
     *
     * @var string
     * @required
     */
    private $custName;

    /**
     * 支付端签约号
     *
     * @var string
     * @required
     */
    private $payContractId;

    /**
     * 证件类型
     *
     * @var string
     * @required
     */
    private $certType;

    /**
     * 证件号码
     *
     * @var string
     * @required
     */
    private $certId;

    /**
     * 银行名称
     *
     * @var string
     * @required
     */
    private $bankName;

    /**
     * 银行简称
     *
     * @var string
     * @required
     */
    private $bankNo;

    /**
     * 银行卡号
     *
     * @var string
     * @required
     */
    private $custBankNo;

    /**
     * 电话号码
     *
     * @var string
     * @required
     */
    private $teleNo;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestRegisteFundUser
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
    public function getCustName()
    {
        return $this->custName;
    }

    /**
     * @param string $custName
     * @return RequestRegisteFundUser
     */
    public function setCustName($custName)
    {
        \Assert\Assertion::string($custName);

        $this->custName = $custName;

        return $this;
    }
    /**
     * @return string
     */
    public function getPayContractId()
    {
        return $this->payContractId;
    }

    /**
     * @param string $payContractId
     * @return RequestRegisteFundUser
     */
    public function setPayContractId($payContractId)
    {
        \Assert\Assertion::string($payContractId);

        $this->payContractId = $payContractId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCertType()
    {
        return $this->certType;
    }

    /**
     * @param string $certType
     * @return RequestRegisteFundUser
     */
    public function setCertType($certType)
    {
        \Assert\Assertion::string($certType);

        $this->certType = $certType;

        return $this;
    }
    /**
     * @return string
     */
    public function getCertId()
    {
        return $this->certId;
    }

    /**
     * @param string $certId
     * @return RequestRegisteFundUser
     */
    public function setCertId($certId)
    {
        \Assert\Assertion::string($certId);

        $this->certId = $certId;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * @param string $bankName
     * @return RequestRegisteFundUser
     */
    public function setBankName($bankName)
    {
        \Assert\Assertion::string($bankName);

        $this->bankName = $bankName;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankNo()
    {
        return $this->bankNo;
    }

    /**
     * @param string $bankNo
     * @return RequestRegisteFundUser
     */
    public function setBankNo($bankNo)
    {
        \Assert\Assertion::string($bankNo);

        $this->bankNo = $bankNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getCustBankNo()
    {
        return $this->custBankNo;
    }

    /**
     * @param string $custBankNo
     * @return RequestRegisteFundUser
     */
    public function setCustBankNo($custBankNo)
    {
        \Assert\Assertion::string($custBankNo);

        $this->custBankNo = $custBankNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getTeleNo()
    {
        return $this->teleNo;
    }

    /**
     * @param string $teleNo
     * @return RequestRegisteFundUser
     */
    public function setTeleNo($teleNo)
    {
        \Assert\Assertion::string($teleNo);

        $this->teleNo = $teleNo;

        return $this;
    }

}