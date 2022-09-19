<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 向基金公司发起撤单请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseWithdraw extends ResponseBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $code;

    /**
     * 基金名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 金额或份额
     *
     * @var float
     * @required
     */
    private $amount;

    /**
     * 到账时间说明
     *
     * @var array
     * @optional
     */
    private $arriveTimeDes = NULL;

    /**
     * 订单类型
     *
     * @var string
     * @required
     */
    private $orderType;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ResponseWithdraw
     */
    public function setCode($code)
    {
        \Assert\Assertion::string($code);

        $this->code = $code;

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
     * @return ResponseWithdraw
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return ResponseWithdraw
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::float($amount);

        $this->amount = $amount;

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
     * @return ResponseWithdraw
     */
    public function setArriveTimeDes(array $arriveTimeDes = NULL)
    {
        $this->arriveTimeDes = $arriveTimeDes;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @param string $orderType
     * @return ResponseWithdraw
     */
    public function setOrderType($orderType)
    {
        \Assert\Assertion::string($orderType);

        $this->orderType = $orderType;

        return $this;
    }

}