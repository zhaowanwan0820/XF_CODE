<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 返回模板标识列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseGetContractTplIdentifierList extends ProtoBufferBase
{
    /**
     * 模板标识列表
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
     * @return ResponseGetContractTplIdentifierList
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}