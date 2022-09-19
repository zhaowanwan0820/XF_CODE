<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * OpOrder号
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestOpOrderNo extends AbstractRequestBase
{
    /**
     * opOrder号
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
     * @return RequestOpOrderNo
     */
    public function setOpOrderNo($opOrderNo)
    {
        \Assert\Assertion::string($opOrderNo);

        $this->opOrderNo = $opOrderNo;

        return $this;
    }

}