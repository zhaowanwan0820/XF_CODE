<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 添加供应商返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class ResponseAddSupplier extends ResponseBase
{
    /**
     * 供应商id
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
     * @return ResponseAddSupplier
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}
