<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 向基金公司发起申购
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class ResponseRemotePurchase extends ResponseBase
{
    /**
     * 基金名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 申购总额（单位：分）
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $code;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ResponseRemotePurchase
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return ResponseRemotePurchase
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

        return $this;
    }
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ResponseRemotePurchase
     */
    public function setCode($code)
    {
        \Assert\Assertion::string($code);

        $this->code = $code;

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
     * @return ResponseRemotePurchase
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
     * @return ResponseRemotePurchase
     */
    public function setProgressState($progressState = 1)
    {
        $this->progressState = $progressState;

        return $this;
    }

}