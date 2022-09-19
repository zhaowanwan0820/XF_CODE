<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 添加零售店返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class ResponseAddStore extends ResponseBase
{
    /**
     * 零售店id
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
     * @return ResponseAddStore
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}