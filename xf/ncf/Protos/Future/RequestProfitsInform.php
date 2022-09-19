<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提取利润
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestProfitsInform extends AbstractRequestBase
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
     * 备注
     *
     * @var string
     * @optional
     */
    private $remarks = '';

    /**
     * @return string
     */
    public function getOpOrderNo()
    {
        return $this->opOrderNo;
    }

    /**
     * @param string $opOrderNo
     * @return RequestProfitsInform
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
     * @return RequestProfitsInform
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
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @param string $remarks
     * @return RequestProfitsInform
     */
    public function setRemarks($remarks = '')
    {
        $this->remarks = $remarks;

        return $this;
    }

}