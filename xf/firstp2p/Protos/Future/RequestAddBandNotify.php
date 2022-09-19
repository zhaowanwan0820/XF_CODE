<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 追加保障金通知请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestAddBandNotify extends AbstractRequestBase
{
    /**
     * 追加保障金ID
     *
     * @var string
     * @required
     */
    private $opOrderNo;

    /**
     * 审批结果 0：通过；1：未通过
     *
     * @var int
     * @required
     */
    private $result;

    /**
     * @return string
     */
    public function getOpOrderNo()
    {
        return $this->opOrderNo;
    }

    /**
     * @param string $opOrderNo
     * @return RequestAddBandNotify
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
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param int $result
     * @return RequestAddBandNotify
     */
    public function setResult($result)
    {
        \Assert\Assertion::integer($result);

        $this->result = $result;

        return $this;
    }

}