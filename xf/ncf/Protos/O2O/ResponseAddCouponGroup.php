<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 添加券组返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ResponseAddCouponGroup extends ResponseBase
{
    /**
     * 券组id
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
     * @return ResponseAddCouponGroup
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}