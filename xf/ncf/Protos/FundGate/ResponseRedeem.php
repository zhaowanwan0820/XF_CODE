<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 发起基金赎回订单
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ<qicheng@ucfgroup.com>
 */
class ResponseRedeem extends ResponseBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 基金名称
     *
     * @var string
     * @required
     */
    private $fundName;

    /**
     * 赎回份额
     *
     * @var float
     * @required
     */
    private $share;

    /**
     * 到账时间说明
     *
     * @var array
     * @optional
     */
    private $arriveTimeDes = NULL;

    /**
     * 业务处理状态
     *
     * @var int
     * @optional
     */
    private $progressState = 1;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return ResponseRedeem
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundName()
    {
        return $this->fundName;
    }

    /**
     * @param string $fundName
     * @return ResponseRedeem
     */
    public function setFundName($fundName)
    {
        \Assert\Assertion::string($fundName);

        $this->fundName = $fundName;

        return $this;
    }
    /**
     * @return float
     */
    public function getShare()
    {
        return $this->share;
    }

    /**
     * @param float $share
     * @return ResponseRedeem
     */
    public function setShare($share)
    {
        \Assert\Assertion::float($share);

        $this->share = $share;

        return $this;
    }
    /**
     * @return array
     */
    public function getArriveTimeDes()
    {
        return $this->arriveTimeDes;
    }

    /**
     * @param array $arriveTimeDes
     * @return ResponseRedeem
     */
    public function setArriveTimeDes(array $arriveTimeDes = NULL)
    {
        $this->arriveTimeDes = $arriveTimeDes;

        return $this;
    }
    /**
     * @return int
     */
    public function getProgressState()
    {
        return $this->progressState;
    }

    /**
     * @param int $progressState
     * @return ResponseRedeem
     */
    public function setProgressState($progressState = 1)
    {
        $this->progressState = $progressState;

        return $this;
    }

}