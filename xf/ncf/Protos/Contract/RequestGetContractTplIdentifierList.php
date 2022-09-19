<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取模板标识列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class RequestGetContractTplIdentifierList extends ProtoBufferBase
{
    /**
     * 为了避免空对象
     *
     * @var int
     * @optional
     */
    private $temp = 0;

    /**
     * @return int
     */
    public function getTemp()
    {
        return $this->temp;
    }

    /**
     * @param int $temp
     * @return RequestGetContractTplIdentifierList
     */
    public function setTemp($temp = 0)
    {
        $this->temp = $temp;

        return $this;
    }

}