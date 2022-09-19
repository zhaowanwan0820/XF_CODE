<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 合约续期
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestExtensionInform extends AbstractRequestBase
{
    /**
     * 操作订单
     *
     * @var string
     * @required
     */
    private $opOrderNo;

    /**
     * 审核结果
     *
     * @var int
     * @required
     */
    private $auditResult;

    /**
     * 合约结束时间
     *
     * @var string
     * @required
     */
    private $endDate;

    /**
     * 备注
     *
     * @var string
     * @required
     */
    private $remarks;

    /**
     * @return string
     */
    public function getOpOrderNo()
    {
        return $this->opOrderNo;
    }

    /**
     * @param string $opOrderNo
     * @return RequestExtensionInform
     */
    public function setOpOrderNo($opOrderNo)
    {
        \Assert\Assertion::string($opOrderNo);

        $this->opOrderNo = $opOrderNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getAuditResult()
    {
        return $this->auditResult;
    }

    /**
     * @param int $auditResult
     * @return RequestExtensionInform
     */
    public function setAuditResult($auditResult)
    {
        \Assert\Assertion::integer($auditResult);

        $this->auditResult = $auditResult;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param string $endDate
     * @return RequestExtensionInform
     */
    public function setEndDate($endDate)
    {
        \Assert\Assertion::string($endDate);

        $this->endDate = $endDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @param string $remarks
     * @return RequestExtensionInform
     */
    public function setRemarks($remarks)
    {
        \Assert\Assertion::string($remarks);

        $this->remarks = $remarks;

        return $this;
    }

}