<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 已投项目详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestDealLoadDetail extends AbstractRequestBase
{
    /**
     * 投资ID
     *
     * @var int
     * @required
     */
    private $loadId;

    /**
     * 投资记录首页记录数
     *
     * @var int
     * @optional
     */
    private $dealLoanSize = 0;

    /**
     * @return int
     */
    public function getLoadId()
    {
        return $this->loadId;
    }

    /**
     * @param int $loadId
     * @return RequestDealLoadDetail
     */
    public function setLoadId($loadId)
    {
        \Assert\Assertion::integer($loadId);

        $this->loadId = $loadId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoanSize()
    {
        return $this->dealLoanSize;
    }

    /**
     * @param int $dealLoanSize
     * @return RequestDealLoadDetail
     */
    public function setDealLoanSize($dealLoanSize = 0)
    {
        $this->dealLoanSize = $dealLoanSize;

        return $this;
    }

}