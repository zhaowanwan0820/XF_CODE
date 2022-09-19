<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 添加商品返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ResponseAddProduct extends ResponseBase
{
    /**
     * 商品id
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
     * @return ResponseAddProduct
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}