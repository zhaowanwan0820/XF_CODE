<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 搜索客户
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class RequestSearchCustomers extends AbstractRequestBase
{
    /**
     * 理财师ID
     *
     * @var int
     * @optional
     */
    private $cfpId = 0;

    /**
     * 搜索条件类别
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 搜索关键词
     *
     * @var string
     * @optional
     */
    private $skey = '0';

    /**
     * 回款时间最小值
     *
     * @var int
     * @optional
     */
    private $bidRepayDayMin = 0;

    /**
     * 回款时间最大值
     *
     * @var int
     * @optional
     */
    private $bidRepayDayMax = 0;

    /**
     * 检索条件投资收益
     *
     * @var int
     * @optional
     */
    private $bidYearrate = 0;

    /**
     * 检索条件投资期限
     *
     * @var int
     * @optional
     */
    private $bidRepayLimitTime = 0;

    /**
     * 检索条件最小佣金收入
     *
     * @var int
     * @optional
     */
    private $benefitMoneyMin = 0;

    /**
     * 检索条件最大佣金收入
     *
     * @var int
     * @optional
     */
    private $benefitMoneyMax = 0;

    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestSearchCustomers
     */
    public function setCfpId($cfpId = 0)
    {
        $this->cfpId = $cfpId;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestSearchCustomers
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getSkey()
    {
        return $this->skey;
    }

    /**
     * @param string $skey
     * @return RequestSearchCustomers
     */
    public function setSkey($skey = '0')
    {
        $this->skey = $skey;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidRepayDayMin()
    {
        return $this->bidRepayDayMin;
    }

    /**
     * @param int $bidRepayDayMin
     * @return RequestSearchCustomers
     */
    public function setBidRepayDayMin($bidRepayDayMin = 0)
    {
        $this->bidRepayDayMin = $bidRepayDayMin;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidRepayDayMax()
    {
        return $this->bidRepayDayMax;
    }

    /**
     * @param int $bidRepayDayMax
     * @return RequestSearchCustomers
     */
    public function setBidRepayDayMax($bidRepayDayMax = 0)
    {
        $this->bidRepayDayMax = $bidRepayDayMax;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidYearrate()
    {
        return $this->bidYearrate;
    }

    /**
     * @param int $bidYearrate
     * @return RequestSearchCustomers
     */
    public function setBidYearrate($bidYearrate = 0)
    {
        $this->bidYearrate = $bidYearrate;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidRepayLimitTime()
    {
        return $this->bidRepayLimitTime;
    }

    /**
     * @param int $bidRepayLimitTime
     * @return RequestSearchCustomers
     */
    public function setBidRepayLimitTime($bidRepayLimitTime = 0)
    {
        $this->bidRepayLimitTime = $bidRepayLimitTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getBenefitMoneyMin()
    {
        return $this->benefitMoneyMin;
    }

    /**
     * @param int $benefitMoneyMin
     * @return RequestSearchCustomers
     */
    public function setBenefitMoneyMin($benefitMoneyMin = 0)
    {
        $this->benefitMoneyMin = $benefitMoneyMin;

        return $this;
    }
    /**
     * @return int
     */
    public function getBenefitMoneyMax()
    {
        return $this->benefitMoneyMax;
    }

    /**
     * @param int $benefitMoneyMax
     * @return RequestSearchCustomers
     */
    public function setBenefitMoneyMax($benefitMoneyMax = 0)
    {
        $this->benefitMoneyMax = $benefitMoneyMax;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestSearchCustomers
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }

}