<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * DealProject
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoDealProject extends ProtoBufferBase
{
    /**
     * projectID
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * userId
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 放款审批单编号
     *
     * @var string
     * @optional
     */
    private $approveNumber = '';

    /**
     * 项目名称
     *
     * @var string
     * @optional
     */
    private $name = '';

    /**
     * 借款总金额
     *
     * @var float
     * @optional
     */
    private $borrowAmount = 0;

    /**
     * 还款方式
     *
     * @var int
     * @optional
     */
    private $loanType = 0;

    /**
     * 借款期限
     *
     * @var int
     * @optional
     */
    private $repayReriod = 0;

    /**
     * 借款综合成本(年化)
     *
     * @var float
     * @optional
     */
    private $rate = 0;

    /**
     * 项目授信额度
     *
     * @var float
     * @optional
     */
    private $credit = 0;

    /**
     * 项目简介（word附件地址）
     *
     * @var string
     * @optional
     */
    private $projectInfoUrl = '';

    /**
     * 项目要素(excel 附件地址)
     *
     * @var string
     * @optional
     */
    private $ProjectExtrainfoUrl = '';

    /**
     * 标的类型
     *
     * @var int
     * @optional
     */
    private $dealType = 0;

    /**
     * 项目锁定期
     *
     * @var int
     * @optional
     */
    private $lockPeriod = 0;

    /**
     * 项目赎回期
     *
     * @var int
     * @optional
     */
    private $redemptionPeriod = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoDealProject
     */
    public function setId($id = 0)
    {
        $this->id = $id;

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
     * @return ProtoDealProject
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
    public function getApproveNumber()
    {
        return $this->approveNumber;
    }

    /**
     * @param string $approveNumber
     * @return ProtoDealProject
     */
    public function setApproveNumber($approveNumber = '')
    {
        $this->approveNumber = $approveNumber;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProtoDealProject
     */
    public function setName($name = '')
    {
        $this->name = $name;

        return $this;
    }
    /**
     * @return float
     */
    public function getBorrowAmount()
    {
        return $this->borrowAmount;
    }

    /**
     * @param float $borrowAmount
     * @return ProtoDealProject
     */
    public function setBorrowAmount($borrowAmount = 0)
    {
        $this->borrowAmount = $borrowAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoanType()
    {
        return $this->loanType;
    }

    /**
     * @param int $loanType
     * @return ProtoDealProject
     */
    public function setLoanType($loanType = 0)
    {
        $this->loanType = $loanType;

        return $this;
    }
    /**
     * @return int
     */
    public function getRepayReriod()
    {
        return $this->repayReriod;
    }

    /**
     * @param int $repayReriod
     * @return ProtoDealProject
     */
    public function setRepayReriod($repayReriod = 0)
    {
        $this->repayReriod = $repayReriod;

        return $this;
    }
    /**
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     * @return ProtoDealProject
     */
    public function setRate($rate = 0)
    {
        $this->rate = $rate;

        return $this;
    }
    /**
     * @return float
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param float $credit
     * @return ProtoDealProject
     */
    public function setCredit($credit = 0)
    {
        $this->credit = $credit;

        return $this;
    }
    /**
     * @return string
     */
    public function getProjectInfoUrl()
    {
        return $this->projectInfoUrl;
    }

    /**
     * @param string $projectInfoUrl
     * @return ProtoDealProject
     */
    public function setProjectInfoUrl($projectInfoUrl = '')
    {
        $this->projectInfoUrl = $projectInfoUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getProjectExtrainfoUrl()
    {
        return $this->ProjectExtrainfoUrl;
    }

    /**
     * @param string $ProjectExtrainfoUrl
     * @return ProtoDealProject
     */
    public function setProjectExtrainfoUrl($ProjectExtrainfoUrl = '')
    {
        $this->ProjectExtrainfoUrl = $ProjectExtrainfoUrl;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return ProtoDealProject
     */
    public function setDealType($dealType = 0)
    {
        $this->dealType = $dealType;

        return $this;
    }
    /**
     * @return int
     */
    public function getLockPeriod()
    {
        return $this->lockPeriod;
    }

    /**
     * @param int $lockPeriod
     * @return ProtoDealProject
     */
    public function setLockPeriod($lockPeriod = 0)
    {
        $this->lockPeriod = $lockPeriod;

        return $this;
    }
    /**
     * @return int
     */
    public function getRedemptionPeriod()
    {
        return $this->redemptionPeriod;
    }

    /**
     * @param int $redemptionPeriod
     * @return ProtoDealProject
     */
    public function setRedemptionPeriod($redemptionPeriod = 0)
    {
        $this->redemptionPeriod = $redemptionPeriod;

        return $this;
    }

}