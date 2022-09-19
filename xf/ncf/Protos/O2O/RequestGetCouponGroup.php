<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取相应券组的全部信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class RequestGetCouponGroup extends AbstractRequestBase
{
    /**
     * 券组ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 投资年化金额
     *
     * @var float
     * @optional
     */
    private $annualizedAmount = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestGetCouponGroup
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return float
     */
    public function getAnnualizedAmount()
    {
        return $this->annualizedAmount;
    }

    /**
     * @param float $annualizedAmount
     * @return RequestGetCouponGroup
     */
    public function setAnnualizedAmount($annualizedAmount = 0)
    {
        $this->annualizedAmount = $annualizedAmount;

        return $this;
    }

}