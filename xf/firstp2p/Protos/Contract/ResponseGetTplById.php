<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照模板id取得模板列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetTplById extends ProtoBufferBase
{
    /**
     * 模板信息
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetTplById
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}