<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 申请追加保障金返回结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseAddBand extends ResponseBase
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
     * @return ResponseAddBand
     */
    public function setOpOrderNo($opOrderNo)
    {
        \Assert\Assertion::string($opOrderNo);

        $this->opOrderNo = $opOrderNo;

        return $this;
    }

}