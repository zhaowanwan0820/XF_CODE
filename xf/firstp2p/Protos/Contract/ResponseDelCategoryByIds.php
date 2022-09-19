<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 批量或单独删除合同分类（逻辑删除）
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseDelCategoryByIds extends ProtoBufferBase
{
    /**
     * 状态(0:失败,1:成功)
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     * @return ResponseDelCategoryByIds
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }

}