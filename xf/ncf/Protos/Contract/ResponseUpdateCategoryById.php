<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 更新分类
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseUpdateCategoryById extends ProtoBufferBase
{
    /**
     * 状态(0:失败,1:成功,2:db插入失败)
     *
     * @var int
     * @required
     */
    private $status;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ResponseUpdateCategoryById
     */
    public function setStatus($status)
    {
        \Assert\Assertion::integer($status);

        $this->status = $status;

        return $this;
    }

}