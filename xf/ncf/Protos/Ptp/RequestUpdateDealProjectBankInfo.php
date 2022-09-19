<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 更改项目信息中的银行卡信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class RequestUpdateDealProjectBankInfo extends ProtoBufferBase
{
    /**
     * 放款审批单编号
     *
     * @var string
     * @required
     */
    private $approveNumber;

    /**
     * 新的银行卡账号
     *
     * @var string
     * @required
     */
    private $bankcard;

    /**
     * 银行
     *
     * @var int
     * @required
     */
    private $bankId;

    /**
     * 开户网点
     *
     * @var string
     * @required
     */
    private $bankZone;

    /**
     * 开户人姓名
     *
     * @var string
     * @required
     */
    private $cardName;

    /**
     * 放款账号类型(1:对公 0:对私)
     *
     * @var int
     * @required
     */
    private $cardType;

    /**
     * 交易所结算方式(1:场内 2:场外)
     *
     * @var int
     * @optional
     */
    private $clearingType = 0;

    /**
     * @return string
     */
    public function getApproveNumber()
    {
        return $this->approveNumber;
    }

    /**
     * @param string $approveNumber
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setApproveNumber($approveNumber)
    {
        \Assert\Assertion::string($approveNumber);

        $this->approveNumber = $approveNumber;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankcard()
    {
        return $this->bankcard;
    }

    /**
     * @param string $bankcard
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setBankcard($bankcard)
    {
        \Assert\Assertion::string($bankcard);

        $this->bankcard = $bankcard;

        return $this;
    }
    /**
     * @return int
     */
    public function getBankId()
    {
        return $this->bankId;
    }

    /**
     * @param int $bankId
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setBankId($bankId)
    {
        \Assert\Assertion::integer($bankId);

        $this->bankId = $bankId;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankZone()
    {
        return $this->bankZone;
    }

    /**
     * @param string $bankZone
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setBankZone($bankZone)
    {
        \Assert\Assertion::string($bankZone);

        $this->bankZone = $bankZone;

        return $this;
    }
    /**
     * @return string
     */
    public function getCardName()
    {
        return $this->cardName;
    }

    /**
     * @param string $cardName
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setCardName($cardName)
    {
        \Assert\Assertion::string($cardName);

        $this->cardName = $cardName;

        return $this;
    }
    /**
     * @return int
     */
    public function getCardType()
    {
        return $this->cardType;
    }

    /**
     * @param int $cardType
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setCardType($cardType)
    {
        \Assert\Assertion::integer($cardType);

        $this->cardType = $cardType;

        return $this;
    }
    /**
     * @return int
     */
    public function getClearingType()
    {
        return $this->clearingType;
    }

    /**
     * @param int $clearingType
     * @return RequestUpdateDealProjectBankInfo
     */
    public function setClearingType($clearingType = 0)
    {
        $this->clearingType = $clearingType;

        return $this;
    }

}