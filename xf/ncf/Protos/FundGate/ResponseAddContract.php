<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 添加协议返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseAddContract extends ResponseBase
{
    /**
     * 协议id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResponseAddContract
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}