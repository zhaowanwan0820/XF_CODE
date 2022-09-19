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
class ResponseGetContractTplIdentifier extends ProtoBufferBase
{
    /**
     * 合同模板标识信息
     *
     * @var array
     * @optional
     */
    private $data = NULL;

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ResponseGetContractTplIdentifier
     */
    public function setData(array $data = NULL)
    {
        $this->data = $data;

        return $this;
    }

}