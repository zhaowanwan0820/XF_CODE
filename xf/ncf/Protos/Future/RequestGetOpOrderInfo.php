<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 操作订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestGetOpOrderInfo extends AbstractRequestBase
{
    /**
     * 操作订单号
     *
     * @var string
     * @required
     */
    private $opOrderNo;

    /**
     * @return string
     */
    public function getOpOrderNo()
    {
        return $this->opOrderNo;
    }

    /**
     * @param string $opOrderNo
     * @return RequestGetOpOrderInfo
     */
    public function setOpOrderNo($opOrderNo)
    {
        \Assert\Assertion::string($opOrderNo);

        $this->opOrderNo = $opOrderNo;

        return $this;
    }

}